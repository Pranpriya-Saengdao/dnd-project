<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiceRoll extends Model
{
    protected $fillable = ['game_match_id', 'game_member_id', 'purpose', 'result', 'rolled_at'];

    public function gameMatch()
    {
        return $this->belongsTo(GameMatch::class);
    }

    public function gameMember()
    {
        return $this->belongsTo(GameMember::class);
    }

    public function combatLog()
    {
        return $this->hasOne(CombatLog::class);
    }
}
