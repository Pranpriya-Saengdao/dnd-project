<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $table = 'Chat_Message';

    protected $primaryKey = 'message_id';

    protected $guarded = [];

    public $timestamps = true;

    const CREATED_AT = 'createdat';

    const UPDATED_AT = null;

    public function member()
    {
        return $this->belongsTo(GameMember::class, 'member_id', 'member_id');
    }
}
