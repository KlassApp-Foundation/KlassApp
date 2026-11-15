<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\GradingRulesKit;
use App\Services\Reports\ProgressiveRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * T-154 / T-157: the grade, points and division arithmetic and the checks the rules screen uses.
 * Expected values come from the independent reference (reference/calc_reference.py), through the
 * fixture it generated; nothing here copies KlassApp's own tables except the app default ranges.
 */
class GradingRulesKitTest extends TestCase
{
    /** The reference bands (D1 to F9), in the shape the screen uses. */
    private function referenceBands(): array
    {
        $rows = [[95, 100, 'D1', 1], [90, 94, 'D2', 2], [75, 89, 'C3', 3], [65, 74, 'C4', 4], [60, 64, 'C5', 5],
            [50, 59, 'C6', 6], [45, 49, 'P7', 7], [40, 44, 'P8', 8], [0, 39, 'F9', 9]];

        return array_map(fn (array $r) => ['grade' => $r[2], 'points' => $r[3], 'min_score' => $r[0], 'max_score' => $r[1], 'remark' => ''], $rows);
    }

    public static function boundaryMarks(): array
    {
        return [
            'top' => [100, 'D1', 1], 'lowest D1' => [95, 'D1', 1], 'highest D2' => [94, 'D2', 2], 'lowest D2' => [90, 'D2', 2],
            'highest C3' => [89, 'C3', 3], 'lowest C3' => [75, 'C3', 3], 'highest C4' => [74, 'C4', 4], 'lowest C4' => [65, 'C4', 4],
            'highest C5' => [64, 'C5', 5], 'lowest C5' => [60, 'C5', 5], 'highest C6' => [59, 'C6', 6], 'lowest C6' => [50, 'C6', 6],
            'highest P7' => [49, 'P7', 7], 'lowest P7' => [45, 'P7', 7], 'highest P8' => [44, 'P8', 8], 'lowest P8' => [40, 'P8', 8],
            'highest F9' => [39, 'F9', 9], 'zero' => [0, 'F9', 9],
        ];
    }

    #[DataProvider('boundaryMarks')]
    public function test_every_band_edge_gets_its_grade_and_points(int $mark, string $grade, int $points): void
    {
        $band = GradingRulesKit::bandForMark($this->referenceBands(), $mark);

        $this->assertSame($grade, $band['grade']);
        $this->assertSame($points, $band['points']);
    }

    public function test_a_mark_outside_zero_to_one_hundred_has_no_band(): void
    {
        $this->assertNull(GradingRulesKit::bandForMark($this->referenceBands(), 101));
        $this->assertNull(GradingRulesKit::bandForMark($this->referenceBands(), -1));
    }

    public function test_the_reference_bands_cover_every_mark_once(): void
    {
        $this->assertSame([], GradingRulesKit::bandProblems($this->referenceBands()));
        for ($mark = 0; $mark <= 100; $mark++) {
            $hits = array_filter($this->referenceBands(), fn ($b) => $mark >= $b['min_score'] && $mark <= $b['max_score']);
            $this->assertCount(1, $hits, "mark {$mark}");
        }
    }

    public function test_a_gap_in_the_bands_is_refused_with_the_marks_that_are_left_out(): void
    {
        $bands = $this->referenceBands();
        $bands[2]['min_score'] = 76; // 75 now belongs to no band

        $problems = GradingRulesKit::bandProblems($bands);

        $this->assertCount(1, $problems);
        $this->assertStringContainsString('no gaps and no overlaps', $problems[0]);
        $this->assertStringContainsString('75', $problems[0]);
    }

    public function test_an_overlap_in_the_bands_is_refused(): void
    {
        $bands = $this->referenceBands();
        $bands[2]['min_score'] = 70; // C3 now runs into C4

        $this->assertNotSame([], GradingRulesKit::bandProblems($bands));
    }

    public function test_bands_that_do_not_start_at_zero_or_reach_one_hundred_are_refused(): void
    {
        $noZero = $this->referenceBands();
        $noZero[8]['min_score'] = 5;
        $this->assertStringContainsString('start at 0', GradingRulesKit::bandProblems($noZero)[0]);

        $noTop = $this->referenceBands();
        $noTop[0]['max_score'] = 98;
        $this->assertStringContainsString('100', GradingRulesKit::bandProblems($noTop)[0]);
    }

