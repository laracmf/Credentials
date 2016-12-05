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

use GrahamCampbell\Binput\Facades\Binput;
use GrahamCampbell\Credentials\Facades\Credentials;
use GrahamCampbell\Credentials\Facades\UserRepository;
use GrahamCampbell\Throttle\Throttlers\ThrottlerInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

/**
 * This is the registration controller class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class RegistrationController extends AbstractController
{
    /**
     * Display the registration form.
     *
     * @return \Illuminate\View\View
     */
    public function getRegister()
    {
        return View::make('credentials::account.register');
    }

    /**
     * Attempt to register a new user.
     *
     * @return \Illuminate\Http\Response
     */
    public function postRegister()
    {
        if (!Config::get('credentials.regallowed')) {
            return Redirect::route('account.register');
        }

        $input = Binput::only(['first_name', 'last_name', 'email', 'password', 'password_confirmation']);

        $rules = [
            'password'   => 'required|max:255|min:6|confirmed',
            'email'      => 'required|email|unique',
            'first_name' => 'max:30|min:3',
            'last_name'  => 'max:30|min:3',
        ];

        $val = UserRepository::validate($input, $rules, true);

        if ($val->fails()) {
            return Redirect::route('account.register')->withInput()->withErrors($val->errors());
        }

        try {
            unset($input['password_confirmation']);

            $user = Credentials::register($input);

            $activationResponse = Credentials::getActivationRepository()->create($user);
            $code = $activationResponse ? $activationResponse->code : '';

            if (!Config::get('credentials.activation')) {
                $mail = [
                    'url'     => URL::to(Config::get('credentials.home', '/')),
                    'email'   => $user->email,
                    'subject' => Config::get('app.name').' - Welcome',
                ];

                Mail::queue('credentials::emails.welcome', $mail, function ($message) use ($mail) {
                    $message->to($mail['email'])->subject($mail['subject']);
                });

                Credentials::getActivationRepository()->complete($user, $code);

                //Set role for user
                $role = Credentials::getRoleRepository()->findByName('User');
                $role->users()->attach($user);

                return Redirect::to(Config::get('credentials.home', '/'))
                    ->with('success', 'Your account has been created successfully. You may now login.');
            }

            $mail = [
                'url'     => URL::to(Config::get('credentials.home', '/')),
                'link'    => URL::route('account.activate', ['id' => $user->id, 'code' => $code]),
                'email'   => $user->email,
                'subject' => Config::get('app.name').' - Welcome',
            ];

            Mail::queue('credentials::emails.welcome', $mail, function ($message) use ($mail) {
                $message->to($mail['email'])->subject($mail['subject']);
            });

            return Redirect::to(Config::get('credentials.home', '/'))
                ->with('success', 'Your account has been created. Check your email for the confirmation link.');
        } catch (\Exception $e) {
            return Redirect::route('account.register')->withInput()->withErrors($val->errors())
                ->with('error', 'That email address is taken.');
        }
    }
}