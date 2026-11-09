<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\Middleware\AuthenticateSession;

/**
 * Laravel's AuthenticateSession, safe for this app's impersonation and for switching users.
 *
 * It signs a session out when the account's password hash no longer matches the hash the
 * session stored, which is how "change password, sign out my other devices" works: the
 * request that changes the password re-stores the hash for ITS session at the end
 * (AuthenticateSession::handle), so the current device carries on; every other device
 * (and a remember-me cookie minted before the change) is refused on its next request.
 *
 * Two cases must not be compared with a stored hash:
 *  - Impersonation (Nckg\Impersonate, a global middleware that calls Auth::onceUsingId) swaps
 *    the user for a request without touching the session's stored hash.
 *  - A session whose signed-in user CHANGED (a new sign-in on top of an old session, or a test
 *    acting as one user after another) holds the previous user's hash. We remember whose hash
 *    it is; when the user differs the stale hash is dropped and re-stamped for the new user.
 *    The same user with a stale hash is exactly the password-changed case and is signed out.
 */
class AuthenticateSessionUnlessImpersonating extends AuthenticateSession
{
    private const OWNER_KEY = 'password_hash_owner';

    public function handle($request, Closure $next)
    {
        if (! $request->hasSession() || $request->session()->has('impersonate')) {
            return $next($request);
        }

        $user = $request->user();

        if ($user) {
            $session = $request->session();
            $owner = $session->get(self::OWNER_KEY);

            if ($owner !== null && (string) $owner !== (string) $user->getAuthIdentifier()) {
                $session->forget('password_hash_'.$this->auth->getDefaultDriver());
            }

            $session->put(self::OWNER_KEY, $user->getAuthIdentifier());
        }

        return parent::handle($request, $next);
    }
}
