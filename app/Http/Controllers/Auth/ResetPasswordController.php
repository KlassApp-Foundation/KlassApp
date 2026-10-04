<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationGate;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Set by resetPassword() when the user's email is not verified, so the
     * response parks on the code-entry screen instead of the base trait's
     * autologin.
     */
    private bool $pendingVerificationAfterReset = false;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Display the password reset view for the given token.
     *
     * Accepts the token from either the route parameter (path-style)
     * or query string (default Laravel notification format).
     */
    public function showResetForm(Request $request)
    {
        $token = $request->route()->parameter('token')
              ?? $request->query('token');

        return view('auth.preview.reset-newpw')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * Finish the reset, then only sign the user in when their email is
     * verified. Otherwise park them on the code-entry screen with a fresh
     * code — a reset e-mail matching the account's (unverified) address is
     * the same proof level as the password login the gate already covers.
     */
    protected function resetPassword($user, $password)
    {
        $this->setUserPassword($user, $password);

        $user->setRememberToken(Str::random(60));

        $user->save();

        event(new PasswordReset($user));

        if (EmailVerificationGate::needsVerification($user)) {
            $this->pendingVerificationAfterReset = true;

            app(EmailVerificationGate::class)->sendToCodeEntry(request(), $user);

            return;
        }

        $this->guard()->login($user);
    }

    protected function sendResetResponse(Request $request, $response)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => trans($response)], 200);
        }

        if ($this->pendingVerificationAfterReset) {
            return redirect()->route('register.verify')
                ->with('status', trans($response));
        }

        return redirect($this->redirectPath())
            ->with('status', trans($response));
    }
}
