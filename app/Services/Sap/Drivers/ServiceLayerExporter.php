<?php

namespace App\Services\Sap\Drivers;

use App\Services\Sap\Contracts\SapExporter;
use App\Services\Sap\SapExportResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Exporta el asiento preliminar por el Service Layer de SAP B1 (REST nativo).
 * Disponible en SAP B1 sobre HANA y, desde la 9.3, también sobre SQL Server.
 */
class ServiceLayerExporter implements SapExporter
{
    private string $baseUrl;

    private string $companyDB;

    private string $username;

    private string $password;

    /**
     * @param  Collection  $params  filas de `management` del grupo accountability
     */
    public function __construct(Collection $params)
    {
        $this->baseUrl = rtrim((string) $params->where('name', 'service_layer')->first()?->value, '/').'/b1s/v1/';
        $this->companyDB = (string) $params->where('name', 'bd_sap')->first()?->value;
        $this->username = (string) $params->where('name', 'user')->first()?->value;
        $this->password = (string) $params->where('name', 'password')->first()?->value;
    }

    public function mode(): string
    {
        return 'SL';
    }

    public function addJournalVoucher(array $payload, string $idempotencyKey, array $udfTypes = []): SapExportResult
    {
        $login = Http::withoutVerifying()
            ->baseUrl($this->baseUrl)
            ->post('Login', [
                'CompanyDB' => $this->companyDB,
                'UserName' => $this->username,
                'Password' => $this->password,
            ]);

        if (! $login->successful()) {
            return SapExportResult::failure(
                $login->json()['error']['message']['value'] ?? 'Error al conectar con SAP',
                (string) ($login->json()['error']['code'] ?? '')
            );
        }

        $session = $login['SessionId'];

        $http = Http::baseUrl($this->baseUrl)
            ->withoutVerifying()
            ->withHeaders(['Cookie' => 'B1SESSION='.$session.'; ROUTEID=.node9']);

        try {
            $response = $http->post('JournalVouchersService_Add', $payload);

            if (! $response->successful()) {
                return SapExportResult::failure(
                    $response->json()['error']['message']['value'] ?? 'Error al exportar a SAP',
                    (string) ($response->json()['error']['code'] ?? '')
                );
            }

            // JournalVouchersService_Add no devuelve el número del asiento borrador.
            return SapExportResult::success();
        } finally {
            // Libera la licencia del Service Layer en lugar de esperar el timeout de sesión.
            rescue(fn () => $http->post('Logout'), null, false);
        }
    }
}
