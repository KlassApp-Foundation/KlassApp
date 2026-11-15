<?php

namespace Tests\Feature\Reports;

use App\Contracts\Results\ReportTemplates;
use App\Helpers\GradingHelper;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\Results\ResultCell;
use App\Services\StudentReportCardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The new look is a visual template. The same class, rendered both ways,
 * keeps every mark, grade, total, aggregate, division and position.
 */
class ProgressiveNewLookTest extends TestCase
{
    use RefreshDatabase;

    public function test_formal_stays_the_default(): void
    {
        $this->assertSame('Formal', StudentReportCardService::TEMPLATES['formal']['label']);
        $this->assertSame('Progressive (new look)', StudentReportCardService::TEMPLATES['progressive']['label']);
        $this->assertSame(
            'admin.marks.report-templates.formal',
            app(ReportTemplates::class)->resolve(null, 'formal')['view'],
        );
        $this->assertSame(
            'admin.marks.report-templates.progressive',
            app(ReportTemplates::class)->resolve('progressive', 'formal')['view'],
        );
    }

    public function test_both_templates_print_the_same_values(): void
    {
        [$school, $link, $learner] = $this->school();

        $captured = null;
        $viewName = null;
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, &$viewName, $pdf) {
            $captured = $data;
            $viewName = $view;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF');
        $this->app->instance('dompdf.wrapper', $pdf);

        $service = app(StudentReportCardService::class);
        $service->pdfForStudent((int) $school->id, $link, $learner);

        $this->assertSame('admin.marks.report-templates.formal', $viewName);
        $this->assertSame(['Closing paper', 'Opening paper'], array_column($captured['setColumns'], 'label'));
        $this->assertSame(['Opening paper'], array_column($captured['monthlySets'], 'label'));
        $this->assertSame(['Closing paper'], array_column($captured['termSets'], 'label'));

        $formal = view('admin.marks.report-templates.formal', $captured)->render();
        $progressive = view('admin.marks.report-templates.progressive', $captured)->render();

        $this->assertStringContainsString('MONTHLY RESULTS', $formal);
        $this->assertStringContainsString('END OF TERM', $formal);
        $this->assertStringNotContainsString('MONTHLY RESULTS', $progressive);
        $this->assertStringNotContainsString('END OF TERM', $progressive);
        $this->assertStringNotContainsString('SAMPLE FOR APPROVAL', $progressive);
        $this->assertStringNotContainsString('Beginning of Term', $progressive);
        $this->assertStringNotContainsString('Mid Term', $progressive);
        $this->assertStringNotContainsString((string) $school->phone, $progressive);
        $this->assertStringContainsString('Riverbank Primary', $progressive);
        $this->assertStringContainsString('>RP<', $progressive);

        $closingAt = strpos($progressive, 'Closing paper');
        $openingAt = strpos($progressive, 'Opening paper');
        $this->assertNotFalse($closingAt);
        $this->assertTrue($closingAt < $openingAt);

        $values = $this->values($progressive);
        $this->assertNotSame([], $values);
        foreach ($values as $row) {
            if (! in_array($row['kind'], ['mark', 'grade', 'total', 'aggregate', 'division', 'position'], true)) {
                continue;
            }
            $this->assertNotSame('', $row['text']);
            if (in_array($row['kind'], ['mark', 'grade', 'total', 'division'], true)) {
                $this->assertStringContainsString('>'.$row['text'].'<', $formal, $row['kind'].' '.$row['text'].' for '.$row['set']);
            }
            if ($row['kind'] === 'position') {
                $ordinal = strstr($row['text'], ' of ', true) ?: $row['text'];
                $this->assertStringContainsString('>'.$ordinal.'<', $formal);
            }
        }

        $closingAggregate = null;
        foreach ($captured['setColumns'] as $set) {
            if ($set['label'] === 'Closing paper') {
                $closingAggregate = $set['aggregate'];
            }
        }
        $this->assertSame($closingAggregate, $captured['eotAgg']);
        $this->assertStringContainsString('>'.$captured['eotAgg'].'<', $formal);
        $this->assertStringContainsString('>'.$captured['eotAgg'].'<', $progressive);
        $this->assertStringNotContainsString('>11<', $progressive);
        $this->assertStringNotContainsString('>22<', $progressive);
        $this->assertStringNotContainsString('>33<', $progressive);
        $this->assertStringNotContainsString('>44<', $progressive);

        $sample = $captured;
        $sample['sampleForApproval'] = true;
        $sampleHtml = view('admin.marks.report-templates.progressive', $sample)->render();
        $this->assertStringContainsString('SAMPLE FOR APPROVAL', $sampleHtml);
        $this->assertStringContainsString('data-kind="grading-key"', $sampleHtml);
        $this->assertStringContainsString('12 or less', $sampleHtml);
        $this->assertStringNotContainsString('4–12', $sampleHtml);
        $band = $captured['grading_system']->sortBy('min_score')->first();
        $this->assertStringContainsString((int) $band->min_score.'–'.(int) $band->max_score, $sampleHtml);
        foreach ($values as $row) {
            if (in_array($row['kind'], ['mark', 'grade', 'total', 'aggregate', 'division'], true)) {
                $this->assertStringContainsString('>'.$row['text'].'<', $sampleHtml);
            }
        }

        $first = $captured;
        $school->forceFill(['report_template' => 'progressive'])->save();
        $viewName = null;
        $service->pdfForStudent((int) $school->id, $link, $learner->fresh());
        $this->assertSame('admin.marks.report-templates.progressive', $viewName);
        $this->assertSame(
            array_column($first['setColumns'], 'total'),
            array_column($captured['setColumns'], 'total'),
        );
        $this->assertSame($first['eotAgg'], $captured['eotAgg']);
        $this->assertSame($first['eotDivision'], $captured['eotDivision']);
    }

