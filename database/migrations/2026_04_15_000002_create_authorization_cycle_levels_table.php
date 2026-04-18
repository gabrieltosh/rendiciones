<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorization_cycle_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('authorization_cycles')->cascadeOnDelete();
            $table->unsignedInteger('order');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_cycle_levels');
    }
};
