<?php

namespace Tests\Feature\Reports;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\MarksRelease;
use App\Models\ReportGeneration;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentReportCardService;
use App\Services\WhatsAppReportCardDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Report cards pick a term on purpose, keep a released term visible when a
 * newer exam is still unreleased, and total every contributing subject.
 */
class ReportCardTermReleaseAggregateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_a_term_is_the_current_term_or_the_requested_term_not_the_newest_exam(): void
    {
        $ctx = $this->school();
        $current = $this->term($ctx, 'Term I', 'current', '2026-01-01', '2026-04-30');
        $later = $this->term($ctx, 'Term II', 'next', '2026-05-01', '2026-08-31');
        $currentExam = $this->exam($ctx, $current, $ctx['english'], '2026-04-01');
        $laterExam = $this->exam($ctx, $later, $ctx['english'], '2026-08-01');
        $this->stampCreatedAt($currentExam, '2026-02-01 08:00:00');
        $this->stampCreatedAt($laterExam, '2026-06-01 08:00:00');

        $service = app(StudentReportCardService::class);

        $resolved = $service->resolveExam($ctx['school']->id, $ctx['link']);
        $this->assertNotNull($resolved);
        $this->assertSame($currentExam->id, $resolved->id);

        $explicit = $service->resolveExam($ctx['school']->id, $ctx['link'], $later->id);
        $this->assertNotNull($explicit);
        $this->assertSame($laterExam->id, $explicit->id);
    }

    public function test_a_batch_generation_uses_its_term_not_the_newest_exam(): void
    {
        $ctx = $this->school();
        $older = $this->term($ctx, 'Term I', 'last', '2026-01-01', '2026-04-30');
        $newer = $this->term($ctx, 'Term II', 'current', '2026-05-01', '2026-08-31');
        $olderExam = $this->exam($ctx, $older, $ctx['english'], '2026-04-01');
        $newerExam = $this->exam($ctx, $newer, $ctx['english'], '2026-08-01');
        $this->stampCreatedAt($olderExam, '2026-02-01 08:00:00');
        $this->stampCreatedAt($newerExam, '2026-06-01 08:00:00');
        $this->mark($ctx, $olderExam, $ctx['english'], 70);

        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnSelf();
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF-1.4 batch');
        $this->app->instance('dompdf.wrapper', $pdf);

        $generation = ReportGeneration::create([
            'school_id' => $ctx['school']->id,
            'standard_link_id' => $ctx['link']->id,
            'academic_term_id' => $older->id,
            'class_name' => 'P.4',
            'mode' => 'zip',
            'status' => 'pending',
            'requested_by' => $ctx['teacher']->id,
        ]);

        app(\App\Jobs\GenerateClassReportsJob::class, ['generationId' => $generation->id])->handle();

        $generation->refresh();
        $this->assertSame('completed', $generation->status, (string) $generation->error);
    }

    public function test_b_newer_unreleased_exam_does_not_hide_an_older_released_report(): void
    {
        $ctx = $this->school();
        $older = $this->term($ctx, 'Term I', 'last', '2026-01-01', '2026-04-30');
        $newer = $this->term($ctx, 'Term II', 'current', '2026-05-01', '2026-08-31');
        $olderExam = $this->exam($ctx, $older, $ctx['english'], '2026-04-01');
        $newerExam = $this->exam($ctx, $newer, $ctx['english'], '2026-08-01');
        $this->stampCreatedAt($olderExam, '2026-02-01 08:00:00');
        $this->stampCreatedAt($newerExam, '2026-06-01 08:00:00');
        $this->mark($ctx, $olderExam, $ctx['english'], 70);
        $this->mark($ctx, $newerExam, $ctx['english'], 80);
        $this->release($olderExam, $ctx['link']->id);

        $captured = null;
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = $data;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn("%PDF-1.4\nreport");
        $this->app->instance('dompdf.wrapper', $pdf);

        $delivery = app(WhatsAppReportCardDeliveryService::class);
        $result = $delivery->prepareForStudent($ctx['parent'], $ctx['student']);

        $this->assertTrue($result['ok'], $result['message'] ?? json_encode($result));
        $this->assertNotNull($captured);
        $termIds = $captured['learner']->marks
            ->map(fn ($mark) => (int) $mark->exam->academic_term_id)
            ->unique()
            ->values()
            ->all();
        $this->assertSame([$older->id], $termIds);

        $withheld = $delivery->prepareForStudent($ctx['parent'], $ctx['student'], $newer->id);
        $this->assertFalse($withheld['ok']);
        $this->assertSame('report_none', $withheld['flow_type']);

        $staff = app(StudentReportCardService::class)->resolveExam($ctx['school']->id, $ctx['link']);
        $this->assertSame($newerExam->id, $staff?->id);
    }

    public function test_c_end_of_term_aggregate_and_division_count_every_subject(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx, 'Term I', 'current', '2026-01-01', '2026-04-30');
        $englishExam = $this->exam($ctx, $term, $ctx['english'], '2026-04-01');
        $mathsExam = $this->exam($ctx, $term, $ctx['maths'], '2026-04-02');
        $this->mark($ctx, $englishExam, $ctx['english'], 70);
        $this->mark($ctx, $mathsExam, $ctx['maths'], 55);

        SchoolGradingSystem::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'grade' => '9',
            'points' => 9,
            'min_score' => 0,
            'max_score' => 100,
            'remark' => 'Fair',
        ]);

        $captured = null;
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = ['view' => $view, 'data' => $data];

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF-1.4 aggregate');
        $this->app->instance('dompdf.wrapper', $pdf);

        app(StudentReportCardService::class)->pdfForStudent($ctx['school']->id, $ctx['link'], $ctx['student']);

        $this->assertNotNull($captured);
        $this->assertSame(18, $captured['data']['eotAgg']);
        $this->assertSame('2', $captured['data']['eotDivision']);

        $html = view($captured['view'], $captured['data'])->render();
        $this->assertStringContainsString('ENGLISH', $html);
        $this->assertStringContainsString('MATHEMATICS', $html);
    }

    /**
     * @return array{school: School, year: AcademicYear, standard: Standard, section: Section, link: StandardLink, teacher: User, student: User, parent: User, english: Subject, maths: Subject}
     */
    private function school(): array
    {
        $school = School::create([
            'name' => 'Report Term School',
            'email' => 'report-term-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'report-term-'.uniqid(),
            'status' => 1,
            'report_template' => 'formal',
            'registration_country' => 'Uganda',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
            'grading_style' => 'aggregate',
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.4',
            'status' => 1,
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
            'email' => 'ct.'.uniqid().'@t.sch.ug',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacher->id,
            'status' => 1,
        ]);
        $english = $this->subject($school, $year, $standard, $section, 'English', 'ENG');
        $maths = $this->subject($school, $year, $standard, $section, 'Mathematics', 'MATH');
        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $school->id,
            'name' => 'Term Student',
            'email' => 'student.'.uniqid().'@t.sch.ug',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);
        $parent = User::factory()->create([
            'usergroup_id' => 7,
            'school_id' => null,
            'name' => 'Term Parent',
            'email' => 'parent.'.uniqid().'@t.sch.ug',
            'status' => 'active',
        ]);
        StudentParentLink::create([
            'school_id' => $school->id,
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => 1,
        ]);

        return compact('school', 'year', 'standard', 'section', 'link', 'teacher', 'student', 'parent', 'english', 'maths');
    }

    private function subject(School $school, AcademicYear $year, Standard $standard, Section $section, string $name, string $code): Subject
    {
        return Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => $name,
            'code' => $code,
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function term(array $ctx, string $name, string $status, string $starts, string $ends): AcademicTerm
    {
        return AcademicTerm::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'name' => $name,
            'status' => $status,
            'starts_on' => $starts,
            'ends_on' => $ends,
        ]);
    }

    private function exam(array $ctx, AcademicTerm $term, Subject $subject, string $when): Exam
    {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'exam_type_id' => 2,
            'academic_term_id' => $term->id,
            'academic_year_id' => $ctx['year']->id,
            'scheduled_at' => $when,
            'status' => 'submitted',
        ]));
    }

    private function mark(array $ctx, Exam $exam, Subject $subject, int $score): void
    {
        Marks::create([
            'student_id' => $ctx['student']->id,
            'exam_id' => $exam->id,
            'school_id' => $ctx['school']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'section_id' => $ctx['section']->id,
            'marks' => $score,
            'grade' => '9',
        ]);
    }

    private function release(Exam $exam, int $linkId): void
    {
        MarksRelease::create([
            'school_id' => $exam->school_id,
            'exam_id' => $exam->id,
            'scope' => 'class',
            'standard_link_id' => $linkId,
            'scheduled_at' => now()->subHour(),
            'released_at' => now(),
            'status' => MarksRelease::STATUS_RELEASED,
            'timezone' => 'UTC',
        ]);
    }

    private function stampCreatedAt(Exam $exam, string $at): void
    {
        DB::table('exams')->where('id', $exam->id)->update([
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
