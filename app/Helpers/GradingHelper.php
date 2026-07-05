<?php

namespace App\Helpers;

use App\Models\Standard;
use App\Models\Academics\SchoolGradingSystem;
use Illuminate\Support\Facades\Cache;

class GradingHelper
{
    /**
     * Determine the level type for a given standard.
     *
     * Handles both naming conventions:
     *   - Phase-level: "Primary", "O-Level", "Nursery" (used by Toshi onboarding)
     *   - Class-level: "Primary One", "Senior Three", "Baby Class" (used by direct seeder)
     */
    public static function levelTypeForStandard(Standard $standard): ?string
    {
        $name = strtolower(trim($standard->name));

        // Phase-level match (exact or prefix)
        $phaseMap = [
            'nursery' => ['nursery', 'pre-primary', 'kindergarten'],
            'primary' => ['primary'],
            'o-level' => ['o-level', 'o level', 'secondary', 'senior'],
            'a-level' => ['a-level', 'a level', 'high school', 'advanced'],
        ];

        foreach ($phaseMap as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if ($name === $keyword || str_starts_with($name, $keyword . ' ')) {
                    // For "Senior", disambiguate: Senior 5-6 is A-Level, Senior 1-4 is O-Level
                    if ($keyword === 'senior') {
                        return preg_match('/^(senior\s+(five|six))$/', $name) ? 'a-level' : 'o-level';
                    }
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * Get the grading scale config for a given level type.
     */
    public static function scaleConfig(string $levelType): array
    {
        $scales = config('grading_uganda', []);

        return $scales[$levelType] ?? [];
    }

    /**
     * Seed or update SchoolGradingSystem records for a school, for all
     * standards that match a given level type, using the config-driven scale.
     *
     * This is idempotent — call it whenever a school's grading setup needs
     * refreshing (e.g. after onboarding, or when a new standard is added).
     */
    public static function seedGradingForSchool(int $schoolId, string $levelType): void
    {
        $scale = self::scaleConfig($levelType);
        if (empty($scale)) {
            return;
        }

        $standards = Standard::where('school_id', $schoolId)->get();

        foreach ($standards as $standard) {
            if (self::levelTypeForStandard($standard) !== $levelType) {
                continue;
            }

            foreach ($scale as $gradeDef) {
                SchoolGradingSystem::updateOrCreate(
                    [
                        'school_id'    => $schoolId,
                        'standard_id'  => $standard->id,
                        'grade'        => $gradeDef['grade'],
                    ],
                    [
                        'points'    => $gradeDef['points'],
                        'min_score' => $gradeDef['min_score'],
                        'max_score' => $gradeDef['max_score'],
                        'remark'    => $gradeDef['remark'],
                    ]
                );
            }
        }

        Cache::forget("school_grading_{$schoolId}");
    }

    /**
     * Seed grading for ALL level types that have config defined.
     */
    public static function seedAllGradingForSchool(int $schoolId): void
    {
        $levelTypes = ['nursery', 'primary', 'o-level', 'a-level'];

        foreach ($levelTypes as $levelType) {
            $scale = self::scaleConfig($levelType);
            if (!empty($scale)) {
                self::seedGradingForSchool($schoolId, $levelType);
            }
        }
    }

    /**
     * Get the applicable grade for a raw percentage mark, given the
     * student's school and standard.
     *
     * Returns the grade string (e.g. "D1", "B") or null if no match.
     */
    public static function gradeForMark(int $mark, int $schoolId, int $standardId): ?string
    {
        return SchoolGradingSystem::where('school_id', $schoolId)
            ->where('standard_id', $standardId)
            ->where('min_score', '<=', $mark)
            ->where('max_score', '>=', $mark)
            ->value('grade');
    }
}
