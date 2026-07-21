<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Authentication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

        return view('auth.passwords.code', ['email' => $email]);
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
        if (!$user) {
            return back()->withErrors(['email' => 'No account found with that email.']);
        }

        $auth = Authentication::where('user_id', $user->id)
            ->where('type', 'password_reset')
            ->where('status', 0)
            ->where('expires_on', '>=', Carbon::now())
            ->orderBy('id', 'desc')
            ->first();

        if (!$auth || $auth->token != $request->code) {
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
        $email = $request->query('email');
        if (!$email) {
            return redirect()->route('password.email')
                ->withErrors(['email' => 'Please enter your email first.']);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('password.email')
                ->withErrors(['email' => 'No account found with that email.']);
        }

        // Invalidate old codes
        Authentication::where('user_id', $user->id)
            ->where('type', 'password_reset')
            ->where('status', 0)
            ->update(['status' => 2]); // cancelled

        // Generate new code
        $code = random_int(100000, 999999);
        $expiry = Carbon::now()->addMinutes(5);

        Authentication::create([
            'user_id'    => $user->id,
            'type'       => 'password_reset',
            'token'      => $code,
            'ip_address' => $request->ip(),
            'expires_on' => $expiry,
            'status'     => 0,
        ]);

        try {
            \Mail::to($user->email)->send(new \App\Mail\ResetPasswordCodeMail($user, $code));
        } catch (\Throwable $e) {
            Log::warning('Password reset code email failed', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['email' => 'Could not send the code. Please try again.']);
        }

        return redirect()->route('password.reset.code', ['email' => $email])
            ->with('status', 'A new code has been sent to your email.');
    }
}
