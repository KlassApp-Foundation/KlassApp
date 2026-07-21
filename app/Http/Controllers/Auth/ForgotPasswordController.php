<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordCodeMail;
use App\Models\Authentication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class ForgotPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Send a 6-digit reset code to the user's email.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            // Don't reveal whether the email exists — same message either way
            return redirect()->route('password.reset.code', ['email' => $request->email])
                ->with('status', 'If that email is registered, a reset code has been sent.');
        }

        // Invalidate any existing unused codes for this user
        Authentication::where('user_id', $user->id)
            ->where('type', 'password_reset')
            ->where('status', 0)
            ->update(['status' => 2]);

        // Generate a 6-digit code
        $code = random_int(100000, 999999);
        $expiry = Carbon::now()->addMinutes(5);

        try {
            Authentication::create([
                'user_id'    => $user->id,
                'type'       => 'password_reset',
                'token'      => $code,
                'ip_address' => $request->ip(),
                'expires_on' => $expiry,
                'status'     => 0,
            ]);

            Mail::to($user->email)->send(new ResetPasswordCodeMail($user, $code));
        } catch (Throwable $e) {
            Log::warning('Password reset code generation failed', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['We could not send a reset code right now. Please try again shortly.'],
            ]);
        }

        return redirect()->route('password.reset.code', ['email' => $user->email])
            ->with('status', 'A 6-digit reset code has been sent to your email.');
    }

    /**
     * Show the form to request a reset code.
     * Also handles old-format links with ?token= query param.
     */
    public function showLinkRequestForm(Request $request)
    {
        // Old-format reset links use ?token=XXX — redirect to the proper reset form
        if ($token = $request->query('token')) {
            return redirect()->route('password.reset', [
                'token' => $token,
                'email' => $request->query('email'),
            ]);
        }

        return view('auth.passwords.email');
    }
}
