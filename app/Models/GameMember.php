<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMember extends Model
{
    protected $table = 'Game_Member';

    protected $primaryKey = 'member_id';

    protected $guarded = [];

    public $timestamps = true;

    const CREATED_AT = 'createdat';

    const UPDATED_AT = 'updatedat';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id')->withTrashed();
    }

    public function character()
    {
        return $this->belongsTo(Character::class, 'character_id', 'character_id')->withTrashed();
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function gameMatch()
    {
        return $this->belongsTo(GameMatch::class, 'match_id', 'match_id');
    }
}
