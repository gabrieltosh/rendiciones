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
        Schema::create('document_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->enum('type',['IVA','IT','IUE','RC-IVA','EXCENTO','TASA','ICE']);
            $table->string('percentage');
            $table->string('account');
            $table->foreign('document_id')->references('id')->on('documents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_details');
    }
};
