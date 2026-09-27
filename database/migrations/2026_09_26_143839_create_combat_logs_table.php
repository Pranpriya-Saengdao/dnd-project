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
        Schema::create('combat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attacker_member_id')->constrained('game_members')->cascadeOnDelete();
            $table->foreignId('target_member_id')->constrained('game_members')->cascadeOnDelete();
            $table->foreignId('dice_roll_id')->constrained('dice_rolls')->restrictOnDelete();
            $table->string('attack_type', 20);
            $table->integer('damage');
            $table->integer('round_number');
            $table->timestamps();

            $table->index(['game_match_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combat_logs');
    }
};
