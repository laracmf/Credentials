<?php

namespace GrahamCampbell\Credentials\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redirect;
use GrahamCampbell\Throttle\Facades\Throttle;

class ThrottleLoginMiddleware
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
        // check if we've reached the rate limit, but don't hit the throttle yet
        // we can hit the throttle later on in the if validation passes
        if (!Throttle::check($request, 10, 10)) {
            return Redirect::route('account.login')->withInput()
                ->with('error', 'You have made too many login requests. Please try again in 10 minutes.');
        }

        return $next($request);
    }
}