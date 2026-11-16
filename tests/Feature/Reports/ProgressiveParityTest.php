<?php

namespace Tests\Feature\Reports;

use App\Helpers\GradingHelper;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\Reports\ReportScoring;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The expected points, aggregate, division and position are worked out in
 * this file from the progressive rules. The class of 40 is then run through
 * the report code, and every value has to match.
 *
 * A blank or an absence is 0, which is grade 9 and 9 points. Division is
 * 12 or less = 1, 13 to 24 = 2, 25 to 28 = 3, 29 to 32 = 4, 33 or more = U.
 * Aggregate ranking is lowest first. Total ranking is highest first.
 * A tie shares the place and the next place is skipped.
 */
class ProgressiveParityTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{grade: string, points: int, min: int, max: int}> */
    private const BANDS = [
        ['grade' => 'D1', 'points' => 1, 'min' => 95, 'max' => 100],
        ['grade' => 'D2', 'points' => 2, 'min' => 85, 'max' => 94],
        ['grade' => 'C3', 'points' => 3, 'min' => 75, 'max' => 84],
        ['grade' => 'C4', 'points' => 4, 'min' => 65, 'max' => 74],
        ['grade' => 'C5', 'points' => 5, 'min' => 60, 'max' => 64],
        ['grade' => 'C6', 'points' => 6, 'min' => 50, 'max' => 59],
        ['grade' => 'P7', 'points' => 7, 'min' => 45, 'max' => 49],
        ['grade' => 'P8', 'points' => 8, 'min' => 40, 'max' => 44],
        ['grade' => 'F9', 'points' => 9, 'min' => 0, 'max' => 39],
    ];

    /** @var list<string> */
    private const CODES = ['ENG', 'MTH', 'SCI', 'SST'];

    public function test_forty_learners_match_an_independent_progressive_calculation(): void
    {
        $plans = $this->plans();
        $this->assertCount(40, $plans);

        $byAggregate = $this->expect($plans, false);
        $byTotal = $this->expect($plans, true);

        $this->assertSame(1, $byAggregate[1]['position']);
        $this->assertSame(1, $byAggregate[2]['position']);
        $this->assertSame(3, $byAggregate[3]['position']);
        $this->assertSame(4, $byAggregate[1]['aggregate']);
        $this->assertSame($byAggregate[14]['aggregate'], $byAggregate[15]['aggregate']);
        $this->assertSame(36, $byAggregate[14]['aggregate']);
        $this->assertSame('U', $byAggregate[14]['division']);
        $this->assertSame('U', $byAggregate[15]['division']);
        $this->assertSame($byAggregate[14]['position'], $byAggregate[15]['position']);
        $this->assertSame(60.0, $byAggregate[14]['total']);
        $this->assertSame(30.0, $byAggregate[15]['total']);
        $this->assertSame('absent', $byAggregate[15]['cells']['SCI']['status']);
        $this->assertSame('missing', $byAggregate[15]['cells']['SST']['status']);
        $this->assertSame(9, $byAggregate[15]['cells']['SCI']['points']);
        $this->assertSame('1', $byAggregate[6]['division']);
        $this->assertSame(12, $byAggregate[6]['aggregate']);
        $this->assertSame('2', $byAggregate[7]['division']);
        $this->assertSame(13, $byAggregate[7]['aggregate']);
        $this->assertSame('2', $byAggregate[8]['division']);
        $this->assertSame(24, $byAggregate[8]['aggregate']);
        $this->assertSame('3', $byAggregate[9]['division']);
        $this->assertSame(25, $byAggregate[9]['aggregate']);
        $this->assertSame('3', $byAggregate[10]['division']);
        $this->assertSame(28, $byAggregate[10]['aggregate']);
        $this->assertSame('4', $byAggregate[11]['division']);
        $this->assertSame(29, $byAggregate[11]['aggregate']);
        $this->assertSame('4', $byAggregate[12]['division']);
        $this->assertSame(32, $byAggregate[12]['aggregate']);
        $this->assertSame('U', $byAggregate[13]['division']);
        $this->assertSame(33, $byAggregate[13]['aggregate']);
        $this->assertLessThan($byTotal[15]['position'], $byTotal[14]['position']);
        $this->assertNotSame($byTotal[14]['position'], $byTotal[15]['position']);

        $built = $this->school($plans);
        $this->assertMatchesReal($built['aggregate'], $byAggregate, true);
        $this->assertMatchesReal($built['total'], $byTotal, false);
    }

    /**
     * @param  array<int, array{marks: array<string, int|string|null>}>  $plans
     * @return array<int, array{aggregate: int, division: string, total: float, position: int, cells: array<string, array{mark: ?float, points: int, grade: string, status: string}>}>
     */
    private function expect(array $plans, bool $higherIsBetter): array
    {
        $rows = [];
        foreach ($plans as $number => $plan) {
            $points = 0;
            $total = 0.0;
            $cells = [];
            foreach (self::CODES as $code) {
                $raw = $plan['marks'][$code];
                if ($raw === null || $raw === 'ABS') {
                    $cells[$code] = [
                        'mark' => null,
                        'points' => 9,
                        'grade' => 'F9',
                        'status' => $raw === null ? 'missing' : 'absent',
                    ];
                    $points += 9;
                    continue;
                }
                $band = $this->band((float) $raw);
                $cells[$code] = [
                    'mark' => (float) $raw,
                    'points' => $band['points'],
                    'grade' => $band['grade'],
                    'status' => 'marked',
                ];
                $points += $band['points'];
                $total += (float) $raw;
            }
            $rows[$number] = [
                'aggregate' => $points,
                'division' => $this->division($points),
                'total' => $total,
                'cells' => $cells,
                'key' => $higherIsBetter ? $total : (float) $points,
            ];
        }

        foreach ($this->positions($rows, $higherIsBetter) as $number => $position) {
            $rows[$number]['position'] = $position;
        }

        return $rows;
    }

    /**
     * @param  array<int, array{key: float}>  $rows
     * @return array<int, int>
     */
    private function positions(array $rows, bool $higherIsBetter): array
    {
        $sorted = [];
        foreach ($rows as $number => $row) {
            $sorted[] = ['number' => $number, 'key' => $row['key']];
        }
        usort($sorted, function (array $left, array $right) use ($higherIsBetter): int {
            $compared = $left['key'] <=> $right['key'];
            if ($higherIsBetter) {
                $compared = -$compared;
            }
            if ($compared !== 0) {
                return $compared;
            }

            return $left['number'] <=> $right['number'];
        });

        $map = [];
        $position = 0;
        $index = 0;
        $previous = null;
        foreach ($sorted as $entry) {
            $index++;
            if ($previous === null || $entry['key'] !== $previous) {
                $position = $index;
                $previous = $entry['key'];
            }
            $map[$entry['number']] = $position;
        }

        return $map;
    }

    /** @return array{grade: string, points: int, min: int, max: int} */
    private function band(float $mark): array
    {
        foreach (self::BANDS as $band) {
            if ($mark >= $band['min'] && $mark <= $band['max']) {
                return $band;
            }
        }

        $this->fail('No grade band covers '.$mark);
    }

    private function division(int $aggregate): string
    {
        if ($aggregate <= 12) {
            return '1';
        }
        if ($aggregate <= 24) {
            return '2';
        }
        if ($aggregate <= 28) {
            return '3';
        }
        if ($aggregate <= 32) {
            return '4';
        }

        return 'U';
    }

    private function markForPoints(int $points): int
    {
        foreach (self::BANDS as $band) {
            if ($band['points'] === $points) {
                return $band['min'];
            }
        }

        $this->fail('No mark for '.$points.' points');
    }

    /**
     * @return array<int, array{marks: array<string, int|string|null>}>
     */
    private function plans(): array
    {
        $hand = [
            1 => [1, 1, 1, 1],
            2 => [1, 1, 1, 1],
            3 => [1, 1, 1, 2],
            4 => [2, 2, 2, 2],
            5 => [2, 2, 2, 2],
            6 => [3, 3, 3, 3],
            7 => [3, 3, 3, 4],
            8 => [6, 6, 6, 6],
            9 => [6, 6, 6, 7],
            10 => [7, 7, 7, 7],
            11 => [7, 7, 7, 8],
            12 => [8, 8, 8, 8],
            13 => [8, 8, 8, 9],
            14 => [20, 10, 20, 10],
            15 => [20, 10, 'ABS', null],
        ];
        for ($number = 16; $number <= 40; $number++) {
            $hand[$number] = [9, 4, 4, 1 + (($number - 16) % 4)];
        }

        $plans = [];
        foreach ($hand as $number => $spec) {
            $marks = [];
            foreach (self::CODES as $index => $code) {
                $raw = $spec[$index];
                if (is_int($raw) && $raw >= 1 && $raw <= 9 && ! in_array($number, [14, 15], true)) {
                    $marks[$code] = $this->markForPoints($raw);
                    continue;
                }
                $marks[$code] = $raw;
            }
            $plans[$number] = ['marks' => $marks];
        }

        return $plans;
    }

    /**
     * @param  array<int, array{marks: array<string, int|string|null>}>  $plans
     * @return array{aggregate: array{school: School, standard: Standard, section: Section, link: StandardLink, term: AcademicTerm, subjects: array<string, Subject>, learners: array<int, User>}, total: array{school: School, standard: Standard, section: Section, link: StandardLink, term: AcademicTerm, subjects: array<string, Subject>, learners: array<int, User>}}
     */
    private function school(array $plans): array
    {
        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('exam_types')->insert([
            ['id' => 4, 'name' => 'Spare paper', 'code' => 'SPR', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Closing paper', 'code' => 'CLP', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Parity Primary', 'slug' => 'parity-'.uniqid(), 'email' => uniqid().'@parity.test',
            'phone' => '074'.random_int(1000000, 9999999), 'status' => 1, 'report_template' => 'formal',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $school->id, 'email' => uniqid().'@parity-teacher.test', 'status' => 'active',
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term I',
            'status' => 'current', 'starts_on' => '2026-02-02', 'ends_on' => '2026-04-30',
        ]);

        return [
            'aggregate' => $this->classFor($school, $year, $teacher, $term, $plans, 'primary', null, 'P.5', 'KLS0094', 'Agg'),
            'total' => $this->classFor($school, $year, $teacher, $term, $plans, 'primary_total', 'total', 'P.6', 'KLS0095', 'Tot'),
        ];
    }

    /**
     * @param  array<int, array{marks: array<string, int|string|null>}>  $plans
     * @return array{school: School, standard: Standard, section: Section, link: StandardLink, term: AcademicTerm, subjects: array<string, Subject>, learners: array<int, User>}
     */
    private function classFor(
        School $school,
        AcademicYear $year,
        User $teacher,
        AcademicTerm $term,
        array $plans,
        string $standardName,
        ?string $rankingBasis,
        string $sectionName,
        string $klsPrefix,
        string $namePrefix,
    ): array {
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => $standardName, 'order' => 1, 'status' => 1,
            'ranking_basis' => $rankingBasis,
        ]);
        foreach (self::BANDS as $band) {
            SchoolGradingSystem::create([
                'school_id' => $school->id,
                'standard_id' => $standard->id,
                'grade' => $band['grade'],
                'points' => $band['points'],
                'min_score' => $band['min'],
                'max_score' => $band['max'],
                'remark' => $band['grade'],
            ]);
        }
        $section = Section::create(['school_id' => $school->id, 'name' => $sectionName, 'status' => 1]);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'class_teacher_id' => $teacher->id, 'stream' => 'A', 'status' => 1,
        ]);
        $subjects = [];
        foreach (['ENG' => 'English', 'MTH' => 'Mathematics', 'SCI' => 'Science', 'SST' => 'Social Studies'] as $code => $name) {
            $subjects[$code] = Subject::create([
                'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
                'section_id' => $section->id, 'name' => $name, 'code' => $code, 'type' => 'core', 'status' => 1,
            ]);
        }

        $exams = [];
        foreach ($subjects as $code => $subject) {
            $exams[$code] = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
                'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'exam_type_id' => 6,
                'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
                'scheduled_at' => '2026-04-20 09:00:00', 'status' => 'submitted',
            ]));
            Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
                'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'exam_type_id' => 4,
                'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
                'scheduled_at' => '2026-02-10 09:00:00', 'status' => 'submitted',
            ]));
        }

        $learners = [];
        foreach ($plans as $number => $plan) {
            $learner = User::factory()->create([
                'usergroup_id' => 6,
                'school_id' => $school->id,
                'name' => $namePrefix.' '.sprintf('%02d', $number),
                'email' => uniqid().'@parity-learner.test',
                'status' => 'active',
                'registration_number' => $klsPrefix.sprintf('%03d', $number),
            ]);
            StudentAcademic::create([
                'school_id' => $school->id, 'academic_year_id' => $year->id,
                'user_id' => $learner->id, 'standardLink_id' => $link->id,
            ]);
            foreach (self::CODES as $code) {
                $raw = $plan['marks'][$code];
                if ($raw === null) {
                    continue;
                }
                $absent = $raw === 'ABS';
                Marks::create([
                    'student_id' => $learner->id, 'exam_id' => $exams[$code]->id, 'school_id' => $school->id,
                    'subject_id' => $subjects[$code]->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id,
                    'marks' => $absent ? null : $raw, 'grade' => $absent ? 'AB' : '', 'absent' => $absent,
                ]);
            }
            $learners[$number] = $learner;
        }

        return [
            'school' => $school,
            'standard' => $standard,
            'section' => $section,
            'link' => $link,
            'term' => $term,
            'subjects' => $subjects,
            'learners' => $learners,
        ];
    }

    /**
     * @param  array{school: School, standard: Standard, section: Section, link: StandardLink, term: AcademicTerm, subjects: array<string, Subject>, learners: array<int, User>}  $class
     * @param  array<int, array{aggregate: int, division: string, total: float, position: int, cells: array<string, array{mark: ?float, points: int, grade: string, status: string}>}>  $expected
     */
    private function assertMatchesReal(array $class, array $expected, bool $renderCards): void
    {
        $scoring = app(ReportScoring::class);
        $sheet = app(ResultsService::class)->sheet(
            new ResultsQuery(
                schoolId: (int) $class['school']->id,
                sectionId: (int) $class['section']->id,
                academicTermId: (int) $class['term']->id,
            ),
            $scoring->policy(
                (int) $class['school']->id,
                (int) $class['standard']->id,
                $scoring->countsMissingAsZero(
                    (int) $class['school']->id,
                    (int) $class['section']->id,
                    (int) $class['term']->id,
                ),
            ),
        );

        $this->assertSame(['Closing paper'], array_map(fn ($sitting) => $sitting->name, $sheet->sittings));

        foreach ($expected as $number => $want) {
            $row = $sheet->student((int) $class['learners'][$number]->id);
            $this->assertNotNull($row, 'Learner '.$number.' is on the sheet');
            $this->assertSame($want['aggregate'], $row->aggregate, 'Aggregate for learner '.$number);
            $this->assertSame($want['division'], $row->division, 'Division for learner '.$number);
            $this->assertSame($want['position'], $row->classPosition, 'Position for learner '.$number);
            $this->assertSame(40, $row->classOutOf);
            $this->assertSame(4, $row->subjectsCounted);
            $this->assertEqualsWithDelta($want['total'], (float) $row->total, 0.001, 'Total for learner '.$number);
            foreach (self::CODES as $code) {
                $cell = $row->cell((int) $class['subjects'][$code]->id);
                $this->assertNotNull($cell);
                $this->assertSame($want['cells'][$code]['status'], $cell->status, $code.' status for learner '.$number);
                $this->assertSame($want['cells'][$code]['points'], $cell->points, $code.' points for learner '.$number);
                $this->assertSame($want['cells'][$code]['grade'], $cell->grade, $code.' grade for learner '.$number);
                if ($want['cells'][$code]['mark'] === null) {
                    $this->assertNull($cell->mark);
                } else {
                    $this->assertEqualsWithDelta($want['cells'][$code]['mark'], (float) $cell->mark, 0.001);
                }
            }
        }

        if (! $renderCards) {
            return;
        }

        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $captured = null;
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = $data;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF');
        $this->app->instance('dompdf.wrapper', $pdf);

        $service = app(StudentReportCardService::class);
        foreach ($expected as $number => $want) {
            $captured = null;
            $service->pdfForStudent((int) $class['school']->id, $class['link'], $class['learners'][$number]);
            $this->assertSame($want['aggregate'], $captured['eotAgg'], 'Card aggregate for learner '.$number);
            $this->assertSame($want['division'], $captured['eotDivision'], 'Card division for learner '.$number);
            $this->assertSame($want['position'], $captured['myPos'], 'Card position for learner '.$number);
            $this->assertEqualsWithDelta($want['total'], (float) $captured['total'], 0.001);
            $this->assertSame(['Closing paper'], array_column($captured['termSets'], 'label'));
            $this->assertSame([], $captured['monthlySets']);
        }

        $captured = null;
        $service->pdfForStudent((int) $class['school']->id, $class['link'], $class['learners'][15]);
        $html = view('admin.marks.report-templates.formal', $captured)->render();
        $this->assertStringContainsString('Missing', $html);
        $this->assertStringContainsString('ABS', $html);
        $this->assertStringContainsString('F9', $html);
        $this->assertStringContainsString('Closing paper', $html);
        $this->assertStringNotContainsString('Spare paper', $html);
        $this->assertSame(36, $captured['eotAgg']);
        $this->assertSame('U', $captured['eotDivision']);
    }
}
