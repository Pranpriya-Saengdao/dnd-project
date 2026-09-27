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
        Schema::create('dice_rolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_member_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 30);
            $table->integer('result');
            $table->timestamp('rolled_at')->useCurrent();
            $table->timestamps();

            $table->index(['game_match_id', 'rolled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dice_rolls');
    }
};
