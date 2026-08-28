<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Shared password provisioning for admin-created user accounts.
 * Matches OnboardingEngine::saveTeachers / saveStudents (Str::random(16) + is_reset=1).
 */
final class UserProvisioning
{
    public const RANDOM_PASSWORD_LENGTH = 16;

    public static function plainRandomPassword(): string
    {
        return Str::random(self::RANDOM_PASSWORD_LENGTH);
    }

    public static function hashPassword(string $plain): string
    {
        return bcrypt($plain);
    }

    /**
     * Random hashed password + is_reset flag (plain password not retained).
     *
     * @return array{password: string, is_reset: int}
     */
    public static function randomPasswordAttributes(): array
    {
        return [
            'password' => bcrypt(Str::random(self::RANDOM_PASSWORD_LENGTH)),
            'is_reset' => 1,
        ];
    }

    /**
     * Random credentials when the plain password must be delivered (e.g. invite email).
     *
     * @return array{plain: string, password: string, is_reset: int}
     */
    public static function randomPasswordCredentials(): array
    {
        $plain = self::plainRandomPassword();

        return [
            'plain' => $plain,
            'password' => self::hashPassword($plain),
            'is_reset' => 1,
        ];
    }
}
