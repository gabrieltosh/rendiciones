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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->enum('type',['Administrador','Usuario']);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('distribution_rule_one')->nullable();
            $table->string('distribution_rule_second')->nullable();
            $table->string('distribution_rule_three')->nullable();
            $table->string('card_code');
            $table->string('password');
            $table->enum('status',['Activo','PreActivo','Bloqueado']);
            $table->rememberToken();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
