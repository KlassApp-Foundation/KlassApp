<?php

namespace Tests\Feature\Reports;

use App\Helpers\GradingHelper;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\Marks\MarksSubmissionService;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Audit: the whole report pipeline at each level on top of the marks stack.
 *
 * enter marks → submit → approve → sheet → report card (formal and warm) →
 * release. The report card data is captured from the service, then the same
 * Blade view is rendered to HTML so the assertions look at what a family gets.
 */
class LevelPipelineAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    /** @return array<string, mixed> */
    private function level(string $standardName, string $gradingStyle, string $className, string $prefix): array
    {
        $school = School::create([
            'name' => $prefix . ' School',
            'email' => $prefix . '-' . uniqid() . '@t.sch.ug',
            'phone' => '07' . random_int(10000000, 99999999),
            'slug' => $prefix . '-' . uniqid(),
            'status' => 1,
            'report_template' => 'formal',
            'registration_country' => 'Uganda',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term III',
            'status' => 'current', 'starts_on' => '2026-08-24', 'ends_on' => '2026-11-28',
        ]);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => $standardName, 'order' => 1, 'status' => 1,
            'grading_style' => $gradingStyle ?: null,
        ]);
        $section = Section::create(['school_id' => $school->id, 'name' => $className, 'status' => 1]);
        $teacher = User::factory()->create(['usergroup_id' => 5, 'school_id' => $school->id, 'email' => 't.' . uniqid() . '@t.sch.ug']);
        $admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $school->id, 'email' => 'a.' . uniqid() . '@t.sch.ug']);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'class_teacher_id' => $teacher->id, 'stream' => null, 'status' => 1,
        ]);

        GradingHelper::seedGradingForSchool((int) $school->id, (string) GradingHelper::levelTypeForStandard($standard));

        $subjects = [];
        foreach (['Mathematics', 'English', 'Science'] as $i => $name) {
            $subjects[] = Subject::create([
                'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
                'section_id' => $section->id, 'name' => $name, 'code' => strtoupper(substr($name, 0, 3)),
                'type' => 'core', 'status' => 1, 'report_order' => $i + 1,
            ]);
        }

        $student = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $school->id, 'name' => $prefix . ' Learner',
            'email' => 's.' . uniqid() . '@t.sch.ug', 'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'user_id' => $student->id, 'standardLink_id' => $link->id,
        ]);

        return compact('school', 'year', 'term', 'standard', 'section', 'link', 'teacher', 'admin', 'subjects', 'student');
    }

    /** @param array<string, mixed> $ctx */
    private function sitAndApprove(array $ctx): void
    {
        foreach ($ctx['subjects'] as $subject) {
            $exam = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $ctx['school']->id, 'standard_id' => $ctx['standard']->id,
                'section_id' => $ctx['section']->id, 'subject_id' => $subject->id,
                'teacher_id' => $ctx['teacher']->id, 'exam_type_id' => 2,
                'academic_term_id' => $ctx['term']->id, 'academic_year_id' => $ctx['year']->id,
                'scheduled_at' => '2026-11-20 09:00:00', 'status' => 'done',
            ]));
            Marks::create([
                'student_id' => $ctx['student']->id, 'exam_id' => $exam->id,
                'school_id' => $ctx['school']->id, 'subject_id' => $subject->id,
                'teacher_id' => $ctx['teacher']->id, 'section_id' => $ctx['section']->id,
                'marks' => 80, 'grade' => 'stored',
            ]);
            ExamMarksSubmission::create([
                'school_id' => $ctx['school']->id, 'exam_id' => $exam->id,
                'class_id' => $ctx['section']->id, 'subject_id' => $subject->id,
                'teacher_id' => $ctx['teacher']->id, 'status' => 'locked',
                'approval_status' => 'approved', 'submitted_at' => now(), 'approved_at' => now(),
            ]);
        }
    }

    /**
     * Capture the report card view name and data, then render that Blade to HTML.
     *
     * @param array<string, mixed> $ctx
     * @return array{html: string, data: array<string, mixed>}
     */
    private function card(array $ctx, string $template): array
    {
        $captured = null;
        $viewName = null;
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, &$viewName, $pdf) {
            $captured = $data;
            $viewName = $view;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF-1.4 report');
        $this->app->instance('dompdf.wrapper', $pdf);

        $ctx['school']->forceFill(['report_template' => $template])->save();
        app(StudentReportCardService::class)->pdfForStudent((int) $ctx['school']->id, $ctx['link'], $ctx['student'], null, null, $template);

        $this->assertNotNull($captured, 'the report card view was never built');

        return ['html' => view($viewName, $captured)->render(), 'data' => $captured];
    }

    public function test_nursery_report_card(): void
    {
        $ctx = $this->level('Nursery', '', 'Baby Class', 'Nursery');
        $this->sitAndApprove($ctx);
        $exam = Exam::where('school_id', $ctx['school']->id)->orderBy('id')->firstOrFail();
        foreach (['Literacy' => 'Excellent', 'Numeracy' => 'Good', 'Motor Skills' => 'Good', 'Social/Emotional' => 'Satisfactory'] as $domain => $rating) {
            \App\Models\Academics\NurseryAssessment::create([
                'student_id' => $ctx['student']->id,
                'academic_term_id' => $ctx['term']->id,
                'exam_id' => $exam->id,
                'domain' => $domain,
                'rating' => $rating,
                'remarks' => null,
            ]);
        }
        $card = $this->card($ctx, 'formal');

        $html = $card['html'];
        fwrite(STDERR, "\n[nursery] isNursery=" . var_export($card['data']['isNursery'], true)
            . ' showAgg=' . var_export($card['data']['showAgg'], true)
            . ' division=' . var_export($card['data']['eotDivision'], true)
            . ' pos=' . var_export($card['data']['myPos'], true)
            . ' total=' . var_export($card['data']['total'], true)
            . ' assessments=' . count($card['data']['nurseryAssessments'])
            . " htmlBytes=" . strlen($html) . "\n");

        $text = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertTrue($card['data']['isNursery']);
        $this->assertStringContainsString('Literacy', $text);
        $this->assertStringContainsString('Excellent', $text);
        $this->assertStringNotContainsString('DIVISION:', $text);
        $this->assertStringNotContainsString('POSITION', $text);
    }

    public function test_primary_report_card(): void
    {
        $ctx = $this->level('Primary', 'aggregate', 'P.4', 'Primary');
        $this->sitAndApprove($ctx);
        $card = $this->card($ctx, 'formal');

        fwrite(STDERR, "\n[primary] total=" . $card['data']['total']
            . ' division=' . var_export($card['data']['eotDivision'], true)
            . ' agg=' . var_export($card['data']['eotAgg'], true) . "\n");

        $text = preg_replace('/\s+/', ' ', strip_tags($card['html']));
        $this->assertStringContainsString('DIVISION: 1', $text);
        $this->assertSame(240.0, $card['data']['total']);
        $this->assertSame(9, $card['data']['eotAgg']);
    }

    public function test_o_level_report_card(): void
    {
        $ctx = $this->level('O-Level', 'aggregate', 'S.2', 'OLevel');
        $this->sitAndApprove($ctx);
        $card = $this->card($ctx, 'formal');

        fwrite(STDERR, "\n[o-level] grades=" . json_encode($card['data']['grade'])
            . ' division=' . var_export($card['data']['eotDivision'], true) . "\n");

        $text = preg_replace('/\s+/', ' ', strip_tags($card['html']));
        $this->assertStringNotContainsString('DIVISION:', $text);
    }

    public function test_a_level_report_card(): void
    {
        $ctx = $this->level('A-Level', 'aggregate', 'S.5', 'ALevel');
        $this->sitAndApprove($ctx);
        $card = $this->card($ctx, 'formal');

        fwrite(STDERR, "\n[a-level] agg=" . var_export($card['data']['eotAgg'], true)
            . ' division=' . var_export($card['data']['eotDivision'], true)
            . ' hasPointsText=' . var_export(str_contains(strtoupper($card['html']), 'POINT'), true) . "\n");

        $text = preg_replace('/\s+/', ' ', strip_tags($card['html']));
        $this->assertStringNotContainsString('DIVISION:', $text);
    }
}
