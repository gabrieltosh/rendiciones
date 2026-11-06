<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Facturas con ítems: el documento de la rendición registra artículos de inventario
 * y al exportar se crea en SAP una entrada de mercancías de inventario en borrador
 * (OIGN) contra la cuenta del gasto.
 *
 * - documents.items_status: el tipo de documento pide ítems.
 * - accountability_detail_items: artículos de cada documento (precio sin IVA).
 * - accountability_details.sap_receipt_draft_entry: DocEntry del borrador creado en SAP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->boolean('items_status')->default(0);
        });

        Schema::table('accountability_details', function (Blueprint $table) {
            $table->string('sap_receipt_draft_entry')->nullable();
        });

        Schema::create('accountability_detail_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accountability_detail_id');
            $table->string('item_code', 50);
            $table->string('item_name');
            $table->decimal('quantity', 19, 6);
            $table->decimal('price', 19, 6); // precio unitario sin IVA
            $table->string('warehouse_code', 8);
            $table->foreign('accountability_detail_id')->references('id')->on('accountability_details')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accountability_detail_items');

        Schema::table('accountability_details', function (Blueprint $table) {
            $table->dropColumn('sap_receipt_draft_entry');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('items_status');
        });
    }
};
