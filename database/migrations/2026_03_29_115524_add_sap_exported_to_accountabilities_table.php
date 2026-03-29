<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accountabilities', function (Blueprint $table) {
            $table->tinyInteger('sap_exported')->default(0)->after('comments');
        });
    }

    public function down(): void
    {
        Schema::table('accountabilities', function (Blueprint $table) {
            $table->dropColumn('sap_exported');
        });
    }
};
