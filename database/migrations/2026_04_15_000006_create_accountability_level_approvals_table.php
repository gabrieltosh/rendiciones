<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accountability_level_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accountability_id')->constrained('accountabilities')->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('authorization_cycle_levels')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('status'); // 'aprobado' | 'rechazado'
            $table->text('comments')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accountability_level_approvals');
    }
};
