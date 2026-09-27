<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMember extends Model
{
    protected $fillable = [
        'game_match_id', 'user_id', 'character_id', 'team', 'current_hp',
        'position_x', 'position_y', 'turn_order', 'skill_uses_remaining', 'is_alive',
    ];

    public function gameMatch()
    {
        return $this->belongsTo(GameMatch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function diceRolls()
    {
        return $this->hasMany(DiceRoll::class);
    }
}
