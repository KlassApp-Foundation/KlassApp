<?php

namespace Tests\Feature\Reports;

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
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Report-card totals, mid-term rows and positions come from ResultsService.
 */
class ReportCardOnResultsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_mid_term_row_totals_every_subject_in_the_sitting(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $student = $this->student($ctx, 'Mid Student');
        $midEnglish = $this->exam($ctx, $term, $ctx['english'], '2026-02-01', 1);
        $midMaths = $this->exam($ctx, $term, $ctx['maths'], '2026-02-02', 1);
        $end = $this->exam($ctx, $term, $ctx['english'], '2026-04-01', 2);
        $this->mark($ctx, $midEnglish, $ctx['english'], $student, 40);
        $this->mark($ctx, $midMaths, $ctx['maths'], $student, 30);
        $this->mark($ctx, $end, $ctx['english'], $student, 80);

        $captured = $this->render($ctx, $student);
        $sitting = $captured['midSittings'][0] ?? null;

        $this->assertNotNull($sitting);
        $this->assertSame(70.0, $sitting['total']);
        $this->assertSame(40.0, $sitting['cells'][$ctx['english']->id]->mark);
        $this->assertSame(30.0, $sitting['cells'][$ctx['maths']->id]->mark);
    }

    public function test_stream_position_uses_the_same_ranking_as_the_class(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        SchoolGradingSystem::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'grade' => 'F',
            'points' => 9,
            'min_score' => 0,
            'max_score' => 79,
            'remark' => 'F',
        ]);
        SchoolGradingSystem::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'grade' => 'D',
            'points' => 1,
            'min_score' => 80,
            'max_score' => 100,
            'remark' => 'D',
        ]);

        $lowerPoints = $this->student($ctx, 'Lower Points');
        $higherTotal = $this->student($ctx, 'Higher Total');
        $english = $this->exam($ctx, $term, $ctx['english'], '2026-04-01', 2);
        $maths = $this->exam($ctx, $term, $ctx['maths'], '2026-04-02', 2);
        $this->mark($ctx, $english, $ctx['english'], $lowerPoints, 90);
        $this->mark($ctx, $maths, $ctx['maths'], $lowerPoints, 10);
        $this->mark($ctx, $english, $ctx['english'], $higherTotal, 60);
        $this->mark($ctx, $maths, $ctx['maths'], $higherTotal, 60);

        $captured = $this->render($ctx, $lowerPoints);

        $this->assertSame(10, $captured['eotAgg']);
        $this->assertSame(1, $captured['myPos']);
        $this->assertSame(1, $captured['streamPos']);
        $this->assertSame(2, $captured['streamTotal']);
    }

    private function render(array $ctx, User $student): array
    {
        $captured = null;
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = $data;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF-1.4 report');
        $this->app->instance('dompdf.wrapper', $pdf);

        app(StudentReportCardService::class)->pdfForStudent($ctx['school']->id, $ctx['link'], $student);

        $this->assertNotNull($captured);

        return $captured;
    }

    /**
     * @return array{school: School, year: AcademicYear, standard: Standard, section: Section, link: StandardLink, teacher: User, english: Subject, maths: Subject}
     */
    private function school(): array
    {
        $school = School::create([
            'name' => 'Results Card School',
            'email' => 'results-card-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'results-card-'.uniqid(),
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
            'stream' => 'East',
            'status' => 1,
        ]);
        $english = Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);
        $maths = Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'type' => 'core',
            'status' => 1,
        ]);

        return compact('school', 'year', 'standard', 'section', 'link', 'teacher', 'english', 'maths');
    }

    private function student(array $ctx, string $name): User
    {
        $user = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $ctx['school']->id,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@t.sch.ug',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'user_id' => $user->id,
            'standardLink_id' => $ctx['link']->id,
        ]);

        return $user;
    }

    private function term(array $ctx): AcademicTerm
    {
        return AcademicTerm::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'name' => 'Term I',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);
    }

    private function exam(array $ctx, AcademicTerm $term, Subject $subject, string $when, int $examTypeId): Exam
    {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'exam_type_id' => $examTypeId,
            'academic_term_id' => $term->id,
            'academic_year_id' => $ctx['year']->id,
            'scheduled_at' => $when,
            'status' => 'submitted',
        ]));
    }

    private function mark(array $ctx, Exam $exam, Subject $subject, User $student, float $score): void
    {
        Marks::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'school_id' => $ctx['school']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'section_id' => $exam->section_id,
            'marks' => $score,
            'grade' => 'stored',
        ]);
    }
}
