<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CombatLog extends Model
{
    protected $table = 'Combat_Log';

    protected $primaryKey = 'combat_id';

    protected $guarded = [];

    public $timestamps = true;

    const CREATED_AT = 'createdat';

    const UPDATED_AT = null;

    public function attacker()
    {
        return $this->belongsTo(GameMember::class, 'attacker_member_id', 'member_id');
    }

    public function target()
    {
        return $this->belongsTo(GameMember::class, 'target_member_id', 'member_id');
    }

    public function diceRoll()
    {
        return $this->belongsTo(DiceRoll::class, 'dice_roll_id', 'dice_roll_id');
    }
}
