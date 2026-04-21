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
        Schema::table('account_aliases', function (Blueprint $table) {
            $table->dropUnique('account_aliases_acct_code_unique');
            $table->string('format_code')->nullable()->after('acct_code');
            $table->string('acct_name')->nullable()->after('format_code');
            $table->string('name')->nullable()->after('acct_name');
        });
    }

    public function down(): void
    {
        Schema::table('account_aliases', function (Blueprint $table) {
            $table->dropColumn(['format_code', 'acct_name', 'name']);
            $table->unique('acct_code');
        });
    }
};
