<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accountabilities', function (Blueprint $table) {
            // Clave del asiento creado en SAP. Solo la devuelve el modo DI API
            // (GetNewObjectKey); en Service Layer queda nula.
            $table->string('sap_trans_id', 50)->nullable()->after('sap_exported');
            $table->dateTime('sap_exported_at')->nullable()->after('sap_trans_id');
            // 'SL' o 'DI': por qué camino se exportó la rendición.
            $table->string('sap_export_mode', 10)->nullable()->after('sap_exported_at');
        });
    }

    public function down(): void
    {
        Schema::table('accountabilities', function (Blueprint $table) {
            $table->dropColumn(['sap_trans_id', 'sap_exported_at', 'sap_export_mode']);
        });
    }
};
