<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Time-of-day greeting + display first name for role dashboards.
 *
 * First name always comes from userprofiles.firstname (raw DB value),
 * title-cased for display. Never from users.name (login handle).
 */
final class DashboardGreeting
{
    /**
     * @return array{phrase: string, name: string}
     */
    public static function for(User $user, string $fallback = 'Admin'): array
    {
        $hour = (int) now()->timezone(self::timezoneFor($user))->format('G');
        if ($hour < 12) {
            $phrase = 'Good morning';
        } elseif ($hour < 17) {
            $phrase = 'Good afternoon';
        } else {
            $phrase = 'Good evening';
        }

        return [
            'phrase' => $phrase,
            'name' => self::displayFirstName($user, $fallback),
        ];
    }

    public static function displayFirstName(User $user, string $fallback = 'Admin'): string
    {
        $profile = $user->userprofile;
        if (! $profile) {
            return $fallback;
        }

        // Bypass Userprofile::getFirstNameAttribute() which uppercases for storage/display elsewhere.
        $raw = trim((string) ($profile->getAttributes()['firstname'] ?? $profile->getRawOriginal('firstname') ?? ''));

        if ($raw === '') {
            return $fallback;
        }

        return Str::title(mb_strtolower($raw));
    }

    /**
     * School country zone when PHP knows one. A country with several zones
     * uses the first identifier PHP lists. Otherwise the app timezone.
     */
    public static function timezoneFor(User $user): string
    {
        $iso = strtoupper((string) ($user->school?->country?->iso_code ?? ''));
        if (preg_match('/^[A-Z]{2}$/', $iso) === 1) {
            $zones = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $iso);
            if ($zones !== []) {
                return $zones[0];
            }
        }

        $fallback = (string) config('app.timezone');

        return $fallback !== '' ? $fallback : 'UTC';
    }
}
