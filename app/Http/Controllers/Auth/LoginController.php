<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Auth;

use App\Helpers\AuthRedirectHelper;
use App\Http\Controllers\Controller;
//use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Models\User;
use App\Traits\AuthenticatesUsers;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;

class LoginController extends Controller implements ShouldQueue
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Fallback when redirectTo() is not used. Login calls redirectTo().
     *
     * @var string
     */
    protected $redirectTo = '/admin/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        // Already signed in? Send the person to their own dashboard. The guest
        // middleware normally does this, but it is configuration: if it is ever
        // bypassed the login page must still never fall through to a 404.
        if ($user = auth()->user()) {
            return redirect(\App\Helpers\AuthRedirectHelper::dashboardPathForUser($user));
        }

        return response()
            ->view('auth.preview.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    }

    private function intendedBelongsToUser(string $intended, $user): bool
    {
        $path = parse_url($intended, PHP_URL_PATH);
        $path = '/'.ltrim(is_string($path) && $path !== '' ? $path : $intended, '/');
        $home = \App\Helpers\AuthRedirectHelper::dashboardPathForUser($user);
        $area = '/'.explode('/', trim($home, '/'))[0];

        return $path === $home || str_starts_with($path, $area.'/');
    }

    /**
     * Role home after login. An unfinished school admin goes to setup.
     */
    public function redirectTo()
    {
        $user = auth()->user();

        return AuthRedirectHelper::dashboardPathForUser($user instanceof User ? $user : null);
    }

    /**
     * After sign-in:
     *
     * 1. Unfinished setup wins over a stored intended URL (an unfinished school
     *    admin goes to setup).
     * 2. redirect()->intended() replays whatever URL the *previous* visitor tried
     *    to open, such as a parent's page or an admin screen. A teacher signing in
     *    must not inherit that. Keep the intended URL only when it is inside the
     *    area the user just signed into; otherwise send them to their own dashboard.
     *
     * Merged from #1256 and #1264 in the integration rehearsal.
     *
     * @param  mixed  $user
     * @return \Illuminate\Http\RedirectResponse|null
     */
    protected function authenticated(Request $request, $user)
    {
        if (! $user instanceof User) {
            return null;
        }

        $destination = AuthRedirectHelper::dashboardPathForUser($user);
        $home = AuthRedirectHelper::dashboardPathForUsergroup((int) $user->usergroup_id);

        if ($destination !== $home) {
            return redirect()->to($destination);
        }

        $intended = $request->session()->get('url.intended');

        if (! is_string($intended) || $intended === '') {
            return null;
        }

        if ($this->intendedBelongsToUser($intended, $user)) {
            return null;
        }

        $request->session()->forget('url.intended');

        return redirect(AuthRedirectHelper::dashboardPathForUser($user));
    }
}
