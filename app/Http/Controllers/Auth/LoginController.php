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
        return response()
            ->view('auth.preview.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
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
     * Unfinished setup wins over a stored intended URL. Finished school
     * admins and every other role keep the usual intended-or-home redirect.
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

        if ($destination === $home) {
            return null;
        }

        return redirect()->to($destination);
    }
}

