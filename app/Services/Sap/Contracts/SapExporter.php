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
     * @param  array  $inventoryDrafts  [id del documento => payload] entradas de mercancías en
     *                                  borrador de las facturas con ítems; se crean antes que el
     *                                  asiento y, si este falla, se eliminan
     */
    public function addJournalVoucher(array $payload, string $idempotencyKey, array $udfTypes = [], array $inventoryDrafts = []): SapExportResult;

    /** Identificador del modo de exportación ('SL' o 'DI'). */
    public function mode(): string;
}
