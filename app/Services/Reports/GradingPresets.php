<?php

namespace App\Services\Reports;

use App\Helpers\GradingHelper;
use App\Models\Standard;

/**
 * Starting points for the progressive report rules screen. Loading a preset only fills the form;
 * nothing is saved until an admin or head teacher presses Save, and a saved change applies from the
 * next release (a released report stores the rules it was made with).
 */
final class GradingPresets
{
    public const DEFAULT = 'default';

    public const OLDER_SEEDED = 'older-seeded';

    public const CUSTOM = 'custom';

    /** @return array<string, string> key => label */
    public static function labels(): array
    {
        return [
            self::DEFAULT => 'App default',
            self::OLDER_SEEDED => 'Older seeded scale (confirm with the school)',
            self::CUSTOM => 'Custom',
        ];
    }

    /**
     * The grade bands of a preset for one class level. Custom has none: it keeps whatever is on screen.
     *
     * The older seeded primary table that the repository's restructure seeder command wrote for one
     * school (marks 80 to 89 are 3 points, 70 to 79 are 4, and so on). It differs from the app default,
     * has not been confirmed by any school, and the screen says "confirm with the school". The values
     * are copied here, not read from the command, so this screen does not depend on a command class.
     *
     * @return list<array{grade: string, points: ?int, min_score: int, max_score: int, remark: string}>|null
     */
    public static function bands(string $preset, int $schoolId, Standard $standard): ?array
    {
        if ($preset === self::OLDER_SEEDED) {
            return [
                ['grade' => 'D1', 'points' => 1, 'min_score' => 95, 'max_score' => 100, 'remark' => 'Excellent'],
                ['grade' => 'D2', 'points' => 2, 'min_score' => 90, 'max_score' => 94, 'remark' => 'V.Good'],
                ['grade' => 'C3', 'points' => 3, 'min_score' => 80, 'max_score' => 89, 'remark' => 'Good'],
                ['grade' => 'C4', 'points' => 4, 'min_score' => 70, 'max_score' => 79, 'remark' => 'Good'],
                ['grade' => 'C5', 'points' => 5, 'min_score' => 60, 'max_score' => 69, 'remark' => 'Fairly Good'],
                ['grade' => 'C6', 'points' => 6, 'min_score' => 55, 'max_score' => 59, 'remark' => 'Promising'],
                ['grade' => 'P7', 'points' => 7, 'min_score' => 50, 'max_score' => 54, 'remark' => 'Fair'],
                ['grade' => 'P8', 'points' => 8, 'min_score' => 40, 'max_score' => 49, 'remark' => 'Work harder / Aim higher'],
                ['grade' => 'F9', 'points' => 9, 'min_score' => 0, 'max_score' => 39, 'remark' => 'Extra effort'],
            ];
        }
        if ($preset === self::DEFAULT) {
            return self::appDefaultBands($schoolId, $standard);
        }

        return null;
    }

    /**
     * The app's own bands for this class level, before any saved change.
     *
     * @return list<array{grade: string, points: ?int, min_score: int, max_score: int, remark: string}>
     */
    public static function appDefaultBands(int $schoolId, Standard $standard): array
    {
        $level = GradingHelper::levelTypeForStandard($standard) ?? 'primary';

        return array_map(fn (array $band) => [
            'grade' => (string) ($band['grade'] ?? ''),
            'points' => isset($band['points']) && $band['points'] !== null ? (int) $band['points'] : null,
            'min_score' => (int) ($band['min_score'] ?? 0),
            'max_score' => (int) ($band['max_score'] ?? 0),
            'remark' => (string) ($band['remark'] ?? ''),
        ], GradingHelper::scaleConfigForSchool($schoolId, $level));
    }

    /**
     * Division ranges of a preset, or null to keep what is on screen (custom).
     *
     * @return list<array{label: string, min: ?int, max: ?int}>|null
     */
    public static function divisionRanges(string $preset): ?array
    {
        return in_array($preset, [self::DEFAULT, self::OLDER_SEEDED], true)
            ? ProgressiveRules::defaultDivisionRanges()
            : null;
    }

    /** Ranking choice of a preset: automatic, aggregate or total; null keeps what is on screen. */
    public static function ranking(string $preset): ?string
    {
        return match ($preset) {
            self::DEFAULT => 'automatic',
            self::OLDER_SEEDED => 'aggregate',
            default => null,
        };
    }

    public static function isPreset(string $key): bool
    {
        return array_key_exists($key, self::labels());
    }
}
