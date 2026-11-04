<?php

namespace App\Http\Controllers;

use App\Services\StudentParentAccountInviteService;
use App\Services\TeacherInviteLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Public entry for a parent's or student's personal setup link.
 *
 * The school already created the account. This page only lets the person
 * choose a password; it never shows or creates one for them.
 */
class StudentParentSetupController extends Controller
{
    public function show(string $token): View|Response
    {
        $result = TeacherInviteLinkService::validateToken($token);

        if ($result === null) {
            return view('auth.invite-invalid', ['reason' => 'invalid']);
        }

        $invite = $result['invite'];
        $error = $result['error'];

        if ($error === 'revoked') {
            return view('auth.invite-invalid', ['reason' => 'revoked']);
        }

        if ($error === 'expired') {
            return view('auth.invite-invalid', ['reason' => 'expired', 'invite' => $invite]);
        }

        if ($error === 'claimed') {
            return view('auth.invite-invalid', ['reason' => 'claimed', 'invite' => $invite]);
        }

        $person = app(StudentParentAccountInviteService::class)->personForInvite($invite);
        if ($person === null) {
            return view('auth.invite-invalid', ['reason' => 'invalid']);
        }

        return view('auth.invite-set-password', [
            'token' => $token,
            'invite' => $invite,
            'school' => $result['school'],
            'className' => null,
            'claimRoute' => 'account.invite.claim',
            'roleLabel' => $invite->role === 'parent' ? 'as a parent' : 'as a student',
            'submitLabel' => 'Set password',
        ]);
    }

    public function claim(Request $request, string $token): RedirectResponse
    {
        $result = TeacherInviteLinkService::validateToken($token);

        if ($result === null) {
            return redirect()->route('account.invite.form', $token)
                ->with('error', 'This setup link is invalid.');
        }

        if ($result['error'] !== null) {
            $reason = match ($result['error']) {
                'expired' => 'This setup link has expired.',
                'revoked' => 'This setup link has been replaced.',
                default => 'This setup link has already been used.',
            };

            return redirect()->route('login')->with('error', $reason);
        }

        $invite = $result['invite'];
        $person = app(StudentParentAccountInviteService::class)->personForInvite($invite);
        if ($person === null) {
            return redirect()->route('login')
                ->with('error', 'This setup link no longer matches an account.');
        }

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

        $claim = app(StudentParentAccountInviteService::class)
            ->completeClaim($invite, $validated['password']);

        if (! ($claim['success'] ?? false)) {
            return redirect()->route('account.invite.form', $token)
                ->with('error', $claim['message'] ?? 'Could not set up your account.');
        }

        return redirect()->route('login')->with('successmessage', $claim['message']);
    }
}
