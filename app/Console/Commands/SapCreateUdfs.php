<?php

namespace App\Console\Commands;

use App\Models\Management;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SapCreateUdfs extends Command
{
    protected $signature = 'sap:create-udfs
                            {--table=JDT1 : Tabla SAP B1 para los campos de rendición (ej: JDT1, OJDT)}';

    protected $description = 'Crea los campos de usuario (UDFs) en SAP B1 vía Service Layer';

    // Grupos a procesar: [group => tabla SAP]
    private array $groupTables = [
        'accountability_detail' => null,   // usa --table option (JDT1)
        'employee'              => 'OCRD', // campos de socio de negocio
    ];

    // Mapeo de nombres de campo → tipo SAP
    private array $fieldTypes = [
        'date'      => ['type' => 'db_Date',  'size' => null],
        'amount'    => ['type' => 'db_Float', 'size' => null],
        'discount'  => ['type' => 'db_Float', 'size' => null],
        'excento'   => ['type' => 'db_Float', 'size' => null],
        'rate'      => ['type' => 'db_Float', 'size' => null],
        'gift_card' => ['type' => 'db_Float', 'size' => null],
        'rate_zero' => ['type' => 'db_Float', 'size' => null],
        'ice'       => ['type' => 'db_Float', 'size' => null],
    ];

    public function handle(): int
    {
        $defaultTable = $this->option('table');

        // Credenciales Service Layer
        $params    = Management::where('group', 'accountability')->get();
        $baseUrl   = $params->where('name', 'service_layer')->first()?->value;
        $companyDB = $params->where('name', 'bd_sap')->first()?->value;
        $username  = $params->where('name', 'user')->first()?->value;
        $password  = $params->where('name', 'password')->first()?->value;

        if (!$baseUrl || !$companyDB || !$username || !$password) {
            $this->error('Faltan parámetros de conexión SAP en la tabla Management.');
            return self::FAILURE;
        }

        $this->info("Conectando a Service Layer: {$baseUrl}");

        // Login
        $login = Http::withoutVerifying()
            ->baseUrl("{$baseUrl}/b1s/v1/")
            ->post('Login', [
                'CompanyDB' => $companyDB,
                'UserName'  => $username,
                'Password'  => $password,
            ]);

        if (!$login->successful()) {
            $this->error('Error al autenticarse: ' . ($login->json()['error']['message']['value'] ?? $login->body()));
            return self::FAILURE;
        }

        $session = $login->json()['SessionId'];
        $this->info('Sesión iniciada correctamente.');

        $http = Http::withoutVerifying()
            ->baseUrl("{$baseUrl}/b1s/v1/")
            ->withHeaders(['Cookie' => "B1SESSION={$session}; ROUTEID=.node9"]);

        $created = 0;
        $skipped = 0;
        $errors  = 0;

        foreach ($this->groupTables as $group => $sapTable) {
            $table  = $sapTable ?? $defaultTable;
            $fields = Management::where('group', $group)->get()
                        // Solo procesar campos cuyo valor empiece con U_
                        ->filter(fn($f) => str_starts_with($f->value, 'U_'));

            if ($fields->isEmpty()) {
                continue;
            }

            $this->newLine();
            $this->line("<fg=cyan>── Tabla {$table} (grupo: {$group})</>");

            foreach ($fields as $field) {
                $udfName    = substr($field->value, 2); // quitar "U_"
                $typeConfig = $this->fieldTypes[$field->name] ?? ['type' => 'db_Alpha', 'size' => 100];

                $body = [
                    'TableName'   => $table,
                    'Name'        => $udfName,
                    'Description' => $field->label,
                    'Type'        => $typeConfig['type'],
                ];

                if ($typeConfig['size'] !== null) {
                    $body['Size'] = $typeConfig['size'];
                }

                $response = $http->post('UserFieldsMD', $body);

                if ($response->successful()) {
                    $this->line("  <fg=green>✓</> Creado: U_{$udfName} ({$field->label})");
                    $created++;
                } elseif ($response->status() === 400) {
                    $errorCode = $response->json()['error']['code'] ?? null;
                    // -2028 = UDF ya existe en SAP B1
                    if ($errorCode == -2028) {
                        $this->line("  <fg=yellow>~</> Ya existe: U_{$udfName} ({$field->label})");
                        $skipped++;
                    } else {
                        $message = $response->json()['error']['message']['value'] ?? $response->body();
                        $this->line("  <fg=red>✗</> Error en U_{$udfName}: {$message}");
                        $errors++;
                    }
                } else {
                    $message = $response->json()['error']['message']['value'] ?? $response->body();
                    $this->line("  <fg=red>✗</> Error en U_{$udfName}: {$message}");
                    $errors++;
                }
            }
        }

        // Logout
        $http->post('Logout');

        $this->newLine();
        $this->table(
            ['Estado', 'Cantidad'],
            [
                ['<fg=green>Creados</>',     $created],
                ['<fg=yellow>Ya existían</>', $skipped],
                ['<fg=red>Errores</>',       $errors],
            ]
        );

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
