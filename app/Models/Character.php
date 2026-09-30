<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Character extends Model
{
    use SoftDeletes;

    protected $table = 'Character';

    protected $primaryKey = 'character_id';

    protected $guarded = [];

    public $timestamps = true;

    const CREATED_AT = 'createdat';

    const UPDATED_AT = 'updatedat';

    const DELETED_AT = 'deleteat';

    public function items()
    {
        return $this->hasMany(CharacterItem::class, 'character_id', 'character_id');
    }
}
