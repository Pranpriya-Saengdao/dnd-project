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
        Schema::create('game_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->restrictOnDelete();
            $table->integer('team');
            $table->integer('current_hp');
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->integer('turn_order');
            $table->integer('skill_uses_remaining')->default(2);
            $table->boolean('is_alive')->default(true);
            $table->timestamps();

            $table->unique(['game_match_id', 'user_id']);
            $table->unique(['game_match_id', 'character_id']);
            $table->unique(['game_match_id', 'turn_order']);
            $table->index(['game_match_id', 'team']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_members');
    }
};
