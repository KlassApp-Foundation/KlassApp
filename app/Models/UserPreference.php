<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Small per-user preference store (key/value). Used by the dashboard v2
 * setup banner so a dismissal sticks per user and school, across devices,
 * instead of living in the browser or the session.
 */
class UserPreference extends Model
{
    protected $fillable = ['user_id', 'key', 'value'];

    public static function get(int|User $user, string $key, ?string $default = null): ?string
    {
        $userId = $user instanceof User ? $user->id : $user;

        $row = static::query()->where('user_id', $userId)->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    public static function set(int|User $user, string $key, ?string $value): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        static::query()->updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value],
        );
    }

    public static function forget(int|User $user, string $key): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        static::query()->where('user_id', $userId)->where('key', $key)->delete();
    }

    /** Preference key for the dashboard setup banner (per user and school). */
    public static function setupBannerDismissedKey(int $schoolId): string
    {
        return 'dashboard.setup_banner_dismissed.'.$schoolId;
    }
}
