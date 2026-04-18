<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accountabilities', function (Blueprint $table) {
            $table->foreignId('current_level_id')
                ->nullable()
                ->constrained('authorization_cycle_levels')
                ->nullOnDelete()
                ->after('sap_exported');
        });
    }

    public function down(): void
    {
        Schema::table('accountabilities', function (Blueprint $table) {
            $table->dropForeign(['current_level_id']);
            $table->dropColumn('current_level_id');
        });
    }
};
