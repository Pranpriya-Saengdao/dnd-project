<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $table = 'Skill';

    protected $primaryKey = 'skill_id';

    protected $guarded = [];

    public $timestamps = false;
}
