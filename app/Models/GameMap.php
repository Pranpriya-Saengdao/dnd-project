<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameMap extends Model
{
    use SoftDeletes;

    protected $table = 'Map';

    protected $primaryKey = 'map_id';

    protected $guarded = [];

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updatedat';

    const DELETED_AT = 'deleteat';
}
