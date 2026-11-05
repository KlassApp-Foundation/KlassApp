<?php

namespace App\Helpers;

use App\Models\Standard;
use App\Models\School;
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
                if ($name === $keyword || str_starts_with($name, $keyword . ' ') || str_starts_with($name, $keyword . '_')) {
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
        // FLAGGED(product-decision): onboarding seeds the Uganda grading scales, including O-level and A-level bands. see docs/onboarding-engine-plan.md
        $scales = config('grading_uganda', []);

        return $scales[$levelType] ?? [];
    }

    /**
     * The school's curriculum profile key, or the configured default when
     * unset or unknown. See config/curricula.php.
     */
    public static function profileKeyForSchool(int $schoolId): string
    {
        $key = trim((string) (School::query()->find($schoolId)?->curriculum_profile ?? ''));
        $profiles = config('curricula.profiles', []);

        return ($key !== '' && isset($profiles[$key])) ? $key : (string) config('curricula.default_profile');
    }

    /**
     * The band table a school's curriculum profile selects for a level type.
     * Profiles reference keys inside config/grading_uganda.php; an unknown
     * reference falls back to the level type itself (today's behaviour).
     *
     * The legacy O-level scale is the exception: UNEB never published one fixed
     * pre-NLSC table, so the school owns those boundaries. A school that saved
     * its own table gets it here; a school that has not saved one gets the
     * labelled example, which it may accept or edit.
     */
    public static function scaleConfigForSchool(int $schoolId, string $levelType): array
    {
        $profile = config('curricula.profiles.' . self::profileKeyForSchool($schoolId), []);
        $bandsKey = (string) ($profile['levels'][$levelType] ?? $levelType);

        if ($bandsKey === 'o-level-legacy') {
            return self::legacyBandsForSchool($schoolId);
        }

        $bands = config('grading_uganda.' . $bandsKey, []);

        return is_array($bands) ? $bands : [];
    }

    /** The letters and points of the legacy scale, in order. Fixed; only the cut-offs move. */
    public static function legacyLetters(): array
    {
        $example = config('grading_uganda.o-level-legacy', []);

        return array_map(fn (array $band) => [
            'grade' => (string) $band['grade'],
            'points' => $band['points'] ?? null,
            'remark' => (string) ($band['remark'] ?? ''),
        ], is_array($example) ? $example : []);
    }

    /**
     * The legacy O-level table this school uses: its own saved boundaries when
     * it has them, otherwise the example shipped in config.
     */
    public static function legacyBandsForSchool(int $schoolId): array
    {
        $letters = self::legacyLetters();
        $saved = School::query()->find($schoolId)?->legacy_o_level_bands;

        if (! is_array($saved) || $saved === []) {
            return config('grading_uganda.o-level-legacy', []);
        }

        $byGrade = [];
        foreach ($saved as $row) {
            if (is_array($row) && isset($row['grade'])) {
                $byGrade[strtoupper((string) $row['grade'])] = $row;
            }
        }

        $bands = [];
        foreach ($letters as $letter) {
            $row = $byGrade[strtoupper($letter['grade'])] ?? null;
            if ($row === null || ! isset($row['min_score'], $row['max_score'])) {
                continue;
            }
            $bands[] = [
                'grade' => $letter['grade'],
                'points' => $letter['points'],
                'min_score' => (int) $row['min_score'],
                'max_score' => (int) $row['max_score'],
                'remark' => $letter['remark'],
            ];
        }

        // A saved table that does not cover every letter is not usable: fall
        // back to the example rather than grade a learner against half a scale.
        return count($bands) === count($letters) && $bands !== [] ? $bands : config('grading_uganda.o-level-legacy', []);
    }

    /**
     * A level whose configured bands have score bounds is graded from a mark.
     * A descriptive scale (no min and max) is not.
     */
    public static function scaleUsesNumericMarks(string $levelType): bool
    {
        foreach (self::scaleConfig($levelType) as $band) {
            if (isset($band['min_score'], $band['max_score']) && $band['min_score'] !== null && $band['max_score'] !== null) {
                return true;
            }
        }

        return false;
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
        $scale = self::scaleConfigForSchool($schoolId, $levelType);
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
     * Aggregate points for a grading-system row.
     * Prefer explicit `points`; fall back to a numeric `grade` label (primary 1–9
     * bands were historically seeded with points=null while grade held the band).
     */
    public static function effectivePoints(object $gradingRow): ?int
    {
        if (isset($gradingRow->points) && $gradingRow->points !== null && $gradingRow->points !== '') {
            return (int) $gradingRow->points;
        }

        $grade = isset($gradingRow->grade) ? trim((string) $gradingRow->grade) : '';
        if ($grade !== '' && ctype_digit($grade)) {
            return (int) $grade;
        }

        return null;
    }

    /**
     * UNEB-style AGG cell label, e.g. "C3". Returns null when the row has no
     * numeric aggregate points (descriptive nursery / O-level letter scales).
     */
    public static function formatAggLabel(?object $gradingRow, array $gradeLetters = []): ?string
    {
        if ($gradingRow === null) {
            return null;
        }

        $points = self::effectivePoints($gradingRow);
        if ($points === null) {
            return null;
        }

        $defaultLetters = [
            1 => 'D', 2 => 'D', 3 => 'C', 4 => 'C', 5 => 'C',
            6 => 'C', 7 => 'P', 8 => 'P', 9 => 'F',
        ];
        $letters = $gradeLetters !== [] ? $gradeLetters : $defaultLetters;
        $letter = $letters[$points] ?? $letters[(string) $points] ?? 'D';

        return $letter.$points;
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
