<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $table = 'Team';

    protected $primaryKey = 'team_id';

    protected $guarded = [];

    public $timestamps = false;
}
