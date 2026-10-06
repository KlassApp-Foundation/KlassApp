<?php

namespace App\Support;

use App\Models\SchoolDetail;
use Throwable;

/**
 * Tracks what the demo seeders created for a demo school so that
 * `php artisan demo:refresh` can restore the baseline later.
 *
 * The manifest lives in the school_details table (meta_key = 'demo_manifest',
 * meta_value = JSON) — NOT in local storage, which Laravel Cloud wipes on
 * redeploy. It never contains credentials: only user emails, ids and a few
 * walkthrough hints used by the refresh command.
 */
final class DemoSeedManifest
{
    public const META_KEY = 'demo_manifest';

    /**
     * @return array<string, mixed>|null
     */
    public static function read(int $schoolId): ?array
    {
        try {
            $row = SchoolDetail::where('school_id', $schoolId)
                ->where('meta_key', self::META_KEY)
                ->first();
        } catch (Throwable) {
            return null;
        }

        if (! $row) {
            return null;
        }

        $data = json_decode((string) $row->meta_value, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Write (replace) the manifest for a school.
     *
     * `seeded_at` is refreshed on every write so the refresh command can
     * treat "created after seeded_at" as "added after the last seeding".
     *
     * @param  array<string, mixed>  $data
     */
    public static function write(int $schoolId, array $data): bool
    {
        try {
            $data['seeded_at'] = $data['seeded_at'] ?? now()->toDateTimeString();

            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if ($json === false) {
                return false;
            }

            SchoolDetail::updateOrCreate(
                ['school_id' => $schoolId, 'meta_key' => self::META_KEY],
                ['meta_value' => $json]
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Emails of the users that existed when a school was last seeded.
     *
     * @param  array<string, mixed>|null  $manifest
     * @return array<int, string>
     */
    public static function emails(?array $manifest): array
    {
        $emails = $manifest['user_emails'] ?? [];

        return is_array($emails) ? array_values(array_filter($emails)) : [];
    }
}
