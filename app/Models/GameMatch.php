<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
    protected $table = 'Match';

    protected $primaryKey = 'match_id';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime'];

    public function room()
    {
        return $this->belongsTo(GameRoom::class, 'room_id', 'room_id');
    }

    public function members()
    {
        return $this->hasMany(GameMember::class, 'match_id', 'match_id');
    }

    public function map()
    {
        return $this->hasOne(GameMap::class, 'match_match_id', 'match_id');
    }

    public function teams()
    {
        return $this->hasMany(Team::class, 'match_id', 'match_id');
    }
}
