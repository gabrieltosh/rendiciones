<?php

namespace App\Console\Commands;

use App\Models\Management;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Diagnóstico de la conexión con el Service Layer de SAP B1: usa los mismos
 * parámetros de `management` (grupo accountability) que la exportación.
 */
class SapTestConnection extends Command
{
    protected $signature = 'sap:test-connection
                            {--company= : Probar con otra CompanyDB en vez de bd_sap}
                            {--user= : Probar con otro usuario en vez del configurado}
                            {--ask-password : Pedir la contraseña por consola en vez de usar la configurada}
                            {--timeout=30 : Segundos de espera por petición}';

    protected $description = 'Verifica la conexión y el login contra el Service Layer de SAP B1';

    public function handle(): int
    {
        $params = Management::where('group', 'accountability')->get();
        $get = fn (string $name) => $params->where('name', $name)->first()?->value;

        $rawUrl = (string) $get('service_layer');
        $companyDB = $this->option('company') ?? (string) $get('bd_sap');
        $username = $this->option('user') ?? (string) $get('user');
        $password = $this->option('ask-password') ? (string) $this->secret('Contraseña SAP') : (string) $get('password');
        $timeout = (int) $this->option('timeout');

        $this->line('<fg=cyan>── Parámetros</>');
        $this->table(['Parámetro', 'Valor'], [
            ['export_mode', $get('export_mode') ?: '(vacío → Service Layer)'],
            ['hana_enable', $get('hana_enable') ?: '(vacío)'],
            ['service_layer', '['.$rawUrl.']'],
            ['bd_sap (CompanyDB)', '['.$companyDB.'] ('.strlen($companyDB).' caracteres)'],
            ['user', '['.$username.']'],
            ['password', $password === '' ? '(vacía)' : str_repeat('*', 8).' ('.strlen($password).' caracteres)'],
        ]);

        if ($companyDB !== trim($companyDB) || $username !== trim($username)) {
            $this->warn('CompanyDB o usuario tienen espacios al inicio/fin: SAP no los va a reconocer.');
        }

        if ($rawUrl === '' || $companyDB === '' || $username === '' || $password === '') {
            $this->error('Faltan parámetros de conexión SAP en la tabla management (grupo accountability).');

            return self::FAILURE;
        }

        $baseUrl = rtrim($rawUrl, '/').'/b1s/v1/';

        // 1. Red: ¿llega el servidor al puerto del Service Layer?
        $this->newLine();
        $this->line('<fg=cyan>── 1. Conectividad de red</>');
        $parts = parse_url($rawUrl);
        $host = $parts['host'] ?? null;
        $port = $parts['port'] ?? (($parts['scheme'] ?? 'https') === 'https' ? 443 : 80);

        if (! $host) {
            $this->error("La URL del Service Layer no es válida: {$rawUrl}");

            return self::FAILURE;
        }

        $ip = gethostbyname($host);
        $this->line("Host: {$host} → {$ip}   Puerto: {$port}");

        $start = microtime(true);
        $socket = @fsockopen($host, $port, $errno, $errstr, 10);
        if (! $socket) {
            $this->error("No se pudo abrir conexión TCP a {$host}:{$port} ({$errno} {$errstr}).");
            $this->line('Revisar firewall/VPN entre este servidor y el Service Layer.');

            return self::FAILURE;
        }
        fclose($socket);
        $this->info(sprintf('✓ Puerto abierto (%d ms)', (microtime(true) - $start) * 1000));

        // 2. Login
        $this->newLine();
        $this->line('<fg=cyan>── 2. Login</>');
        $this->line("POST {$baseUrl}Login");

        $start = microtime(true);
        try {
            $login = Http::withoutVerifying()
                ->timeout($timeout)
                ->baseUrl($baseUrl)
                ->post('Login', [
                    'CompanyDB' => $companyDB,
                    'UserName' => $username,
                    'Password' => $password,
                ]);
        } catch (Throwable $e) {
            $this->error('Error de conexión HTTP: '.$e->getMessage());

            return self::FAILURE;
        }
        $ms = (int) ((microtime(true) - $start) * 1000);

        $this->line("HTTP {$login->status()} ({$ms} ms)");

        if (! $login->successful()) {
            $code = $login->json('error.code');
            $message = $login->json('error.message.value') ?? $login->body();

            $this->error("Login falló [{$code}]: {$message}");
            $this->line('Respuesta completa:');
            $this->line($login->body());
            $this->printHints((string) $message);

            return self::FAILURE;
        }

        $session = $login->json('SessionId');
        $this->info('✓ Login correcto. Versión SL: '.($login->json('Version') ?? '?').'  Timeout sesión: '.($login->json('SessionTimeout') ?? '?').' min');

        $http = Http::withoutVerifying()
            ->timeout($timeout)
            ->baseUrl($baseUrl)
            ->withHeaders(['Cookie' => 'B1SESSION='.$session.'; ROUTEID=.node9']);

        // 3. Consulta simple para confirmar que la sesión opera sobre la base
        try {
            $this->newLine();
            $this->line('<fg=cyan>── 3. Consulta de prueba</>');
            $info = $http->post('CompanyService_GetCompanyInfo');

            if ($info->successful()) {
                $this->info('✓ Empresa: '.($info->json('CompanyName') ?? '?').'  Versión B1: '.($info->json('Version') ?? '?'));
            } else {
                $this->warn('Login OK pero la consulta falló: '.($info->json('error.message.value') ?? $info->body()));
            }
        } finally {
            rescue(fn () => $http->post('Logout'), null, false);
            $this->line('Sesión cerrada (Logout).');
        }

        return self::SUCCESS;
    }

    private function printHints(string $message): void
    {
        $this->newLine();
        $this->line('<fg=yellow>── Posibles causas</>');

        if (stripos($message, 'SLD') !== false || stripos($message, 'database instance') !== false) {
            $this->line(' • El Service Layer respondió, pero no encontró la base en el SLD (System Landscape Directory).');
            $this->line(' • Verificar que bd_sap sea EXACTAMENTE el nombre del esquema/BD (en HANA distingue mayúsculas).');
            $this->line(' • Verificar que la empresa esté registrada en el SLD / Control Center (https://<servidor>:40000/ControlCenter).');
            $this->line(' • Verificar que el servicio SLD y el License Server estén arriba, y que el Service Layer apunte al SLD correcto (b1s.conf).');
            $this->line(' • Si se cambió de servidor/instancia HANA, re-registrar la instancia de BD en el SLD y reiniciar el Service Layer.');
            $this->line(' • Probar con: php artisan sap:test-connection --company=NOMBRE_EXACTO');

            return;
        }

        if (stripos($message, 'password') !== false || stripos($message, 'user') !== false) {
            $this->line(' • Usuario o contraseña SAP incorrectos, o el usuario está bloqueado / sin licencia.');

            return;
        }

        $this->line(' • Revisar el mensaje anterior y los logs del Service Layer en el servidor SAP.');
    }
}
