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
        return  RoleUsers::join('users', 'users.id', '=', 'role_users.user_id')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->where('users.id', '=', $user->id)
            ->get();
    }

    /**
     * Remove user roles
     *
     * @param User $user
     */
    public function deleteUserRoles(User $user)
    {
        $user->roles()->detach();
    }

    /**
     * Add user roles.
     *
     * @param User $user
     * @param array $data
     */
    public function saveUserRoles(User $user, $data)
    {
        $user->roles()->sync($data);
    }

    /**
     * Parse roles values from request.
     *
     * @param array $roles
     *
     * @return array
     */
    public function parseRoles($roles)
    {
        return array_map(function ($role) {
            return (int)str_replace('role_', '', $role);
        }, $roles);
    }

    /**
     * Get roles keys from request.
     *
     * @param array $request
     *
     * @return array
     */
    public function getRolesKeysFromRequest($request)
    {
        return array_keys(array_filter($request, function ($key) {
            // check that str 'role_' starts from 0 position
            return strpos($key, 'role_') === 0;
        }, ARRAY_FILTER_USE_KEY));
    }
}