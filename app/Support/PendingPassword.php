<?php

namespace App\Support;

use App\Models\User;

/**
 * An account whose password column is empty has not finished setup.
 * No sign-in route may accept it, and password reset must not fill it in.
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
