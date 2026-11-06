<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reparto del gasto de un documento de la rendición en varias líneas del asiento.
 *
 * - accountability_detail_splits: reparto de cada dimensión (Centro de Costo 1..5)
 *   entre uno o varios códigos con su %.
 * - accountability_detail_lines: líneas resultantes de combinar los repartos de todas
 *   las dimensiones, cada una con su % del gasto, su cuenta y su glosa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accountability_detail_splits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accountability_detail_id');
            $table->unsignedTinyInteger('dimension'); // 1..5
            $table->string('code', 50);
            $table->decimal('percentage', 9, 4);
            $table->foreign('accountability_detail_id')->references('id')->on('accountability_details')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('accountability_detail_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accountability_detail_id');
            $table->string('combination', 300); // "1:CC01|2:CC05", identifica la línea al regenerarla
            $table->decimal('percentage', 12, 8);
            $table->string('account');
            $table->string('account_name');
            $table->string('concept')->nullable(); // glosa propia de la línea
            $table->string('distribution_rule_one')->nullable();
            $table->string('distribution_rule_second')->nullable();
            $table->string('distribution_rule_three')->nullable();
            $table->string('distribution_rule_four')->nullable();
            $table->string('distribution_rule_five')->nullable();
            $table->foreign('accountability_detail_id')->references('id')->on('accountability_details')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accountability_detail_lines');
        Schema::dropIfExists('accountability_detail_splits');
    }
};
