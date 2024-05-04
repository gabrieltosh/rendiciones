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
        Schema::create('accountability_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accountability_id'); //rendicion
            $table->string('account'); //cuenta
            $table->string('account_name'); //cuenta
            $table->date('date'); //fecha
            $table->unsignedBigInteger('document_id'); //tipo de documento
            $table->string('document_number'); //numero de documento
            $table->string('authorization_number'); //numero de autorización
            $table->string('cuf'); //codigo de control
            $table->string('control_code'); //codigo de control
            $table->unsignedBigInteger('supplier_code'); //codigo de proveedor;
            $table->string('business_name'); //razon social
            $table->string('nit'); //NIT
            $table->string('concept'); //concepto
            $table->decimal('amount',8,2); //importe
            $table->decimal('discount',8,2)->nullable(); //descuento
            $table->decimal('excento',8,2)->nullable(); //descuento
            $table->decimal('rate',8,2)->nullable(); //Tasa
            $table->decimal('gift_card',8,2)->nullable(); //gif card
            $table->decimal('rate_zero',8,2)->nullable(); //Tasa Cero
            $table->decimal('ice',8,2)->nullable(); //Tasa Cero
            $table->string('project_code')->nullable(); // codigo proyecto
            $table->string('distribution_rule_one')->nullable(); //Centro de Costo 1
            $table->string('distribution_rule_second')->nullable(); //Centro de Costo 2
            $table->string('distribution_rule_three')->nullable(); //Centro de Costo 3
            $table->string('distribution_rule_four')->nullable(); //Centro de Costo 4
            $table->string('distribution_rule_five')->nullable(); //Centro de Costo 5
            $table->foreign('accountability_id')->references('id')->on('accountabilities');
            $table->foreign('document_id')->references('id')->on('documents');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accountability_details');
    }
};
