<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'Users';

    protected $primaryKey = 'user_id';

    protected $guarded = [];

    protected $hidden = ['password'];

    public $timestamps = true;

    const CREATED_AT = 'createdat';

    const UPDATED_AT = 'updatedat';

    const DELETED_AT = 'deleteat';

    public function character()
    {
        return $this->belongsTo(Character::class, 'character_character_id', 'character_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function getRememberTokenName()
    {
        return '';
    }
}
