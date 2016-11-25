<?php

/*
 * This file is part of Laravel Credentials.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GrahamCampbell\Credentials\Http\Controllers;

use Cartalyst\Sentinel\Roles\EloquentRole;
use GrahamCampbell\Credentials\Services\UsersService;
use Carbon\Carbon;
use Cartalyst\Sentinel\Checkpoints\ThrottlingException;
use GrahamCampbell\Binput\Facades\Binput;
use GrahamCampbell\Credentials\Facades\Credentials;
use GrahamCampbell\Credentials\Facades\UserRepository;
use GrahamCampbell\Credentials\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This is the user controller class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class UserController extends AbstractController
{
    /**
     * Users service instance.
     *
     * @var UsersService
     */
    public $usersService;

    /**
     * Create a new instance.
     */
    public function __construct(UsersService $usersService)
    {
        $this->usersService = $usersService;

        $this->setPermissions([
            'index'   => 'moderator|admin',
            'create'  => 'admin',
            'store'   => 'admin',
            'show'    => 'moderator|admin',
            'edit'    => 'admin',
            'update'  => 'admin',
            'suspend' => 'moderator|admin',
            'reset'   => 'admin',
            'resend'  => 'admin',
            'destroy' => 'admin',
        ]);

        parent::__construct();
    }

    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $users = Credentials::getUserRepository()->paginate();
        $links = UserRepository::links();

        if ($users) {
            $users = $users->filter(function ($user) {
                return !$user->inRole('admin') && !$user->deleted_at;
            });
        }

        return View::make('credentials::users.index', compact('users', 'links'));
    }

    /**
     * Show the form for creating a new user.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return View::make('credentials::users.create', [
            'roles' => EloquentRole::all()
        ]);
    }

    /**
     * Store a new user.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $password = Str::random();

        $input = Binput::only(['first_name', 'last_name', 'email']);
        $rules = UserRepository::rules(array_keys($input));
        $val = UserRepository::validate($input, $rules, true);

        if ($val->fails()) {
            return Redirect::route('users.create')->withInput()->withErrors($val->errors());
        }

        try {
            $user = new User($input);
            $user->password = $user->hash($password);
            $user->save();

            $requestRolesKeys = $this->usersService->getRolesKeysFromRequest(Binput::all());
            $rolesIds = $this->usersService->parseRoles($requestRolesKeys);

            if ($rolesIds) {
                $this->usersService->saveUserRoles($user, $rolesIds);
            }

            $mail = [
                'url'      => URL::to(Config::get('credentials.home', '/')),
                'password' => $password,
                'email'    => $user->email,
                'subject'  => Config::get('app.name').' - New Account Information',
            ];

            Mail::queue('credentials::emails.newuser', $mail, function ($message) use ($mail) {
                $message->to($mail['email'])->subject($mail['subject']);
            });

            return Redirect::route('users.show', ['users' => $user->id])
                ->with('success', 'The user has been created successfully. Their password has been emailed to them.');
        } catch (\Exception $e) {
            return Redirect::route('users.create')->withInput()->withErrors($val->errors())
                ->with('error', 'That email address is taken.');
        }
    }

    /**
     * Show the specified user.
     *
     * @param int $id
     *
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $user = User::find($id);
        $this->checkUser($user);

        if ($activation = Credentials::getActivationRepository()->completed($user)) {
            $activated = html_ago(Carbon::createFromFormat('Y-m-d H:m:s', $activation->completed_at));
        } else {
            if (Credentials::hasAccess(
                    [
                        'user.create',
                        'user.delete',
                        'user.view',
                        'user.update'
                    ]) && Config::get('credentials.activation')) {
                $activated = 'No - <a href="#resend_user" data-toggle="modal" data-target="#resend_user">Resend Email</a>';
            } else {
                $activated = 'Not Activated';
            }
        }

        $roles = $this->usersService->getRoles($user);

        $result = [];

        foreach ($roles as $key => $role) {
            $result[] = $role->name;
        }

        if ($result) {
            $roles = implode(', ', $result);
        } else {
            $roles = 'No Roles Found';
        }

        return View::make('credentials::users.show', compact('user', 'roles', 'activated'));
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param int $id
     *
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $user = User::find($id);
        $this->checkUser($user);

        $roles = EloquentRole::all();
        $userRoles = ($this->usersService->getRoles($user))->pluck('id')->toArray();

        return View::make('credentials::users.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Update an existing user.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $requestRolesKeys = $this->usersService->getRolesKeysFromRequest(Binput::all());
        $rolesIds = $this->usersService->parseRoles($requestRolesKeys);

        $input = Binput::only(['first_name', 'last_name', 'email']);

        $val = UserRepository::validate($input, array_keys($input));
        if ($val->fails()) {
            return Redirect::route('users.edit', ['users' => $id])
                ->withInput()->withErrors($val->errors());
        }

        $user = User::find($id);
        $this->checkUser($user);

        $email = $user['email'];

        $user->update($input);

        $roles = $this->usersService->getRoles($user);

        $changed = !!array_diff($rolesIds, $roles->pluck('id')->toArray());
        $this->usersService->deleteUserRoles($user);

        if ($rolesIds) {
            $this->usersService->saveUserRoles($user, $rolesIds);
        }

        if ($email !== $input['email']) {
            $mail = [
                'old'     => $email,
                'new'     => $input['email'],
                'url'     => URL::to(Config::get('credentials.home', '/')),
                'subject' => Config::get('app.name').' - New Email Information',
            ];

            Mail::queue('credentials::emails.newemail', $mail, function ($message) use ($mail) {
                $message->to($mail['old'])->subject($mail['subject']);
            });

            Mail::queue('credentials::emails.newemail', $mail, function ($message) use ($mail) {
                $message->to($mail['new'])->subject($mail['subject']);
            });
        }

        if ($changed) {
            $mail = [
                'url'     => URL::to(Config::get('credentials.home', '/')),
                'email'   => $input['email'],
                'subject' => Config::get('app.name').' - Roles Changes',
            ];

            Mail::queue('credentials::emails.groups', $mail, function ($message) use ($mail) {
                $message->to($mail['email'])->subject($mail['subject']);
            });
        }

        return Redirect::route('users.show', ['users' => $user->id])
            ->with('success', 'The user has been updated successfully.');
    }

    /**
     * Reset the password of an existing user.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function reset($id)
    {
        $input = [
            'password' => Str::random(),
        ];

        $rules = [
            'password' => 'required|min:6',
        ];

        $val = UserRepository::validate($input, $rules, true);

        if ($val->fails()) {
            return Redirect::route('users.show', ['users' => $id])->withErrors($val->errors());
        }

        $user = User::find($id);
        $this->checkUser($user);

        $password = $input['password'];
        $input['password'] = $user->hash($password);

        $user->update($input);

        $mail = [
            'password' => $password,
            'email'    => $user->email,
            'subject'  => Config::get('app.name').' - New Password Information',
        ];

        Mail::queue('credentials::emails.password', $mail, function ($message) use ($mail) {
            $message->to($mail['email'])->subject($mail['subject']);
        });

        return Redirect::route('users.show', ['users' => $id])
            ->with('success', 'The user\'s password has been reset successfully, and has been emailed to them.');
    }

    /**
     * Resend the activation email of an existing user.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function resend($id)
    {
        $user = UserRepository::find($id);
        $this->checkUser($user);

        if ($activation = Credentials::getActivationRepository()->completed($user)) {
            return Redirect::route('account.resend')->withInput()
                ->with('error', 'That user is already activated.');
        }

        $code = $activation->code;

        $mail = [
            'url'     => URL::to(Config::get('credentials.home', '/')),
            'link'    => URL::route('account.activate', ['id' => $user->id, 'code' => $code]),
            'email'   => $user->email,
            'subject' => Config::get('app.name').' - Activation',
        ];

        Mail::queue('credentials::emails.resend', $mail, function ($message) use ($mail) {
            $message->to($mail['email'])->subject($mail['subject']);
        });

        return Redirect::route('users.show', ['users' => $id])
            ->with('success', 'The user\'s activation email has been sent successfully.');
    }

    /**
     * Delete an existing user.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        $this->checkUser($user);

        try {
            $this->usersService->deleteUserRoles($user);

            $user->delete();
        } catch (\Exception $e) {
            return Redirect::route('users.show', ['users' => $id])
                ->with('error', 'We were unable to delete the account.');
        }

        $mail = [
            'url'     => URL::to(Config::get('credentials.home', '/')),
            'email'   => $user->email,
            'subject' => Config::get('app.name').' - Account Deleted Notification',
        ];

        Mail::queue('credentials::emails.admindeleted', $mail, function ($message) use ($mail) {
            $message->to($mail['email'])->subject($mail['subject']);
        });

        return Redirect::route('users.index')
            ->with('success', 'The user has been deleted successfully.');
    }

    /**
     * Check the user model.
     *
     * @param mixed $user
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return void
     */
    protected function checkUser($user)
    {
        if (!$user) {
            throw new NotFoundHttpException('User Not Found');
        }
    }
}