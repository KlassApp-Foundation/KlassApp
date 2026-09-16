<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

/**
 * Dashboard roster KPI cache — TTL + invalidation for student/teacher/parent counts.
 *
 * CACHE_TIME historically fed Cache::remember directly. When unset/null, Laravel
 * stores the value forever, so dashboards silently lied after onboarding uploads.
 */
class DashboardCache
{
    /** Default TTL when CACHE_TIME is missing or non-positive (5 minutes). */
    public const DEFAULT_TTL_SECONDS = 300;

    /**
     * Positive TTL in seconds for dashboard count keys.
     */
    public static function ttl(): int
    {
        $raw = env('CACHE_TIME');
        if ($raw === null || $raw === '') {
            return self::DEFAULT_TTL_SECONDS;
        }

        $ttl = (int) $raw;

        return $ttl > 0 ? $ttl : self::DEFAULT_TTL_SECONDS;
    }

    /**
     * Cache keys that must refresh whenever school roster membership changes.
     *
     * @return list<string>
     */
    public static function rosterCountKeys(int $schoolId): array
    {
        return [
            'studentCount_'.$schoolId,
            'teacherCount_'.$schoolId,
            'parentCount_'.$schoolId,
            'maleCount_'.$schoolId,
            'femaleCount_'.$schoolId,
            'unknownCount_'.$schoolId,
        ];
    }

    /**
     * Drop cached roster KPIs for a school so the next dashboard load re-queries.
     */
    public static function forgetRosterCounts(?int $schoolId): void
    {
        if ($schoolId === null || $schoolId < 1) {
            return;
        }

        foreach (self::rosterCountKeys($schoolId) as $key) {
            Cache::forget($key);
        }
    }
}
