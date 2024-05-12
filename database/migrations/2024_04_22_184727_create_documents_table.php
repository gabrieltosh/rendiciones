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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type_document_sap');
            $table->enum('type_calculation',['Grossing Up','Grossing Down']);
            $table->decimal('ice',8,2)->default(0)->nullable(); //descuento
            $table->decimal('tasas',8,2)->default(0)->nullable(); //descuento
            $table->decimal('exento',8,2)->default(0)->nullable(); //descuento
            $table->boolean('ice_status')->default(0); //descuento
            $table->boolean('tasas_status')->default(0); //descuento
            $table->boolean('exento_status')->default(0); //descuento
            $table->boolean('authorization_number_status')->default(0); //descuento
            $table->boolean('cuf_status')->default(0); //descuento
            $table->boolean('control_code_status')->default(0); //descuento
            $table->boolean('business_name_status')->default(0); //descuento
            $table->boolean('nit_status')->default(0); //descuento
            $table->boolean('discount_status')->default(0); //descuento
            $table->boolean('gift_card_status')->default(0); //descuento
            $table->boolean('rate_zero_status')->default(0); //descuento
            $table->unsignedBigInteger('profile_id');
            $table->foreign('profile_id')->references('id')->on('profiles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
