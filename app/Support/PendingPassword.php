<?php

namespace App\Support;

use App\Models\User;

/**
 * An account whose password column is empty has not finished setup.
 * No password sign-in route may accept it, and password reset must not fill
 * it in. The single exception is a parent's single-use WhatsApp magic link:
 * an imported guardian without an email has no other way in, and that link
 * is possession-based, signed, single-use and rate-limited.
 */
final class PendingPassword
{
    public static function isUnset(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        $password = $user->getAttributes()['password'] ?? null;

        return $password === null || $password === '';
    }
}
