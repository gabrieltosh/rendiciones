<?php

namespace App\Services\Sap\Contracts;

use App\Services\Sap\SapExportResult;

interface SapExporter
{
    /**
     * Crea el asiento preliminar (JournalVoucher) en SAP B1.
     *
     * @param  array  $payload         ['JournalVoucher' => ['JournalEntry' => [...]]]
     * @param  string  $idempotencyKey  clave para evitar asientos duplicados en reintentos
     * @param  array  $udfTypes        [nombre del UDF => tipo SAP], solo lo usa el DI API
     */
    public function addJournalVoucher(array $payload, string $idempotencyKey, array $udfTypes = []): SapExportResult;

    /** Identificador del modo de exportación ('SL' o 'DI'). */
    public function mode(): string;
}
