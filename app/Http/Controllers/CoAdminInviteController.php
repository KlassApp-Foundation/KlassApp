<?php

namespace App\Http\Controllers;

use App\Models\CoAdminInvite;
use App\Services\CoAdminInviteLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoAdminInviteController extends Controller
{
    /**
     * Show the password-set form for a valid invite token.
     * Expired/claimed/invalid tokens get a dedicated error page.
     */
    public function show(string $token): View
    {
        $result = CoAdminInviteLinkService::validateToken($token);

        if ($result === null) {
            return view('auth.invite-invalid', ['reason' => 'invalid']);
        }

        $invite = $result['invite'];
        $error = $result['error'];

        if ($error === 'expired') {
            return view('auth.invite-invalid', [
                'reason' => 'expired',
                'invite' => $invite,
            ]);
        }

        if ($error === 'claimed') {
            return view('auth.invite-invalid', [
                'reason' => 'claimed',
                'invite' => $invite,
            ]);
        }

        return view('auth.invite-set-password', [
            'token'      => $token,
            'invite'     => $invite,
            'school'     => $result['school'],
            'className'  => null,
            'claimRoute' => 'coadmin.invite.claim',
            'roleLabel'  => 'as a Co-Admin',
        ]);
    }

    /**
     * Process the password-set form submission.
     */
    public function claim(Request $request, string $token): RedirectResponse
    {
        $result = CoAdminInviteLinkService::validateToken($token);

        if ($result === null) {
            return redirect()->route('coadmin.invite.form', $token)
                ->with('error', 'This invite link is invalid.');
        }

        if ($result['error'] !== null) {
            $reason = $result['error'] === 'expired'
                ? 'This invite link has expired.'
                : 'This invite has already been used.';
            return redirect()->route('login')
                ->with('error', $reason);
        }

        $invite = $result['invite'];

        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
            ],
        ], [
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must include at least one lowercase letter, one uppercase letter, and one number.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        $claim = CoAdminInviteLinkService::claim($invite, $validated['password']);

        if (! ($claim['success'] ?? false)) {
            return redirect()->route('coadmin.invite.form', $token)
                ->with('error', $claim['message'] ?? 'Could not create your account.');
        }

        return redirect()->route('login')
            ->with('status', 'Account created! You can now log in with your email and the password you just set.');
    }
}
