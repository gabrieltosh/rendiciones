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
        Schema::create('accountability_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('value');
            $table->unsignedBigInteger('field_id');
            $table->unsignedBigInteger('accountability_detail_id');
            $table->foreign('field_id')->references('id')->on('document_fields');
            $table->foreign('accountability_detail_id')->references('id')->on('accountability_details');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('accountability_fields');
    }
};
