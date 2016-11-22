<?php

namespace GrahamCampbell\Credentials\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['slug', 'name', 'permissions'];
}