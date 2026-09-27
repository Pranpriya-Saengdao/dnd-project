<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
    protected $fillable = [
        'map_key', 'status', 'current_round', 'current_turn_order',
        'has_moved_this_turn', 'has_positioned_this_turn', 'has_acted_this_turn', 'winner_team', 'started_at', 'ended_at',
    ];

    public function members()
    {
        return $this->hasMany(GameMember::class);
    }

    public function diceRolls()
    {
        return $this->hasMany(DiceRoll::class);
    }

    public function combatLogs()
    {
        return $this->hasMany(CombatLog::class);
    }
}
