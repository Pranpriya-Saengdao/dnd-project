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
        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();
            $table->string('map_key', 50);
            $table->string('status', 20)->default('waiting');
            $table->integer('current_round')->default(1);
            $table->integer('current_turn_order')->default(1);
            $table->boolean('has_moved_this_turn')->default(false);
            $table->boolean('has_positioned_this_turn')->default(false);
            $table->boolean('has_acted_this_turn')->default(false);
            $table->integer('winner_team')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_matches');
    }
};