    public function test_six_subjects_and_three_sets_stay_on_one_page(): void
    {
        $subjects = [];
        $marks = collect();
        for ($i = 1; $i <= 6; $i++) {
            $subject = new Subject(['name' => 'Subject '.$i]);
            $subject->id = $i;
            $subjects[] = $subject;
            $marks->push(new Marks(['subject_id' => $i, 'marks' => 50 + $i]));
        }
        $learner = new User(['name' => 'Lina Learner', 'registration_number' => 'KLS0096301']);
        $learner->setRelation('marks', $marks);

        $sets = [];
        foreach (['First paper', 'Second paper', 'Third paper'] as $index => $label) {
            $cells = [];
            foreach ($subjects as $subject) {
                $cells[$subject->id] = new ResultCell($subject->id, 50 + $index, 'C5', 5, ResultCell::MARKED);
            }
            $sets[] = [
                'label' => $label,
                'cells' => $cells,
                'total' => 330 + $index,
                'aggregate' => 30,
                'pos' => 4,
                'of' => 20,
                'division' => '4',
            ];
        }

        $bands = collect([
            (object) ['min_score' => 0, 'max_score' => 39, 'points' => 9, 'grade' => '9'],
            (object) ['min_score' => 80, 'max_score' => 100, 'points' => 1, 'grade' => '1'],
        ]);
        $data = [
            'learner' => $learner,
            'subjects' => collect($subjects),
            'setColumns' => $sets,
            'monthlySets' => [],
            'termSets' => [$sets[2]],
            'grading_system' => $bands,
            'countMissingAsZero' => true,
            'rankingBasis' => 'aggregate',
            'divisionRanges' => \App\Services\Reports\ProgressiveRules::defaultDivisionRanges(),
            'schoolIdentity' => ['name' => 'Riverbank Primary', 'motto' => 'Learn widely', 'phones' => ['+999000'], 'phones_line' => 'Tel: +999000'],
            'class_name' => 'P.5',
            'streamName' => '',
            'totalLearners' => 20,
            'showAgg' => true,
            'showDivision' => true,
            'total' => 330,
            'eotAgg' => 30,
            'eotDivision' => '4',
            'teacherComment' => '',
            'headTeacherComment' => '',
            'nextTerm' => null,
            'sampleForApproval' => false,
            'logoPath' => null,
        ];

        $html = view('admin.marks.report-templates.progressive', $data)->render();
        $this->assertStringNotContainsString('+999000', $html);
        $this->assertStringNotContainsString('SAMPLE FOR APPROVAL', $html);
        $this->assertStringContainsString('Learn widely', $html);
        $this->assertStringContainsString('KLS0096301', $html);
        $this->assertSame(1, $this->pages($html));

        $data['sampleForApproval'] = true;
        $data['learner'] = new User(['name' => 'No Number', 'registration_number' => '']);
        $data['learner']->setRelation('marks', $marks);
        $sample = view('admin.marks.report-templates.progressive', $data)->render();
        $this->assertStringContainsString('SAMPLE FOR APPROVAL', $sample);
        $this->assertStringNotContainsString('KLS number', $sample);
        $this->assertSame(1, $this->pages($sample));
    }

