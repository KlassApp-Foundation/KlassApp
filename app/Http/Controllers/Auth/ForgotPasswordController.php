<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\PasswordResetCodeIssuer;
use App\Services\Auth\PasswordResetDeliveryException;
use App\Services\TeacherAccountInviteService;
use App\Support\PendingPassword;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
        if (!$user || PendingPassword::isUnset($user)) {
            if ($user && PendingPassword::isUnset($user) && (int) $user->usergroup_id === 5) {
                app(TeacherAccountInviteService::class)->inviteIfNeeded($user, null);
            }

            // Don't reveal whether the email exists — same message either way.
            // An account that has not set a password is sent its setup link, not a reset code.
            return redirect()->route('password.reset.code', ['email' => $request->email])
                ->with('status', 'If that email is registered, a reset code has been sent.');
        }

        try {
            app(PasswordResetCodeIssuer::class)->issue($user, $request->ip());
        } catch (PasswordResetDeliveryException $e) {
            throw ValidationException::withMessages([
                'email' => [$e->userMessage()],
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

        return view('auth.preview.reset-request');
    }
}
