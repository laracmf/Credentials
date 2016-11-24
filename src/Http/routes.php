<?php

/*
 * This file is part of Laravel Credentials.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

// send users to the profile page
Route::get('account', ['as' => 'account', function () {
    Session::flash('', ''); // work around laravel bug if there is no session yet
    Session::reflash();

    return Redirect::route('account.profile');
}]);

// account routes
Route::get('account/history', ['as' => 'account.history', 'uses' => 'AccountController@getHistory']);
Route::get('account/profile', ['as' => 'account.profile', 'uses' => 'AccountController@getProfile']);
Route::delete('account/profile', ['as' => 'account.profile.delete', 'uses' => 'AccountController@deleteProfile']);
Route::patch('account/details', ['as' => 'account.details.patch', 'uses' => 'AccountController@patchDetails']);
Route::patch('account/password', ['as' => 'account.password.patch', 'uses' => 'AccountController@patchPassword']);

// registration routes
if (Config::get('credentials.regallowed')) {
    Route::get('account/register', ['as' => 'account.register', 'uses' => 'RegistrationController@getRegister']);
    Route::post('account/register', ['as' => 'account.register.post', 'uses' => 'RegistrationController@postRegister']);
}

// activation routes
if (Config::get('credentials.activation')) {
    Route::get('account/activate/{id}/{code}', ['as' => 'account.activate', 'uses' => 'ActivationController@getActivate']);
    Route::get('account/resend', ['as' => 'account.resend', 'uses' => 'ActivationController@getResend']);
    Route::post('account/resend', ['as' => 'account.resend.post', 'uses' => 'ActivationController@postResend']);
}

// reset routes
Route::get('account/reset', ['as' => 'account.reset', 'uses' => 'ResetController@getReset']);
Route::post('account/reset', ['as' => 'account.reset.post', 'uses' => 'ResetController@postReset']);
Route::get('account/password/{id}/{code}', ['as' => 'account.password', 'uses' => 'ResetController@getPassword']);

// login routes
Route::get('account/login', ['as' => 'account.login', 'uses' => 'LoginController@getLogin']);
Route::post('account/login', ['as' => 'account.login.post', 'uses' => 'LoginController@postLogin']);
Route::get('account/logout', ['as' => 'account.logout', 'uses' => 'LoginController@getLogout']);

// user routes
Route::resource('users', 'UserController');
Route::post('users/{users}/suspend', ['as' => 'users.suspend', 'uses' => 'UserController@suspend']);
Route::post('users/{users}/reset', ['as' => 'users.reset', 'uses' => 'UserController@reset']);
if (Config::get('credentials.activation')) {
    Route::post('users/{users}/resend', ['as' => 'users.resend', 'uses' => 'UserController@resend']);
}
