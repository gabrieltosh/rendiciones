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
        Schema::create('accountabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id');
            $table->unsignedBigInteger('user_id');
            $table->string('employee_code')->nullable();
            $table->string('employee_name')->nullable();
            $table->string('account_code');
            $table->string('account_name');
            $table->decimal('total',8,2);
            $table->string('description');
            $table->string('preliminary')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->text('comments')->nullable();
            $table->enum('status',['Pendiente','Rechazado','Autorizado','Anulado'])->nullable();
            $table->foreign('profile_id')->references('id')->on('profiles');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accountabilities');
    }
};
