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
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('base_hp');
            $table->integer('attack');
            $table->integer('wisdom')->default(0);
            $table->integer('dexterity')->default(0);
            $table->integer('intelligence')->default(0);
            $table->integer('charisma')->default(0);
            $table->integer('attack_range')->default(1);
            $table->integer('skill_range')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
