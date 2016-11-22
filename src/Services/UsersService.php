<?php

namespace GrahamCampbell\Credentials\Services;

use GrahamCampbell\Credentials\Models\RoleUsers;
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
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->where('users.id', '=', $user->id)
            ->get();

        $result = [];

        foreach ($roles as $key => $role) {
            $result[] = $role->name;
        }

        return $result;
    }

    /**
     * Remove user roles
     *
     * @param User $user
     */
    public function deleteUserInterests(User $user)
    {
        $user->roles()->detach();
    }

    /**
     * Add user roles.
     *
     * @param User $user
     * @param array $data
     */
    public function saveUserInterests(User $user, $data)
    {
        $user->roles()->sync($data);
    }
}