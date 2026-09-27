<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = [
        'name', 'base_hp', 'attack', 'wisdom', 'dexterity', 'intelligence',
        'charisma', 'attack_range', 'skill_range',
    ];

    public function gameMembers()
    {
        return $this->hasMany(GameMember::class);
    }
}
