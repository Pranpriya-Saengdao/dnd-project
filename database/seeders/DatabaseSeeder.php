<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Skill;
use App\Models\User;
use App\Services\GameEngine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Item::firstOrCreate(['item_name' => 'Heal Potion'], [
            'item_type' => 'Consumable', 'description' => 'Restore HP', 'effect_value' => 15,
        ]);
        Item::firstOrCreate(['item_name' => 'Strength Potion'], [
            'item_type' => 'Buff', 'description' => 'ATK +10 for this turn', 'effect_value' => 10,
        ]);
        Skill::firstOrCreate(['skill_name' => 'Attack'], ['description' => 'Basic attack: ATK + D20']);

        // Default admin: admin / admin1234
        if (! User::where('email', 'admin@example.com')->exists()) {
            $engine = app(GameEngine::class);
            $ch = $engine->createCharacter();
            $engine->starterItems($ch);
            User::create(['username' => 'admin', 'email' => 'admin@example.com', 'password' => Hash::make('admin1234'),
                'role' => 'admin', 'character_character_id' => $ch->character_id]);
        }
    }
}
