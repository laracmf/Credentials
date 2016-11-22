<?php

/*
 * This file is part of Laravel Credentials.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GrahamCampbell\Credentials\Models;

use Cartalyst\Sentinel\Roles\EloquentRole;

/**
 * This is the user model class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class Role extends EloquentRole
{
    public $fillable = [
        'slug',
        'name',
        'permissions'
    ];
}
