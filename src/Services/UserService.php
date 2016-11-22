<?php

namespace App\Services;

use App\RoleUsers;
use GrahamCampbell\Credentials\Models\User;

class UsersService
{
    /*
     * Get user roles.
     *
     * @param User $user
     *
     * @return array
     */
    public function getRoles(User $user)
    {
        $roles = RoleUsers::join('users', 'users.id', '=', 'role_users.user_id')
            ->join('roles', 'role.id', '=', 'role_users.role_id')
            ->where('users.id', '=', $user->id)
            ->get();

        $result = [];

        foreach ($roles as $key => $role) {
            $result[] = $role->name;
        }

        return $result;
    }
}