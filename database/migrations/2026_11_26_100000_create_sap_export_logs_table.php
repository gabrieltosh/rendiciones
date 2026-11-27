<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de exportaciones a SAP: un registro por cada intento (exportación al
 * autorizar y reintentos desde Pendientes de Exportación), exitoso o no, con el
 * mensaje de SAP y el asiento enviado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sap_export_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accountability_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('success')->default(0);
            $table->string('mode', 10)->nullable();     // SL / DI
            $table->string('sap_key')->nullable();      // número del asiento preliminar
            $table->text('message')->nullable();        // error de SAP o mensaje de éxito
            $table->longText('payload')->nullable();    // asiento y borradores enviados (JSON)
            $table->foreign('accountability_id')->references('id')->on('accountabilities')->cascadeOnDelete();
            $table->index(['accountability_id', 'created_at']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sap_export_logs');
    }
};
