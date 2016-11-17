<?php

namespace GrahamCampbell\Credentials\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redirect;
use GrahamCampbell\Throttle\Facades\Throttle;

class ThrottleResendMiddleware
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
        if (!Throttle::check($request, 5, 30)) {
            return Redirect::route('account.resend')->withInput()
                ->with('error', 'You have been suspended from resending activation emails. Please contact support.');
        }

        return $next($request);
    }
}