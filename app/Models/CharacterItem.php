<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CharacterItem extends Model
{
    use SoftDeletes;

    protected $table = 'Character_Item';

    protected $primaryKey = 'character_item_id';

    protected $guarded = [];

    public $timestamps = true;

    const CREATED_AT = 'createdat';

    const UPDATED_AT = 'updatedat';

    const DELETED_AT = 'deletedat';

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }
}
