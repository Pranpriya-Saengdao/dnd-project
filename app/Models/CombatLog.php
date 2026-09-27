<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CombatLog extends Model
{
    protected $fillable = [
        'game_match_id', 'attacker_member_id', 'target_member_id', 'dice_roll_id',
        'attack_type', 'damage', 'round_number',
    ];

    public function gameMatch()
    {
        return $this->belongsTo(GameMatch::class);
    }

    public function attacker()
    {
        return $this->belongsTo(GameMember::class, 'attacker_member_id');
    }

    public function target()
    {
        return $this->belongsTo(GameMember::class, 'target_member_id');
    }

    public function diceRoll()
    {
        return $this->belongsTo(DiceRoll::class);
    }
}
