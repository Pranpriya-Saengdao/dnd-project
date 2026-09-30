<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameRoom extends Model
{
    protected $table = 'Game_Room';

    protected $primaryKey = 'room_id';

    protected $guarded = [];

    public $timestamps = true;

    const CREATED_AT = 'createdat';

    const UPDATED_AT = 'updatedat';

    public function match()
    {
        return $this->hasOne(GameMatch::class, 'room_id', 'room_id');
    }
}
