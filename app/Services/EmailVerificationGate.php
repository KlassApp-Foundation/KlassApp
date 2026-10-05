<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * One rule for every password-based sign-in path: an account whose email
 * was never confirmed signs in until the code entry screen only. It spans
 * the web login (AuthenticatesUsers), the password-reset autologin and the
 * three mobile-token logins. Invites, Google sign-ins and the sign-up code
 * flow verify by construction; accounts without an email at all (WhatsApp
 * only) have nothing to verify.
 */
class EmailVerificationGate
{
    /**
     * The built-in boundary, used whenever the EMAIL_VERIFICATION_CUTOFF
     * environment variable (config('app.email_verification_cutoff')) is
     * blank: accounts created before it predate the gate. It does a backfill
     * migration's job without touching the database. Everyone who signed up
     * after it confirms their email the first time they sign in.
     *
     * The boundary is an instant, not a calendar day, regardless of the
     * app's timezone: Carbon compares instants, so a Kampala deployment
     * (UTC+3) exempts, and must not lock out, an account written at
     * 2026-10-04 22:00 UTC even though its Kampala wall clock already read
     * Oct 5. A configured cutoff is parsed the same way — a bare date as
     * UTC midnight, an ISO-8601 carrying its own offset keeps that offset.
     */
    public const CUTOFF = '2026-10-05';

    /**
     * The effective cutoff instant: the configured value when one is set,
     * otherwise the built-in default. Never throws for a blank config.
     */
    public static function cutoffInstant(): Carbon
    {
        $raw = config('app.email_verification_cutoff', self::CUTOFF);

        if ($raw === null || trim((string) $raw) === '') {
            $raw = self::CUTOFF;
        }

        return Carbon::parse((string) $raw, 'UTC');
    }

    public static function needsVerification(?User $user): bool
    {
        if ($user === null || trim((string) $user->email) === '') {
            // Nothing to verify: WhatsApp-only accounts never had an email.
            return false;
        }

        if ((int) $user->email_verified === 1) {
            return false;
        }

        if ($user->created_at !== null
            && $user->created_at->lessThan(self::cutoffInstant())) {
            return false;
        }

        return true;
    }

    /**
     * Park the user on the shared code-entry screen with a fresh code.
     * It reuses the sign-up verification session, so the screen, the code
     * check, the resend and the status poll work unchanged on this path.
     */
    public function sendToCodeEntry(Request $request, User $user): RedirectResponse
    {
        $request->session()->put('pending_verification_user_id', $user->id);
        app(EmailVerificationCodeService::class)->issue($user, (string) $request->ip());

        return redirect()->route('register.verify');
    }
}
