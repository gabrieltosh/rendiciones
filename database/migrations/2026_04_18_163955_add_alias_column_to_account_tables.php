<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('detail_accounts', 'alias')) {
            Schema::table('detail_accounts', function (Blueprint $table) {
                $table->string('alias')->nullable()->after('account_name');
            });
        }

        if (!Schema::hasColumn('general_accounts', 'alias')) {
            Schema::table('general_accounts', function (Blueprint $table) {
                $table->string('alias')->nullable()->after('account_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('detail_accounts', 'alias')) {
            Schema::table('detail_accounts', function (Blueprint $table) {
                $table->dropColumn('alias');
            });
        }

        if (Schema::hasColumn('general_accounts', 'alias')) {
            Schema::table('general_accounts', function (Blueprint $table) {
                $table->dropColumn('alias');
            });
        }
    }
};
