<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailVerificationCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationCodeController extends Controller
{
    public function __construct(private readonly EmailVerificationCodeService $codes)
    {
        // The status poll must answer (not redirect) even if this browser just got signed in.
        $this->middleware('guest')->except('status');
    }

    public function show(Request $request)
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('register');
        }

        return view('auth.preview.verify-code', [
            'email' => $user->email,
            'minutes' => EmailVerificationCodeService::MINUTES_VALID,
            'expiresAt' => optional($this->codes->outstandingExpiry($user))->timestamp ?? now()->timestamp,
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'code.regex' => 'Enter the 6-digit code we emailed you.',
        ]);

        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('register')->withErrors([
                'email' => 'Your registration session expired. Please sign up again.',
            ]);
        }

        // Already confirmed by the emailed link on another device: this is the sign-up
        // browser, so just carry on instead of reporting the (now used-up) code as expired.
        if ((int) $user->email_verified === 1) {
            return $this->continueIntoOnboarding($request, $user);
        }

        $result = $this->codes->verify($user, $request->string('code')->toString());

        if ($result !== EmailVerificationCodeService::OK) {
            return back()->withErrors(['code' => $this->failureMessage($result, $user)]);
        }

        $this->codes->markVerified($user);

        return $this->continueIntoOnboarding($request, $user);
    }

    /**
     * Polled by the original sign-up tab (every 5s + on visibilitychange). Answers for the
     * pending signup session only; a browser without one always gets false.
     */
    public function status(Request $request)
    {
        $user = $this->pendingUser($request);

        return response()
            ->json(['confirmed' => $user !== null && (int) $user->email_verified === 1])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * The sign-up tab moves on after the email was confirmed on another device.
     * Only this browser's own pending signup session can be continued.
     */
    public function continueSignup(Request $request)
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('register');
        }

        if ((int) $user->email_verified !== 1) {
            return redirect()->route('register.verify');
        }

        return $this->continueIntoOnboarding($request, $user);
    }

    private function continueIntoOnboarding(Request $request, User $user)
    {
        $request->session()->forget('pending_verification_user_id');
        Auth::login($user);

        return redirect('/admin/dashboard')
            ->with('open_toshi_onboarding', true)
            ->with('successmessage', "Email confirmed. Welcome to KlassApp! Let's set up your school.");
    }

    public function resend(Request $request)
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('register')->withErrors([
                'email' => 'Your registration session expired. Please sign up again.',
            ]);
        }

        $this->codes->issue($user, $request->ip());

        return redirect()->route('register.verify')
            ->with('status', 'A new 6-digit code is on its way to '.$user->email.'.');
    }

    private function pendingUser(Request $request): ?User
    {
        $id = (int) $request->session()->get('pending_verification_user_id', 0);

        if ($id <= 0) {
            return null;
        }

        return User::find($id);
    }

    private function failureMessage(string $result, User $user): string
    {
        return match ($result) {
            EmailVerificationCodeService::LOCKED => 'Too many incorrect attempts. Wait a few minutes, or request a new code.',
            EmailVerificationCodeService::EXPIRED => 'That code has expired. Please request a new one.',
            default => sprintf(
                'That code is incorrect. %d attempt(s) left before this code locks.',
                $this->codes->attemptsRemaining($user)
            ),
        };
    }
}