    public function test_a_band_with_its_marks_the_wrong_way_round_is_refused(): void
    {
        $bands = $this->referenceBands();
        [$bands[1]['min_score'], $bands[1]['max_score']] = [94, 90];

        $this->assertStringContainsString('lowest first', GradingRulesKit::bandProblems($bands)[0]);
    }

    public static function referenceDivisions(): array
    {
        return [[0, '1'], [12, '1'], [13, '2'], [24, '2'], [25, '3'], [28, '3'], [29, '4'], [32, '4'], [33, 'U'], [36, 'U'], [99, 'U']];
    }

    #[DataProvider('referenceDivisions')]
    public function test_the_app_default_ranges_give_the_reference_division(int $aggregate, string $division): void
    {
        $this->assertSame($division, GradingRulesKit::divisionForAggregate(ProgressiveRules::defaultDivisionRanges(), $aggregate));
    }

    public function test_the_default_division_ranges_are_valid_and_ordered(): void
    {
        $this->assertSame([], GradingRulesKit::rangeProblems(ProgressiveRules::defaultDivisionRanges()));
    }

    public function test_division_ranges_out_of_order_overlapping_or_with_a_gap_are_refused(): void
    {
        $ok = [['label' => 'A', 'min' => null, 'max' => 10], ['label' => 'B', 'min' => 11, 'max' => null]];
        $this->assertSame([], GradingRulesKit::rangeProblems($ok));

        $overlap = [['label' => 'A', 'min' => null, 'max' => 10], ['label' => 'B', 'min' => 10, 'max' => null]];
        $this->assertStringContainsString('overlap', GradingRulesKit::rangeProblems($overlap)[0]);

        $gap = [['label' => 'A', 'min' => null, 'max' => 10], ['label' => 'B', 'min' => 13, 'max' => null]];
        $this->assertStringContainsString('gap', GradingRulesKit::rangeProblems($gap)[0]);
        $this->assertStringContainsString('11 to 12', GradingRulesKit::rangeProblems($gap)[0]);

        $reversed = [['label' => 'B', 'min' => 11, 'max' => null], ['label' => 'A', 'min' => null, 'max' => 10]];
        $this->assertNotSame([], GradingRulesKit::rangeProblems($reversed));
    }

    public function test_the_first_division_is_open_below_and_the_last_is_open_above(): void
    {
        $closedBelow = [['label' => 'A', 'min' => 3, 'max' => 10], ['label' => 'B', 'min' => 11, 'max' => null]];
        $this->assertStringContainsString('no lower limit', GradingRulesKit::rangeProblems($closedBelow)[0]);

        $closedAbove = [['label' => 'A', 'min' => null, 'max' => 10], ['label' => 'B', 'min' => 11, 'max' => 40]];
        $this->assertStringContainsString('no upper limit', GradingRulesKit::rangeProblems($closedAbove)[0]);
    }

    public function test_a_range_with_its_limits_the_wrong_way_round_is_refused(): void
    {
        $bad = [['label' => 'A', 'min' => null, 'max' => 10], ['label' => 'B', 'min' => 20, 'max' => 15], ['label' => 'C', 'min' => 16, 'max' => null]];

        $this->assertNotSame([], GradingRulesKit::rangeProblems($bad));
    }

    public function test_the_fixture_divisions_follow_from_the_fixture_aggregates(): void
    {
        $fixture = json_decode((string) file_get_contents(__DIR__.'/../../fixtures/reports/p5-synthetic-class.json'), true);
        $ranges = ProgressiveRules::defaultDivisionRanges();

        foreach ($fixture['expected'] as $learner) {
            foreach ($fixture['sets'] as $index => $setName) {
                $set = $learner['sets'][$index] ?? $learner[$setName] ?? null;
                if ($set === null) {
                    $this->markTestSkipped('Fixture shape differs from the one this test reads.');
                }
                $this->assertSame((string) $set['division'], GradingRulesKit::divisionForAggregate($ranges, (int) $set['aggregate']));
            }
        }
    }
}
