<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
//use Illuminate\Foundation\Auth\AuthenticatesUsers;
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
     * Where to redirect users after login.
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

    /**
     * Where to redirect users after login.
     * Customize based on user role.
     */
    public function redirectTo()
    {
        return \App\Helpers\AuthRedirectHelper::dashboardPathForUser(auth()->user());
    }
}

