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

use Carbon\Carbon;
use Cartalyst\Sentinel\Users\EloquentUser;
use GrahamCampbell\Credentials\Facades\Credentials;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use McCool\LaravelAutoPresenter\HasPresenter;
use Cartalyst\Sentinel\Users\IlluminateUserRepository;
use Psy\Exception\RuntimeException;

/**
 * This is the user model class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class User extends EloquentUser implements HasPresenter
{
    use BaseModelTrait, SoftDeletes;

    /**
     * The model name.
     *
     * @var string
     */
    public static $name = 'user';

    /**
     * The columns to select when displaying an index.
     *
     * @var array
     */
    public static $index = ['id', 'email', 'first_name', 'last_name'];

    /**
     * The max users per page when displaying a paginated index.
     *
     * @var int
     */
    public static $paginate = 20;

    /**
     * The columns to order by when displaying an index.
     *
     * @var string
     */
    public static $order = 'email';

    /**
     * The direction to order by when displaying an index.
     *
     * @var string
     */
    public static $sort = 'asc';

    /**
     * The user validation rules.
     *
     * @var array
     */
    public static $rules = [
        'first_name'            => 'required|min:2|max:32',
        'last_name'             => 'required|min:2|max:32',
        'email'                 => 'required|min:4|max:32|email',
        'password'              => 'required|min:6|confirmed',
        'password_confirmation' => 'required',
        'activated'             => 'required',
        'activated_at'          => 'required',
    ];

    /**
     * Access caches.
     *
     * @var array
     */
    protected $access = [];

    /**
     * Get the recent action history for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function revisions()
    {
        return $this->hasMany(Config::get('credentials.revision'));
    }

    /**
     * Get the user's action history.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function actions()
    {
        return $this->revisions()
            ->where(function ($q) {
                $q->where('revisionable_type', '<>', get_class($this))
                    ->where('user_id', '=', $this->id);
            })
            ->orWhere(function ($q) {
                $q->where('revisionable_type', '=', get_class($this))
                    ->where('revisionable_id', '<>', $this->id)
                    ->where('user_id', '=', $this->id);
            })
            ->orderBy('id', 'desc')->take(20);
    }

    /**
     * Get the user's security history.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function security()
    {
        return $this->revisionHistory()->orderBy('id', 'desc')->take(20);
    }

    /**
     * Get the presenter class.
     *
     * @return string
     */
    public function getPresenterClass()
    {
        return 'GrahamCampbell\Credentials\Presenters\UserPresenter';
    }

    /**
     * Activated at accessor.
     *
     * @param string $value
     *
     * @return \Carbon\Carbon|false
     */
    public function getActivatedAtAccessor($value)
    {
        if ($value) {
            return new Carbon($value);
        }

        if ($this->getAttribute('activated')) {
            return $this->getAttribute('created_at');
        }

        return false;
    }

    /**
     * Check a user's access.
     *
     * @param string|string[] $permissions
     * @param bool            $all
     *
     * @return bool
     */
    public function hasAccess($permissions, $all = true)
    {
        $key = sha1(json_encode($permissions).json_encode($all));

        if (!array_key_exists($key, $this->access)) {
            $this->access[$key] = parent::hasAccess($permissions, $all);
        }

        return $this->access[$key];
    }

    /**
     * Hash string.
     *
     * @param  string  $string
     * @return string
     * @throws RuntimeException
     */
    public function hash($string)
    {
        if ( ! Credentials::getUserRepository()->getHasher())
        {
            throw new \RuntimeException("A hasher has not been provided for the user.");
        }

        return Credentials::getUserRepository()->getHasher()->hash($string);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany('Cartalyst\Sentinel\Roles\EloquentRole', 'role_users', 'user_id', 'role_id');
    }
}
