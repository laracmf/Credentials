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

use Cartalyst\Sentinel\Checkpoints\NotActivatedException;
use GrahamCampbell\Binput\Facades\Binput;
use GrahamCampbell\Credentials\Facades\Credentials;
use GrahamCampbell\Credentials\Facades\UserRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GrahamCampbell\Credentials\Models\User;

/**
 * This is the resend controller class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class ActivationController extends AbstractController
{
    /**
     * Activate an existing user.
     *
     * @param int    $id
     * @param string $code
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return \Illuminate\Http\Response
     */
    public function getActivate($id, $code)
    {
        if (!$id || !$code) {
            throw new BadRequestHttpException();
        }

        try {
            $user = Credentials::getUserRepository()->findById($id);

            if (!Credentials::getActivationRepository()->complete($user, $code)) {
                return Redirect::to(Config::get('credentials.home', '/'))
                    ->with('error', 'There was a problem activating this account. Please contact support.');
            }

            //Set role for user
            $role = Credentials::getRoleRepository()->findByName('user');
            $role->users()->attach($user);

            return Redirect::route('account.login')
                ->with('success', 'Your account has been activated successfully. You may now login.');
        } catch (NotActivatedException $e) {
            return Redirect::route('account.login')
                ->with('warning', 'You have already activated this account. You may want to login.');
        } catch (\Exception $e) {
            return Redirect::to(Config::get('credentials.home', '/'))
                ->with('error', 'There was a problem activating this account. Please contact support.');
        }
    }

    /**
     * Display the resend form.
     *
     * @return \Illuminate\View\View
     */
    public function getResend()
    {
        return View::make('credentials::account.resend');
    }

    /**
     * Queue the sending of the activation email.
     *
     * @return \Illuminate\Http\Response
     */
    public function postResend()
    {
        $input = Binput::only('email');

        $rules = [
            'email' => 'email',
        ];

        $val = UserRepository::validate($input, $rules, true);

        if ($val->fails()) {
            return Redirect::route('account.resend')->withInput()->withErrors($val->errors());
        }

        try {
            $user = User::where('email', '=', $input['email'])->first();

            if (!$user) {
                return Redirect::route('account.resend')
                    ->with('error', 'That user does not exist.');
            }

            if (Credentials::getActivationRepository()->completed($user)) {
                return Redirect::route('account.resend')->withInput()
                    ->with('error', 'That user is already activated.');
            }

            $activation = Credentials::getActivationRepository()->exists($user) ?:
                Credentials::getActivationRepository()->create($user);

            $mail = [
                'url'     => URL::to(Config::get('credentials.home', '/')),
                'link'    => URL::route('account.activate', ['id' => $user->id, 'code' => $activation->code]),
                'email'   => $user->email,
                'subject' => Config::get('app.name').' - Activation',
            ];

            Mail::queue('credentials::emails.resend', $mail, function ($message) use ($mail) {
                $message->to($mail['email'])->subject($mail['subject']);
            });

            return Redirect::route('account.resend')
                ->with('success', 'Check your email for your new activation email.');
        } catch (\Exception $e) {
            return Redirect::route('account.resend')
                ->with('error', $e->getMessage());
        }
    }
}