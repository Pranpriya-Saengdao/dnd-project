<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiceRoll extends Model
{
    protected $table = 'Dice_Roll';

    protected $primaryKey = 'dice_roll_id';

    protected $guarded = [];

    public $timestamps = false;
}
