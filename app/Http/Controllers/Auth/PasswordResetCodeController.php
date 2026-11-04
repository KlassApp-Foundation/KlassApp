<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Authentication;
use App\Models\User;
use App\Services\Auth\PasswordResetCodeIssuer;
use App\Services\Auth\PasswordResetDeliveryException;
use App\Services\TeacherAccountInviteService;
use App\Support\PendingPassword;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetCodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the code entry form.
     */
    public function showCodeForm(Request $request)
    {
        $email = $request->query('email', session('reset_email'));
        if (!$email) {
            return redirect()->route('password.email')
                ->withErrors(['email' => 'Please enter your email first.']);
        }

        return view('auth.preview.reset-code', ['email' => $email]);
    }

    /**
     * Verify the reset code and redirect to the password reset form.
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && PendingPassword::isUnset($user)) {
            if ((int) $user->usergroup_id === 5) {
                app(TeacherAccountInviteService::class)->inviteIfNeeded($user, null);
            }

            return back()->withErrors(['code' => 'Invalid or expired code. Please request a new one.']);
        }

        $auth = $user
            ? Authentication::where('user_id', $user->id)
                ->where('type', 'password_reset')
                ->where('status', 0)
                ->where('expires_on', '>=', Carbon::now())
                ->orderBy('id', 'desc')
                ->first()
            : null;

        // Same response when the email is unknown and when the code is wrong,
        // expired, or already used. A distinct message would show that the
        // address has an account.
        if (!$user || !$auth || $auth->token != $request->code) {
            return back()->withErrors(['code' => 'Invalid or expired code. Please request a new one.']);
        }

        // Mark code as used
        $auth->status = 1;
        $auth->save();

        // Generate a password reset token for the user and redirect to reset form
        $token = Password::getRepository()->create($user);

        return redirect()->route('password.reset.form', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }

    /**
     * Resend the reset code.
     */
    public function resendCode(Request $request)
    {
        $email = $request->input('email');
        if (!$email) {
            return redirect()->route('password.email')
                ->withErrors(['email' => 'Please enter your email first.']);
        }

        $user = User::where('email', $email)->first();
        if ($user && PendingPassword::isUnset($user)) {
            if ((int) $user->usergroup_id === 5) {
                app(TeacherAccountInviteService::class)->inviteIfNeeded($user, null);
            }

            return redirect()->route('password.reset.code', ['email' => $email])
                ->with('status', 'A new code has been sent to your email.');
        }
        if ($user) {
            try {
                app(PasswordResetCodeIssuer::class)->issue($user, $request->ip());
            } catch (PasswordResetDeliveryException $e) {
                return back()->withErrors(['email' => $e->userMessage()]);
            }
        }

        return redirect()->route('password.reset.code', ['email' => $email])
            ->with('status', 'A new code has been sent to your email.');
    }
}
