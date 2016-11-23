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

use Cartalyst\Sentinel\Checkpoints\ThrottlingException;
use Cartalyst\Sentinel\Checkpoints\NotActivatedException;
use GrahamCampbell\Binput\Facades\Binput;
use GrahamCampbell\Credentials\Facades\Credentials;
use GrahamCampbell\Credentials\Facades\UserRepository;
use GrahamCampbell\Credentials\Http\Middleware\SentryThrottle;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use GrahamCampbell\Credentials\Models\User;
use Cartalyst\Sentinel\Laravel\Facades\Activation;

/**
 * This is the login controller class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class LoginController extends AbstractController
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->setPermissions([
            'getLogout' => 'user',
        ]);

        $this->middleware(SentryThrottle::class, ['only' => ['postLogin']]);

        parent::__construct();
    }

    /**
     * Display the login form.
     *
     * @return \Illuminate\View\View
     */
    public function getLogin()
    {
        return View::make('credentials::account.login');
    }

    /**
     * Attempt to login the specified user.
     *
     * @return \Illuminate\Http\Response
     */
    public function postLogin()
    {
        $remember = Binput::get('rememberMe');

        $input = Binput::only(['email', 'password']);

        $rules = UserRepository::rules(array_keys($input));
        $rules['password'] = 'required|min:6';

        $val = UserRepository::validate($input, $rules, true);

        if ($val->fails()) {
            return Redirect::route('account.login')->withInput()->withErrors($val->errors());
        }

        if (!User::where('email', '=', $input['email'])->first()) {
            return Redirect::route('account.login')
                ->withInput()
                ->with('error', 'User with email ' . $input['email'] . ' doesn\'t exists.');
        }

        try {
            if (!Credentials::authenticate($input, $remember)) {
                return Redirect::route('account.login')
                    ->withInput()
                    ->with('error', 'Bad credentials. Please check login and password.');
            }
        } catch (NotActivatedException $e) {
            if (Config::get('credentials::activation')) {
                return Redirect::route('account.login')->withInput()->withErrors($val->errors())
                    ->with('error', 'You have not yet activated this account.');
            } else {
                $user = User::where('email', '=', $input['email'])->first();
                Credentials::getActivationRepository()->create($user);

                //Set role for user
                $role = Credentials::getRoleRepository()->findByName('User');
                $role->users()->attach($user);

                return $this->postLogin();
            }
        } catch (ThrottlingException $e) {
            return Redirect::route('account.login')->withInput()->withErrors($val->errors())
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return Redirect::route('account.login')->withInput()->withErrors($val->errors())
                ->with('error', $e->getMessage());
        }

        return Redirect::intended(Config::get('credentials.home', '/'));
    }

    /**
     * Logout the specified user.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogout()
    {
        Credentials::logout();

        return Redirect::to(Config::get('credentials.home', '/'));
    }
}