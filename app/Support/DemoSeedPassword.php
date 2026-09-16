<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Resolves passwords for demo/staging seeders.
 *
 * Prefer an explicit pin via STAGING_DEMO_PASSWORD (or DEMO_SEED_PASSWORD)
 * so Cloud staging can keep a rotated secret. When unset, generate a random
 * password and never echo it from seeders.
 */
final class DemoSeedPassword
{
    public static function resolve(): string
    {
        $pinned = env('STAGING_DEMO_PASSWORD', env('DEMO_SEED_PASSWORD'));

        if (is_string($pinned) && $pinned !== '') {
            return $pinned;
        }

        return Str::random(32);
    }
}
