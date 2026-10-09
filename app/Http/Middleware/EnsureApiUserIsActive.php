<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Blocks API tokens of banned users, and of unverified users when email
 * verification is switched on.
 */
class EnsureApiUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('api');
        if ($user) {
            if ($user->isBanned()) {
                return responseError(403, __('Your account has been blocked'));
            }
            if (emailVerificationRequired() && is_null($user->email_verified_at)) {
                return responseError(403, __('Please verify your email address first'), ['verification_required' => true]);
            }
        }
        return $next($request);
    }
}
