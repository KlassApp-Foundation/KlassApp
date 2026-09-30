<?php

namespace App\Services;

use App\Mail\EmailVerificationCodeMail;
use App\Models\Authentication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Sign-up email verification codes: 6 digits, hashed at rest, 15-minute
 * expiry, locked for 15 minutes after 5 failed attempts.
 */
class EmailVerificationCodeService
{
    public const MINUTES_VALID = 15;

    public const MAX_ATTEMPTS = 5;

    /** Kept distinct from 'register' / 'password_reset' so those legacy plaintext flows are untouched. */
    public const TYPE = 'email_verification';

    public const OK = 'ok';
    public const INVALID = 'invalid';
    public const EXPIRED = 'expired';
    public const LOCKED = 'locked';

    /**
     * Issue a fresh code and queue the email. The plaintext is returned for the
     * mail payload only — persisting it would defeat hashing at rest.
     */
    public function issue(User $user, ?string $ip = null): string
    {
        $code = (string) random_int(100000, 999999);

        // Supersede anything outstanding so only the newest code can ever verify.
        Authentication::where('user_id', $user->id)
            ->where('type', self::TYPE)
            ->where('status', 0)
            ->update(['status' => 1]);

        $authentication = new Authentication();
        $authentication->user_id = $user->id;
        $authentication->type = self::TYPE;
        $authentication->token = Hash::make($code);
        $authentication->ip_address = (string) $ip;
        $authentication->expires_on = Carbon::now()->addMinutes(self::MINUTES_VALID);
        $authentication->status = 0;
        $authentication->attempts = 0;
        $authentication->locked_until = null;
        $authentication->save();

        Mail::to($user->email)->queue(
            new EmailVerificationCodeMail($user, $code, self::MINUTES_VALID)
        );

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
            // Consumed: a code verifies exactly once, so a leaked code cannot be replayed.
            $authentication->status = 1;
            $authentication->save();

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
}
