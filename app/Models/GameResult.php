<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameResult extends Model
{
    protected $table = 'Game_Result';

    protected $primaryKey = 'result_id';

    protected $guarded = [];

    public $timestamps = true;

    const CREATED_AT = 'createdat';

    const UPDATED_AT = null;

    public function member()
    {
        return $this->belongsTo(GameMember::class, 'member_id', 'member_id');
    }
}
