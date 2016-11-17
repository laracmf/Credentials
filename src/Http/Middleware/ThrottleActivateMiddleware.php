<?php

namespace GrahamCampbell\Credentials\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redirect;
use GrahamCampbell\Throttle\Facades\Throttle;

class ThrottleActivateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!Throttle::attempt($request, 10, 10)) {
            return Redirect::route('account.login')->withInput()
                ->with('error', 'You have made too many activation requests. Please try again in 10 minutes.');
        }

        return $next($request);
    }
}