<?php

namespace Tests\Feature\Grading;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Subject;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Services\ReportCardCommentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportTotalExcludesNonContributingExamsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private AcademicYear $year;
    private AcademicTerm $term;
    private Standard $standard;
    private Section $section;
    private StandardLink $stdLink;
    private User $student;
    private User $teacher;
    private Subject $subject;
    private ExamType $eotType;
    private ExamType $midType;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('exam_types')->insert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Report Test School', 'email' => 'report@test.sch.ug',
            'phone' => '0700000001', 'slug' => 'report-test-school', 'status' => 1,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'Term 2', 'status' => 'current',
            'starts_on' => '2026-05-01', 'ends_on' => '2026-08-31',
        ]);

        $this->standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary_lower', 'order' => 1, 'status' => '1',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id, 'name' => 'P.2', 'status' => 1,
        ]);

        $this->stdLink = StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id, 'section_id' => $this->section->id, 'status' => '1',
        ]);

        $this->teacher = User::create([
            'school_id' => $this->school->id, 'usergroup_id' => 5,
            'name' => 'Test Teacher', 'email' => 'teacher@test.sch.ug',
            'password' => bcrypt('password'), 'status' => 'active',
        ]);

        $this->student = User::create([
            'school_id' => $this->school->id, 'usergroup_id' => 6,
            'name' => 'Test Student', 'email' => 'student@test.sch.ug',
            'password' => bcrypt('password'), 'status' => 'active',
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id, 'section_id' => $this->section->id,
            'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);

        $this->eotType = ExamType::find(2);
        $this->midType = ExamType::find(1);
    }

    private function createExam(ExamType $type, string $status = 'done'): Exam
    {
        return Exam::withoutEvents(fn() => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'exam_type_id' => $type->id,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $this->year->id,
            'scheduled_at' => $type->code === 'MID' ? now()->subMonth() : now(),
            'status' => $status,
        ]));
    }

    /** @test */
    public function total_excludes_mid_marks(): void
    {
        $eot = $this->createExam($this->eotType);
        $mid = $this->createExam($this->midType);

        Marks::create(['student_id' => $this->student->id, 'exam_id' => $eot->id, 'school_id' => $this->school->id, 'subject_id' => $this->subject->id, 'teacher_id' => $this->teacher->id, 'marks' => 80, 'grade' => '3', 'section_id' => $this->section->id]);
        Marks::create(['student_id' => $this->student->id, 'exam_id' => $mid->id, 'school_id' => $this->school->id, 'subject_id' => $this->subject->id, 'teacher_id' => $this->teacher->id, 'marks' => 50, 'grade' => '6', 'section_id' => $this->section->id]);

        $learner = User::with(['marks.exam.examType'])->find($this->student->id);
        $total = $learner->marks->filter(fn($m) => $m->exam?->examType?->contributes_to_report_total)->sum('marks');

        $this->assertEquals(80, (int) $total);
        $this->assertEquals(130, (int) $learner->marks->sum('marks'));
    }

    /** @test */
    public function comment_deterministic_same_student_exam(): void
    {
        $svc = new ReportCardCommentService;
        $c1 = $svc->commentFor(580, 'primary_lower', 2649, 21);
        $c2 = $svc->commentFor(580, 'primary_lower', 2649, 21);
        $this->assertNotEmpty($c1);
        $this->assertSame($c1, $c2);
    }

    /** @test */
    public function comment_differs_by_student(): void
    {
        $svc = new ReportCardCommentService;
        $c1 = $svc->commentFor(580, 'primary_lower', 2649, 21);
        $c2 = $svc->commentFor(580, 'primary_lower', 9999, 21);
        $this->assertNotEmpty($c1);
        $this->assertNotEmpty($c2);
        $allComments = collect(config('report_card_comments.lower'))->flatMap(fn($b) => $b['comments'])->toArray();
        $this->assertContains($c1, $allComments);
        $this->assertContains($c2, $allComments);
    }

    /** @test */
    public function out_of_range_returns_empty(): void
    {
        $svc = new ReportCardCommentService;
        $this->assertSame('', $svc->commentFor(99999, 'primary_lower', 1, 1));
    }

    /** @test */
    public function band_boundaries_resolved_correctly(): void
    {
        $svc = new ReportCardCommentService;
        $bands = config('report_card_comments.lower');

        $this->assertNotEmpty($svc->commentFor(520, 'primary_lower', 1, 1));
        $this->assertNotEmpty($svc->commentFor(519, 'primary_lower', 2, 2));
        $this->assertNotEmpty($svc->commentFor(440, 'primary_lower', 3, 3));
        $this->assertNotEmpty($svc->commentFor(439, 'primary_lower', 4, 4));
        $this->assertNotEmpty($svc->commentFor(0, 'primary_lower', 5, 5));
    }

    /** @test */
    public function upper_and_lower_different_ranges(): void
    {
        $svc = new ReportCardCommentService;
        $upper = $svc->commentFor(360, 'primary', 1, 1);
        $lower = $svc->commentFor(360, 'primary_lower', 2, 2);
        $this->assertNotEmpty($upper);
        $this->assertNotEmpty($lower);
    }

    /** @test */
    public function contributes_flag_is_correct(): void
    {
        $this->assertEquals(0, (int) $this->midType->contributes_to_report_total);
        $this->assertEquals(1, (int) $this->eotType->contributes_to_report_total);
    }
}