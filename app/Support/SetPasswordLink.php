<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Password;

/**
 * Signed, expiring set-password URL from Laravel's password broker.
 * The plain password is never part of the result.
 */
final class SetPasswordLink
{
    /**
     * @return array{url: string, expires_at: Carbon, minutes: int}
     */
    public static function issue(User $user): array
    {
        $token = Password::broker()->createToken($user);
        $minutes = max(1, (int) config('auth.passwords.users.expire', 60));

        return [
            'url' => url('/password/reset/'.$token).'?email='.urlencode($user->getEmailForPasswordReset()),
            'expires_at' => Carbon::now()->addMinutes($minutes),
            'minutes' => $minutes,
        ];
    }
}