    /**
     * @return array{0: School, 1: StandardLink, 2: User}
     */
    private function school(): array
    {
        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('exam_types')->insert([
            ['id' => 4, 'name' => 'Opening paper', 'code' => 'OPN', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'Closing paper', 'code' => 'CLP', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Riverbank Primary', 'slug' => 'riverbank-'.uniqid(), 'email' => uniqid().'@riverbank.test',
            'phone' => '073'.random_int(1000000, 9999999), 'status' => 1, 'report_template' => 'formal',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        GradingHelper::seedGradingForSchool((int) $school->id, 'primary');
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.5', 'status' => 1]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $school->id, 'email' => uniqid().'@riverbank-teacher.test', 'status' => 'active',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'class_teacher_id' => $teacher->id, 'stream' => 'A', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term I',
            'status' => 'current', 'starts_on' => '2026-02-02', 'ends_on' => '2026-04-30',
        ]);
        $english = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);
        $maths = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'name' => 'Mathematics', 'code' => 'MTH', 'type' => 'core', 'status' => 1,
        ]);
        $learner = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $school->id, 'name' => 'Amina Kato',
            'email' => uniqid().'@riverbank-learner.test', 'status' => 'active', 'registration_number' => 'KLS0093111',
        ]);
        $other = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $school->id, 'name' => 'Ben Kato',
            'email' => uniqid().'@riverbank-other.test', 'status' => 'active', 'registration_number' => 'KLS0093112',
        ]);
        foreach ([$learner, $other] as $student) {
            StudentAcademic::create([
                'school_id' => $school->id, 'academic_year_id' => $year->id,
                'user_id' => $student->id, 'standardLink_id' => $link->id,
            ]);
        }

        $plan = [
            [4, '2026-04-15 09:00:00', $english, 55, 11, false],
            [4, '2026-04-15 09:00:00', $maths, null, 22, false],
            [8, '2026-03-01 09:00:00', $english, null, 33, true],
            [8, '2026-03-01 09:00:00', $maths, 72, 44, false],
        ];
        foreach ($plan as [$typeId, $when, $subject, $score, $otherScore, $absent]) {
            $exam = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
                'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'exam_type_id' => $typeId,
                'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
                'scheduled_at' => $when, 'status' => 'submitted',
            ]));
            if ($score !== null || $absent) {
                Marks::create([
                    'student_id' => $learner->id, 'exam_id' => $exam->id, 'school_id' => $school->id,
                    'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id,
                    'marks' => $absent ? null : $score, 'grade' => $absent ? 'AB' : 'stored', 'absent' => $absent,
                ]);
            }
            Marks::create([
                'student_id' => $other->id, 'exam_id' => $exam->id, 'school_id' => $school->id,
                'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id,
                'marks' => $otherScore, 'grade' => 'stored', 'absent' => false,
            ]);
        }

        return [$school, $link, $learner];
    }

    /**
     * @return list<array{kind: string, set: string, subject: string, text: string}>
     */
    private function values(string $html): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $rows = [];
        foreach ($dom->getElementsByTagName('*') as $node) {
            if (! $node->hasAttribute('data-kind')) {
                continue;
            }
            $rows[] = [
                'kind' => $node->getAttribute('data-kind'),
                'set' => $node->getAttribute('data-set'),
                'subject' => $node->getAttribute('data-subject'),
                'text' => trim(preg_replace('/\s+/', ' ', $node->textContent) ?? ''),
            ];
        }

        return $rows;
    }

    private function pages(string $html): int
    {
        $fontDir = storage_path('fonts');
        if (! is_dir($fontDir)) {
            mkdir($fontDir, 0755, true);
        }
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        $pdf->output();

        return $pdf->getDomPDF()->getCanvas()->get_page_count();
    }
}
