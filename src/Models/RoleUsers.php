<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoleUsers extends Model
{
    protected $table = 'role_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'role_id'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
    ];
}