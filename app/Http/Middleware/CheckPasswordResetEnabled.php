<?php

namespace BookStack\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Blocks the password reset flow unless config('auth.password_reset_enabled') says otherwise.
 *
 * Doole keeps it off. Professionals reach docs.doole.io through the backoffice SSO, and
 * SsoController creates their accounts with a random password nobody holds, so a self-service
 * reset would be the one way for them to get a direct login: a session with no embed scope, free
 * to browse the whole instance. Hiding the link on the login form is not enough, since these
 * paths can be requested directly.
 */
class CheckPasswordResetEnabled
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!config('auth.password_reset_enabled')) {
            abort(404);
        }

        return $next($request);
    }
}
