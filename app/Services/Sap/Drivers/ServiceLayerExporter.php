<?php

namespace App\Services\Sap\Drivers;

use App\Helpers\Hana;
use App\Services\Sap\Contracts\SapExporter;
use App\Services\Sap\SapExportResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

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

    private bool $hana;

    /**
     * @param  Collection  $params  filas de `management` del grupo accountability
     */
    public function __construct(Collection $params)
    {
        $this->baseUrl = rtrim((string) $params->where('name', 'service_layer')->first()?->value, '/').'/b1s/v1/';
        $this->companyDB = (string) $params->where('name', 'bd_sap')->first()?->value;
        $this->username = (string) $params->where('name', 'user')->first()?->value;
        $this->password = (string) $params->where('name', 'password')->first()?->value;
        $this->hana = $params->where('name', 'hana_enable')->first()?->value == 'SI';
    }

    public function mode(): string
    {
        return 'SL';
    }

    public function addJournalVoucher(array $payload, string $idempotencyKey, array $udfTypes = [], array $inventoryDrafts = []): SapExportResult
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
            // Primero las entradas de mercancías en borrador: si alguna falla no se crea nada.
            $drafts = [];
            try {
                foreach ($inventoryDrafts as $documentId => $draft) {
                    $response = $http->post('Drafts', $draft);

                    if (! $response->successful()) {
                        $this->deleteDrafts($http, $drafts);

                        return SapExportResult::failure(
                            'Entrada de mercancías: '.($response->json()['error']['message']['value'] ?? 'Error al crear el borrador en SAP'),
                            (string) ($response->json()['error']['code'] ?? '')
                        );
                    }

                    $drafts[$documentId] = (string) $response->json()['DocEntry'];
                }

                $response = $http->post('JournalVouchersService_Add', $payload);

                if (! $response->successful()) {
                    // Sin asiento, los borradores quedarían sueltos en SAP.
                    $this->deleteDrafts($http, $drafts);

                    return SapExportResult::failure(
                        $response->json()['error']['message']['value'] ?? 'Error al exportar a SAP',
                        (string) ($response->json()['error']['code'] ?? '')
                    );
                }

                // JournalVouchersService_Add no devuelve el número del asiento borrador:
                // se busca en SAP por la referencia con la que se envió.
                $reference = $payload['JournalVoucher']['JournalEntry']['Reference'] ?? null;

                return SapExportResult::success($reference ? $this->findVoucherNumber($reference) : null, $drafts);
            } catch (Throwable $e) {
                // Corte de conexión u otro error inesperado: no dejar borradores sueltos.
                $this->deleteDrafts($http, $drafts);
                throw $e;
            }
        } finally {
            // Libera la licencia del Service Layer en lugar de esperar el timeout de sesión.
            rescue(fn () => $http->post('Logout'), null, false);
        }
    }

    /**
     * Elimina los borradores ya creados. Si alguno no se puede borrar se registra para
     * eliminarlo a mano en SAP (Borradores).
     *
     * @param  array<int, string>  $drafts  DocEntry por id de documento
     */
    private function deleteDrafts($http, array $drafts): void
    {
        foreach ($drafts as $docEntry) {
            $deleted = rescue(fn () => $http->delete("Drafts($docEntry)")->successful(), false, false);
            if (! $deleted) {
                Log::warning('No se pudo eliminar el borrador de entrada de mercancías en SAP', ['DocEntry' => $docEntry]);
            }
        }
    }

    /**
     * Número de lote (OBTF.BatchNum) del asiento preliminar con esa referencia (Ref1).
     * Es informativo: si la consulta falla, la exportación igual se da por exitosa.
     */
    private function findVoucherNumber(string $reference): ?string
    {
        return rescue(function () use ($reference) {
            if ($this->hana) {
                $db = Config::get('database.connections.hana.database');
                $reference = str_replace("'", "''", $reference);
                $rows = Hana::query(
                    "select top 1 \"BatchNum\" from $db.OBTF where \"Ref1\" = '$reference' order by \"BatchNum\" desc"
                );
                $number = is_array($rows) ? ($rows[0]['BatchNum'] ?? null) : null;
            } else {
                $number = DB::connection('sap')
                    ->table('OBTF')
                    ->where('Ref1', $reference)
                    ->orderByDesc('BatchNum')
                    ->value('BatchNum');
            }

            return $number !== null ? (string) $number : null;
        }, null);
    }
}
