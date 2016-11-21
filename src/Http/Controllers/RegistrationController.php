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
use Webpatser\Uuid\Uuid;

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

        $val = UserRepository::validate($input, array_keys($input));
        if ($val->fails()) {
            return Redirect::route('account.register')->withInput()->withErrors($val->errors());
        }

        try {
            unset($input['password_confirmation']);

            $user = Credentials::register($input);

            if (!Config::get('credentials.activation')) {
                $mail = [
                    'url'     => URL::to(Config::get('credentials.home', '/')),
                    'email'   => $user->getLogin(),
                    'subject' => Config::get('app.name').' - Welcome',
                ];

                Mail::queue('credentials::emails.welcome', $mail, function ($message) use ($mail) {
                    $message->to($mail['email'])->subject($mail['subject']);
                });

                Credentials::getActivationRepository()->create($user);

                //Set role for user
                $role = Credentials::getRoleRepository()->findByName('User');
                $role->users()->attach($user);

                return Redirect::to(Config::get('credentials.home', '/'))
                    ->with('success', 'Your account has been created successfully. You may now login.');
            }

            $user->confirm_token = Uuid::generate(4);
            $user->save();

            $mail = [
                'url'     => route('register.complete', ['confirm_token' =>  $user->confirm_token]),
                'email'   => $user->getLogin(),
                'subject' => 'Complete your registration'
            ];

            Mail::queue('emails.completeRegistration', $mail, function ($message) use ($mail) {
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
