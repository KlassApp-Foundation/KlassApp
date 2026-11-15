<?php

namespace Tests\Feature\Reports;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Services\Reports\GradingRulesKit;
use App\Services\Reports\ReportScoring;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SyntheticClass;
use Tests\TestCase;

/**
 * T-157: every screen shows the same division for the same learner, and it is the division the school's
 * own ranges give. The school saves ranges that are nothing like the app's (so a screen that still has a
 * built-in table is caught), and all 76 learners are compared on the results sheet, the card's view data
 * for both sets, and the text of the PDF for the learners that test the rules. The marks-owned
 * OneDivisionTableTest covers the other screens for one learner; this extends it to the whole class.
 */
class DivisionConsistencyTest extends TestCase
{
    use RefreshDatabase;

    /** Not the app's table (12, 24, 28, 32). */
    private const RANGES = [
        ['label' => 'A', 'min' => '', 'max' => '10'],
        ['label' => 'B', 'min' => '11', 'max' => '20'],
        ['label' => 'C', 'min' => '21', 'max' => '30'],
        ['label' => 'D', 'min' => '31', 'max' => '36'],
        ['label' => 'E', 'min' => '37', 'max' => ''],
    ];

    public function test_every_learner_gets_the_same_division_on_the_sheet_and_the_card_and_it_is_the_schools_own(): void
    {
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBePrivilege::class]);
        $class = SyntheticClass::build(['counting_sets' => [1]]);

        $this->actingAs($class->admin)->post('/admin/reports/cards/progressive-rules', [
            'standard_id' => $class->standard->id, 'ranking_basis' => 'aggregate', 'ranges' => self::RANGES,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $ranges = $this->ranges();
        $scoring = app(ReportScoring::class);
        $sheet = app(ResultsService::class)->sheet(
            new ResultsQuery(schoolId: (int) $class->school->id, sectionId: (int) $class->section->id, academicTermId: (int) $class->term->id),
            $scoring->policy((int) $class->school->id, (int) $class->standard->id, $scoring->countsMissingAsZero((int) $class->school->id, (int) $class->section->id, (int) $class->term->id)),
        );

        $differsFromDefault = 0;
        foreach (array_keys($class->learners) as $kls) {
            $want = $class->expected($kls);
            $data = $class->cardData($kls);
            $row = $sheet->student((int) $class->learners[$kls]->id);

            foreach ($want['sets'] as $setIndex => $set) {
                $expected = GradingRulesKit::divisionForAggregate($ranges, $set['aggregate']);
                $this->assertSame($expected, (string) $data['setColumns'][$setIndex]['division'], "{$kls} set ".($setIndex + 1).' on the card');
                if ($expected !== $set['division']) {
                    $differsFromDefault++;
                }
            }
            // The counting set (the second) is the term: the sheet and the card's term block agree.
            $termExpected = GradingRulesKit::divisionForAggregate($ranges, $want['sets'][1]['aggregate']);
            $this->assertSame($termExpected, $row->division, "{$kls} on the results sheet (missing and ABS as 0)");
            $this->assertSame($termExpected, (string) $data['eotDivision'], "{$kls} term division on the card");
        }

        $this->assertGreaterThan(20, $differsFromDefault, 'the school\'s ranges really differ from the app\'s, so a built-in table would be caught');
    }

    /**
     * KNOWN GAP found by this test. Screens that call ResultsService::sheet() with no policy (the class
     * marks page, the parent portal, the dashboard, WhatsApp results, exports) still skip a blank or ABS
     * mark instead of counting it as 0, so a learner with an ABS gets a lower aggregate, a better division
     * and a different position than on the card, and other learners' positions move with it. Decision 5
     * says a missing mark and ABS are 0 everywhere. The results service and those screens are the marks
     * agent's, so this is reported, not fixed here; remove the incomplete marker when the default policy
     * counts them as 0.
     */
    public function test_screens_that_build_the_sheet_with_no_policy_agree_with_the_card(): void
    {
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBePrivilege::class]);
        $class = SyntheticClass::build(['counting_sets' => [1]]);

        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: (int) $class->school->id, sectionId: (int) $class->section->id, academicTermId: (int) $class->term->id,
        ));

        $aggregate = $division = $position = 0;
        foreach (array_keys($class->learners) as $kls) {
            $want = $class->expected($kls)['sets'][1];
            $row = $sheet->student((int) $class->learners[$kls]->id);
            $aggregate += $row->aggregate !== $want['aggregate'] ? 1 : 0;
            $division += $row->division !== $want['division'] ? 1 : 0;
            $position += $row->classPosition !== $want['position'] ? 1 : 0;
        }

        $this->assertSame(0, $aggregate, 'the sheet counts a blank the same way the card does');
        $this->assertSame(0, $division);
        $this->assertSame(0, $position);
    }

    public function test_the_division_in_the_pdf_text_is_the_schools_own(): void
    {
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBePrivilege::class]);
        $class = SyntheticClass::build(['counting_sets' => [1], 'learners' => 76]);
        $this->actingAs($class->admin)->post('/admin/reports/cards/progressive-rules', [
            'standard_id' => $class->standard->id, 'ranking_basis' => 'aggregate', 'ranges' => self::RANGES,
        ])->assertRedirect();
        $ranges = $this->ranges();

        foreach (['KLS9000001', 'KLS9000010', 'KLS9000041', 'KLS9000060', 'KLS9000076'] as $kls) {
            $want = $class->expected($kls)['sets'];
            $text = preg_replace('/\s+/', ' ', $class->pdfText($kls));
            $first = GradingRulesKit::divisionForAggregate($ranges, $want[0]['aggregate']);
            $second = GradingRulesKit::divisionForAggregate($ranges, $want[1]['aggregate']);

            $this->assertStringContainsString("DIVISION {$first} {$second} ", $text.' ', "{$kls} division row");
        }
    }

    /** @return list<array{label: string, min: ?int, max: ?int}> */
    private function ranges(): array
    {
        return array_map(fn ($r) => ['label' => $r['label'], 'min' => $r['min'] === '' ? null : (int) $r['min'], 'max' => $r['max'] === '' ? null : (int) $r['max']], self::RANGES);
    }
}
