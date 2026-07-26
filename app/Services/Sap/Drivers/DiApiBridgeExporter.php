<?php

namespace App\Services\Sap\Drivers;

use App\Services\Sap\Contracts\SapExporter;
use App\Services\Sap\SapExportResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Exporta el asiento preliminar por el DI API, a través del servicio intermedio
 * en .NET (ver docs/sap-di-api-bridge.md). Es la única vía disponible en
 * SAP B1 9 sobre SQL Server, donde no existe Service Layer.
 */
class DiApiBridgeExporter implements SapExporter
{
    private string $baseUrl;

    private string $apiKey;

    private int $timeout;

    /**
     * @param  Collection  $params  filas de `management` del grupo accountability
     */
    public function __construct(Collection $params)
    {
        $this->baseUrl = rtrim((string) $params->where('name', 'bridge_url')->first()?->value, '/');
        $this->apiKey = (string) $params->where('name', 'bridge_api_key')->first()?->value;
        $timeout = (int) ($params->where('name', 'bridge_timeout')->first()?->value ?: 0);
        $this->timeout = $timeout > 0 ? $timeout : 120;
    }

    public function mode(): string
    {
        return 'DI';
    }

    public function addJournalVoucher(array $payload, string $idempotencyKey, array $udfTypes = []): SapExportResult
    {
        if ($this->baseUrl === '') {
            return SapExportResult::failure('No está configurada la URL del servicio DI API.');
        }

        $body = $payload;
        $body['UdfTypes'] = $udfTypes;

        $response = Http::baseUrl($this->baseUrl)
            ->withoutVerifying()
            ->timeout($this->timeout)
            ->withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Idempotency-Key' => $idempotencyKey,
            ])
            ->post('/journal-vouchers', $body);

        $json = $response->json() ?? [];

        if (! $response->successful() || ($json['success'] ?? false) !== true) {
            return SapExportResult::failure(
                $json['error']['message'] ?? 'Error al exportar a SAP por DI API',
                isset($json['error']['code']) ? (string) $json['error']['code'] : null
            );
        }

        return SapExportResult::success(isset($json['key']) ? (string) $json['key'] : null);
    }
}
