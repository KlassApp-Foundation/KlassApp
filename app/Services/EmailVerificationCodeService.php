<?php

namespace App\Services;

use App\Mail\EmailVerificationCodeMail;
use App\Models\Authentication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Sign-up email verification: a 6-digit code (hashed at rest) and a signed
 * "Confirm email" link, both issued in one message with the same 15-minute
 * expiry. Confirming by either uses up both. Code locks for 15 minutes after
 * 5 failed attempts.
 */
class EmailVerificationCodeService
{
    public const MINUTES_VALID = 15;

    public const MAX_ATTEMPTS = 5;

    /** Kept distinct from 'register' / 'password_reset' so those legacy plaintext flows are untouched. */
    public const TYPE = 'email_verification';

    /** The link half of the same message. token = sha256 of the random URL token. */
    public const LINK_TYPE = 'email_verification_link';

    public const OK = 'ok';
    public const INVALID = 'invalid';
    public const EXPIRED = 'expired';
    public const LOCKED = 'locked';

    /**
     * Issue a fresh code + link and queue the email. The plaintext code is
     * returned for the mail payload only — persisting it would defeat hashing
     * at rest. Anything outstanding (code or link) is cancelled first.
     */
    public function issue(User $user, ?string $ip = null): string
    {
        $code = (string) random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(self::MINUTES_VALID);

        $this->cancelOutstanding($user);

        $authentication = new Authentication();
        $authentication->user_id = $user->id;
        $authentication->type = self::TYPE;
        $authentication->token = Hash::make($code);
        $authentication->ip_address = (string) $ip;
        $authentication->expires_on = $expiresAt;
        $authentication->status = 0;
        $authentication->attempts = 0;
        $authentication->locked_until = null;
        $authentication->save();

        // 64 random chars: unguessable, so a fast hash is enough at rest.
        $token = Str::random(64);

        $link = new Authentication();
        $link->user_id = $user->id;
        $link->type = self::LINK_TYPE;
        $link->token = hash('sha256', $token);
        $link->ip_address = (string) $ip;
        $link->expires_on = $expiresAt;
        $link->status = 0;
        $link->save();

        $confirmUrl = URL::temporarySignedRoute('register.verify.link', $expiresAt, ['token' => $token]);

        Mail::to($user->email)->queue(
            new EmailVerificationCodeMail($user, $code, self::MINUTES_VALID, $confirmUrl)
        );

        // Staging/local use MAIL_MAILER=log; Cloud runtime logs are the sandbox.
        // Nightwatch ingest on staging is currently broken (No authentication details /
        // quota), so we also emit a structured line that laravel-cloud-socket indexes
        // for E2E. Never fires when the real SMTP mailer is configured (production).
        if (config('mail.default') === 'log') {
            Log::info('klassapp.email_verification_code', [
                'email' => $user->email,
                'code' => $code,
            ]);
        }

        return $code;
    }

    /**
     * @return string self::OK | self::INVALID | self::EXPIRED | self::LOCKED
     */
    public function verify(User $user, string $code): string
    {
        $authentication = Authentication::where('user_id', $user->id)
            ->where('type', self::TYPE)
            ->where('status', 0)
            ->orderByDesc('id')
            ->first();

        if (! $authentication) {
            return self::EXPIRED;
        }

        if ($authentication->locked_until !== null
            && $authentication->locked_until->isFuture()) {
            return self::LOCKED;
        }

        if ($authentication->expires_on->isPast()) {
            return self::EXPIRED;
        }

        if (Hash::check($code, $authentication->token)) {
            // Consumed: a code verifies exactly once, and so does its link.
            $this->cancelOutstanding($user);

            return self::OK;
        }

        $authentication->attempts = (int) $authentication->attempts + 1;

        $nowLocked = $authentication->attempts >= self::MAX_ATTEMPTS;
        if ($nowLocked) {
            $authentication->locked_until = Carbon::now()->addMinutes(self::MINUTES_VALID);
        }

        $authentication->save();

        return $nowLocked ? self::LOCKED : self::INVALID;
    }

    /**
     * Confirm by link token. Single use: success cancels the code as well.
     *
     * @return array{0: string, 1: ?User} [self::OK | self::EXPIRED, user]. EXPIRED covers
     *         unknown, already-used, superseded and timed-out tokens alike.
     */
    public function confirmLinkToken(string $token): array
    {
        $row = Authentication::where('type', self::LINK_TYPE)
            ->where('token', hash('sha256', $token))
            ->where('status', 0)
            ->first();

        if (! $row || $row->expires_on->isPast()) {
            return [self::EXPIRED, null];
        }

        $user = User::find($row->user_id);

        if (! $user || (int) $user->email_verified === 1) {
            return [self::EXPIRED, null];
        }

        $this->cancelOutstanding($user);

        $this->markVerified($user);

        return [self::OK, $user];
    }

    /** Is this link token still usable (used by the GET page; never consumes it). */
    public function peekLinkToken(string $token): bool
    {
        $row = Authentication::where('type', self::LINK_TYPE)
            ->where('token', hash('sha256', $token))
            ->where('status', 0)
            ->first();

        if (! $row || $row->expires_on->isPast()) {
            return false;
        }

        $user = User::find($row->user_id);

        return $user !== null && (int) $user->email_verified !== 1;
    }

    public function markVerified(User $user): void
    {
        $user->forceFill([
            'email_verified' => 1,
            'email_verified_at' => now(),
        ])->save();
    }

    public function userIdForLinkToken(string $token): ?int
    {
        $id = Authentication::where('type', self::LINK_TYPE)
            ->where('token', hash('sha256', $token))
            ->value('user_id');

        return $id ? (int) $id : null;
    }

    public function attemptsRemaining(User $user): int
    {
        $authentication = Authentication::where('user_id', $user->id)
            ->where('type', self::TYPE)
            ->where('status', 0)
            ->orderByDesc('id')
            ->first();

        if (! $authentication) {
            return 0;
        }

        return max(0, self::MAX_ATTEMPTS - (int) $authentication->attempts);
    }

    /** Expiry of the outstanding code (and link), or null when none is live. */
    public function outstandingExpiry(User $user): ?Carbon
    {
        $row = Authentication::where('user_id', $user->id)
            ->where('type', self::TYPE)
            ->where('status', 0)
            ->orderByDesc('id')
            ->first();

        return $row?->expires_on;
    }

    /** Mark every unused code and link for this user as used. */
    private function cancelOutstanding(User $user): void
    {
        Authentication::where('user_id', $user->id)
            ->whereIn('type', [self::TYPE, self::LINK_TYPE])
            ->where('status', 0)
            ->update(['status' => 1]);
    }
}
