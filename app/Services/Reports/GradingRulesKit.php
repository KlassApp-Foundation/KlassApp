<?php

namespace App\Services\Reports;

/**
 * The grade, points and division arithmetic and the checks the rules screen needs, with no
 * database. The screen's live example, the server-side "try a mark" box and the tests all use
 * this one place (the browser script in public/js/progressive-rules.js mirrors it).
 *
 * A band is ['grade' => string, 'points' => ?int, 'min_score' => int, 'max_score' => int, 'remark' => string].
 * A division range is ['label' => string, 'min' => ?int, 'max' => ?int], lowest aggregate first.
 */
final class GradingRulesKit
{
    /**
     * The band a whole-number mark falls in, or null when the mark is outside 0 to 100 or no band covers it.
     *
     * @param  list<array<string, mixed>>  $bands
     * @return array<string, mixed>|null
     */
    public static function bandForMark(array $bands, int $mark): ?array
    {
        if ($mark < 0 || $mark > 100) {
            return null;
        }
        foreach ($bands as $band) {
            if ($mark >= (int) $band['min_score'] && $mark <= (int) $band['max_score']) {
                return $band;
            }
        }

        return null;
    }

    /**
     * The division label for an aggregate, or null when no range holds it.
     *
     * @param  list<array{label: string, min: ?int, max: ?int}>  $ranges
     */
    public static function divisionForAggregate(array $ranges, int $aggregate): ?string
    {
        foreach ($ranges as $range) {
            $min = $range['min'] ?? PHP_INT_MIN;
            $max = $range['max'] ?? PHP_INT_MAX;
            if ($aggregate >= $min && $aggregate <= $max) {
                return (string) $range['label'];
            }
        }

        return null;
    }

    /**
     * Plain-language problems with a set of bands: an empty list means the bands are valid.
     * Bands must cover every whole mark from 0 to 100 once, with no gap and no overlap.
     *
     * @param  list<array<string, mixed>>  $bands
     * @return list<string>
     */
    public static function bandProblems(array $bands): array
    {
        if ($bands === []) {
            return ['Enter at least one grade band.'];
        }

        foreach ($bands as $band) {
            if ((int) $band['min_score'] > (int) $band['max_score']) {
                return ["The {$band['grade']} band needs two marks from 0 to 100, lowest first."];
            }
        }

        usort($bands, fn (array $a, array $b) => $b['max_score'] <=> $a['max_score']);
        $expected = 100;
        foreach ($bands as $band) {
            if ((int) $band['max_score'] > $expected) {
                return ["The {$band['grade']} band overlaps the band above it. Each mark from 0 to 100 must be in exactly one band."];
            }
            if ((int) $band['max_score'] < $expected) {
                return ['The bands must run from 100 down to 0 with no gaps and no overlaps. Marks '.((int) $band['max_score'] + 1)." to {$expected} are in no band."];
            }
            $expected = (int) $band['min_score'] - 1;
        }
        if ($expected !== -1) {
            return ['The lowest band must start at 0. Marks 0 to '.$expected.' are in no band.'];
        }

        return [];
    }

    /**
     * Plain-language problems with division ranges, which must run from the lowest aggregate to the
     * highest in the order entered: the first has no lower limit, each next one starts exactly one above
     * the one before, and the last has no upper limit.
     *
     * @param  list<array{label: string, min: ?int, max: ?int}>  $ranges
     * @return list<string>
     */
    public static function rangeProblems(array $ranges): array
    {
        if ($ranges === []) {
            return ['Enter at least one division range, or keep the automatic ranges.'];
        }

        $first = $ranges[0];
        if ($first['min'] !== null) {
            return ["Division {$first['label']} is first, so it should have no lower limit. Leave \"From aggregate\" empty so every low aggregate has a division."];
        }
        $last = $ranges[count($ranges) - 1];
        if ($last['max'] !== null) {
            return ["Division {$last['label']} is last, so it should have no upper limit. Leave \"To aggregate\" empty so every high aggregate has a division."];
        }

        foreach ($ranges as $index => $range) {
            if ($range['min'] !== null && $range['max'] !== null && $range['min'] > $range['max']) {
                return ["The {$range['label']} range has the lower aggregate above the upper one."];
            }
            if ($index === 0) {
                continue;
            }
            $previous = $ranges[$index - 1];
            if ($previous['max'] === null || $range['min'] === null) {
                return ["Division {$previous['label']} and division {$range['label']} are not in order: only the first can be open below and only the last open above."];
            }
            if ($range['min'] <= $previous['max']) {
                return ['Division ranges must not overlap, and must go from the lowest aggregate to the highest. Division '.$range['label'].' starts at '.$range['min'].' but division '.$previous['label'].' runs to '.$previous['max'].'.'];
            }
            if ($range['min'] > $previous['max'] + 1) {
                return ["There is a gap between division {$previous['label']} and division {$range['label']}: aggregates ".($previous['max'] + 1).' to '.($range['min'] - 1).' belong to no division.'];
            }
        }

        return [];
    }
}
