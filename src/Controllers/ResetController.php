<?php

/*
 * This file is part of Laravel Credentials by Graham Campbell.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at http://bit.ly/UWsjkb.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace GrahamCampbell\Credentials\Controllers;

use Cartalyst\Sentry\Users\UserNotFoundException;
use GrahamCampbell\Binput\Facades\Binput;
use GrahamCampbell\Credentials\Facades\Credentials;
use GrahamCampbell\Credentials\Facades\UserProvider;
use GrahamCampbell\Throttle\Throttlers\ThrottlerInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * This is the reset controller class.
 *
 * @author    Graham Campbell <graham@mineuk.com>
 * @copyright 2013-2014 Graham Campbell
 * @license   <https://github.com/GrahamCampbell/Laravel-Credentials/blob/master/LICENSE.md> Apache 2.0
 */
class ResetController extends AbstractController
{
    /**
     * The throttler instance.
     *
     * @var \GrahamCampbell\Throttle\Throttlers\ThrottlerInterface
     */
    protected $throttler;

    /**
     * Create a new instance.
     *
     * @param \GrahamCampbell\Throttle\Throttlers\ThrottlerInterface $throttler
     *
     * @return void
     */
    public function __construct(ThrottlerInterface $throttler)
    {
        $this->throttler = $throttler;

        $this->beforeFilter('throttle.reset', array('only' => array('postReset')));

        parent::__construct();
    }

    /**
     * Display the password reset form.
     *
     * @return \Illuminate\View\View
     */
    public function getReset()
    {
        return View::make('graham-campbell/credentials::account.reset');
    }

    /**
     * Queue the sending of the password reset email.
     *
     * @return \Illuminate\Http\Response
     */
    public function postReset()
    {
        $input = Binput::only('email');

        $val = UserProvider::validate($input, array_keys($input));
        if ($val->fails()) {
            return Redirect::route('account.reset')->withInput()->withErrors($val->errors());
        }

        $this->throttler->hit();

        try {
            $user = Credentials::getUserProvider()->findByLogin($input['email']);

            $code = $user->getResetPasswordCode();

            $mail = array(
                'link' => URL::route('account.password', array('id' => $user->id, 'code' => $code)),
                'email' => $user->getLogin(),
                'subject' => Config::get('platform.name').' - Password Reset Confirmation',
            );

            Mail::queue('graham-campbell/credentials::emails.reset', $mail, function ($message) use ($mail) {
                $message->to($mail['email'])->subject($mail['subject']);
            });

            return Redirect::route('account.reset')
                ->with('success', 'Check your email for password reset information.');
        } catch (UserNotFoundException $e) {
            return Redirect::route('account.reset')
                ->with('error', 'That user does not exist.');
        }
    }

    /**
     * Reset the user's password.
     *
     * @param int    $id
     * @param string $code
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return \Illuminate\Http\Response
     */
    public function getPassword($id, $code)
    {
        if (!$id || !$code) {
            throw new BadRequestHttpException();
        }

        try {
            $user = Credentials::getUserProvider()->findById($id);

            $password = Str::random();

            if (!$user->attemptResetPassword($code, $password)) {
                return Redirect::to(Config::get('graham-campbell/core::home', '/'))
                    ->with('error', 'There was a problem resetting your password. Please contact support.');
            }

            $mail = array(
                'password' => $password,
                'email' => $user->getLogin(),
                'subject' => Config::get('platform.name').' - New Password Information',
            );

            Mail::queue('graham-campbell/credentials::emails.password', $mail, function ($message) use ($mail) {
                $message->to($mail['email'])->subject($mail['subject']);
            });

            return Redirect::to(Config::get('graham-campbell/core::home', '/'))
                ->with('success', 'Your password has been changed. Check your email for the new password.');
        } catch (UserNotFoundException $e) {
            return Redirect::to(Config::get('graham-campbell/core::home', '/'))
                ->with('error', 'There was a problem resetting your password. Please contact support.');
        }
    }
}
