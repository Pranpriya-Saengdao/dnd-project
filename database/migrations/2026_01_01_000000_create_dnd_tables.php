<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mirrors the Oracle D&D schema used by this application.
// Item and Skill are created before their referencing tables.
return new class extends Migration
{
    private function stamps(Blueprint $t, string $deleted = 'deleteat'): void
    {
        $t->timestamp('createdat')->useCurrent();
        $t->timestamp('updatedat')->useCurrent();
        if ($deleted) {
            $t->timestamp($deleted)->nullable();
        }
    }

    private function fk(Blueprint $t, string $col, string $table, string $pk): void
    {
        $t->foreignId($col)->nullable()->constrained($table, $pk);
    }

    public function up(): void
    {
        Schema::create('Character', function (Blueprint $t) {
            $t->id('character_id');
            $t->string('character_name', 50);
            $t->string('cha_class', 50)->nullable();
            foreach (['max_hp', 'attack', 'charisma', 'dexterity', 'intelligence'] as $c) {
                $t->integer($c)->nullable();
            }
            $this->stamps($t);
        });

        Schema::create('Users', function (Blueprint $t) {
            $t->id('user_id');
            $t->string('username', 50);
            $t->string('password', 255);
            $t->string('email', 45)->unique();
            $t->string('role', 45)->nullable();
            $this->stamps($t);
            $this->fk($t, 'character_character_id', 'Character', 'character_id');
        });

        Schema::create('Item', function (Blueprint $t) {
            $t->id('item_id');
            $t->string('item_name', 50);
            $t->string('item_type', 45);
            $t->string('description', 255)->nullable();
            $t->integer('effect_value')->nullable();
            $this->stamps($t, 'deletedat');
        });

        Schema::create('Skill', function (Blueprint $t) {
            $t->id('skill_id');
            $t->string('skill_name', 50);
            $t->string('description', 255)->nullable();
        });

        Schema::create('Game_Room', function (Blueprint $t) {
            $t->id('room_id');
            $t->string('room_password', 50)->nullable();
            $t->string('status', 20)->nullable();
            $t->string('room_name', 45)->nullable();
            $this->stamps($t, '');
        });

        Schema::create('Match', function (Blueprint $t) {
            $t->id('match_id');
            $t->timestamp('started_at')->useCurrent();
            $t->timestamp('ended_at')->nullable();
            $t->integer('current_turn')->nullable();
            $this->fk($t, 'room_id', 'Game_Room', 'room_id');
            $t->integer('total_round')->nullable();
        });

        Schema::create('Map', function (Blueprint $t) {
            $t->id('map_id');
            $t->timestamp('created_at')->useCurrent();
            $t->string('map_name', 50)->nullable();
            $t->integer('width')->nullable();
            $t->integer('height')->nullable();
            $t->longText('map_data')->nullable();
            $t->timestamp('updatedat')->useCurrent();
            $t->timestamp('deleteat')->nullable();
            $this->fk($t, 'match_match_id', 'Match', 'match_id');
        });

        Schema::create('Team', function (Blueprint $t) {
            $t->id('team_id');
            $t->string('team_name', 50)->nullable();
            $this->fk($t, 'match_id', 'Match', 'match_id');
        });

        Schema::create('Game_Member', function (Blueprint $t) {
            $t->id('member_id');
            $t->integer('hp')->nullable();
            $t->integer('position_x')->nullable();
            $t->integer('position_y')->nullable();
            $t->string('is_ready', 20)->nullable();
            $t->string('is_alive', 20)->nullable();
            $this->stamps($t, '');
            $this->fk($t, 'user_id', 'Users', 'user_id');
            $this->fk($t, 'character_id', 'Character', 'character_id');
            $this->fk($t, 'team_id', 'Team', 'team_id');
            $this->fk($t, 'match_id', 'Match', 'match_id');
        });

        Schema::create('Character_Item', function (Blueprint $t) {
            $t->id('character_item_id');
            $this->fk($t, 'character_id', 'Character', 'character_id');
            $this->fk($t, 'item_id', 'Item', 'item_id');
            $t->integer('quantity')->nullable();
            $this->stamps($t, 'deletedat');
        });

        // Kept for schema parity; unused now that special skills were removed.
        Schema::create('Character_Skill', function (Blueprint $t) {
            $t->id('character_skill_id');
            $this->fk($t, 'skill_id', 'Skill', 'skill_id');
            $this->fk($t, 'character_id', 'Character', 'character_id');
            $t->timestamp('createdat')->useCurrent();
        });

        Schema::create('Dice_Roll', function (Blueprint $t) {
            $t->id('dice_roll_id');
            $this->fk($t, 'room_id', 'Game_Room', 'room_id');
            $this->fk($t, 'member_id', 'Game_Member', 'member_id');
            $t->integer('dice_result')->nullable();
            $t->string('roll_purpose', 50)->nullable();
            $t->timestamp('roll_at')->useCurrent();
            $this->fk($t, 'match_match_id', 'Match', 'match_id');
        });

        Schema::create('Chat_Message', function (Blueprint $t) {
            $t->id('message_id');
            $this->fk($t, 'room_id', 'Game_Room', 'room_id');
            $this->fk($t, 'member_id', 'Game_Member', 'member_id');
            $t->string('message', 255)->nullable();
            $t->timestamp('createdat')->useCurrent();
            $this->fk($t, 'match_id', 'Match', 'match_id');
        });

        Schema::create('Combat_Log', function (Blueprint $t) {
            $t->id('combat_id');
            $t->integer('damage_dealt')->nullable();
            $t->integer('turn_number')->nullable();
            $t->timestamp('createdat')->useCurrent();
            $this->fk($t, 'attacker_member_id', 'Game_Member', 'member_id');
            $this->fk($t, 'target_member_id', 'Game_Member', 'member_id');
            $this->fk($t, 'room_id', 'Game_Room', 'room_id');
            $this->fk($t, 'skill_id', 'Skill', 'skill_id');
            $this->fk($t, 'dice_roll_id', 'Dice_Roll', 'dice_roll_id');
            $this->fk($t, 'match_id', 'Match', 'match_id');
        });

        Schema::create('Game_Result', function (Blueprint $t) {
            $t->id('result_id');
            $this->fk($t, 'room_id', 'Game_Room', 'room_id');
            $this->fk($t, 'member_id', 'Game_Member', 'member_id');
            $t->integer('result')->nullable();      // 1 = win, 0 = lose
            $t->integer('total_damage')->nullable();
            $t->timestamp('createdat')->useCurrent();
            $this->fk($t, 'match_id', 'Match', 'match_id');
        });
    }

    public function down(): void
    {
        foreach (['Game_Result', 'Combat_Log', 'Chat_Message', 'Dice_Roll', 'Character_Skill', 'Character_Item', 'Game_Member',
            'Team', 'Map', 'Match', 'Game_Room', 'Skill', 'Item', 'Users', 'Character'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
