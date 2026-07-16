<?php

namespace Tests\Feature\Toshi;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\User;
use App\Services\StudentReportHelperService;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToshiReportCardPipelineFixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    private function createSchool(): \App\Models\School
    {
        return \App\Models\School::create([
            'name' => 'Test ' . uniqid(), 'slug' => 't-' . uniqid(),
            'email' => uniqid() . '@t.sch.ug', 'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
    }

    /** @test */
    public function exam_type_relationship_exists()
    {
        $school = $this->createSchool();
        $std = \App\Models\Standard::create(['school_id' => $school->id, 'name' => 'P4', 'order' => 1, 'status' => 1]);
        $examType = ExamType::create(['name' => 'Midterm', 'code' => 'MID']);

        $exam = Exam::create([
            'school_id' => $school->id, 'subject_id' => 1, 'standard_id' => $std->id,
            'section_id' => 1, 'academic_year_id' => 1, 'academic_term_id' => 1,
            'exam_type_id' => $examType->id, 'teacher_id' => 1, 'status' => 'undone',
        ]);

        $this->assertNotNull($exam->examType);
        $this->assertEquals('Midterm', $exam->examType->name);
        $this->assertEquals('MID', $exam->examType->code);
    }

    /** @test */
    public function scoped_eager_load_excludes_cross_term_marks()
    {
        $school = $this->createSchool();
        $year = \App\Models\AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $term1 = \App\Models\AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'T1', 'status' => 'current',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-04-01',
        ]);
        $term2 = \App\Models\AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'T2', 'status' => 'current',
            'starts_on' => '2026-05-01', 'ends_on' => '2026-08-01',
        ]);
        $section = \App\Models\Section::create(['school_id' => $school->id, 'name' => 'A', 'status' => 1]);
        $std = \App\Models\Standard::create(['school_id' => $school->id, 'name' => 'P4', 'order' => 1, 'status' => 1]);
        $subject = \App\Models\Subject::create([
            'school_id' => $school->id, 'standard_id' => $std->id, 'section_id' => $section->id,
            'academic_year_id' => $year->id, 'name' => 'Eng', 'type' => 'core', 'status' => 1,
        ]);
        ExamType::create(['id' => 1, 'name' => 'T', 'code' => 'T']);

        $studentId = \DB::table('users')->insertGetId([
            'school_id' => $school->id, 'usergroup_id' => 6, 'name' => 'Test Student',
            'email' => 'test' . uniqid() . '@t.sch.ug', 'password' => bcrypt('pw'),
            'status' => 'active', 'email_verified' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = \App\Models\User::find($studentId);

        // Term 1 exam + mark
        $exam1 = Exam::create([
            'school_id' => $school->id, 'subject_id' => $subject->id, 'standard_id' => $std->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id,
            'academic_term_id' => $term1->id, 'exam_type_id' => 1, 'teacher_id' => 1, 'status' => 'undone',
        ]);
        Marks::create([
            'student_id' => $student->id, 'exam_id' => $exam1->id, 'subject_id' => $subject->id,
            'section_id' => $section->id, 'school_id' => $school->id, 'teacher_id' => 1,
            'marks' => 50, 'grade' => 'D',
        ]);

        // Term 2 exam + mark
        $exam2 = Exam::create([
            'school_id' => $school->id, 'subject_id' => $subject->id, 'standard_id' => $std->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id,
            'academic_term_id' => $term2->id, 'exam_type_id' => 1, 'teacher_id' => 1, 'status' => 'undone',
        ]);
        Marks::create([
            'student_id' => $student->id, 'exam_id' => $exam2->id, 'subject_id' => $subject->id,
            'section_id' => $section->id, 'school_id' => $school->id, 'teacher_id' => 1,
            'marks' => 100, 'grade' => 'A',
        ]);

        // Verify baseline: user has marks in both terms
        $this->assertEquals(2, Marks::where('student_id', $student->id)->count());

        // Verify the join between marks and exams works
        $marksWithExams = \DB::table('marks')
            ->join('exams', 'marks.exam_id', '=', 'exams.id')
            ->where('marks.student_id', $student->id)
            ->where('exams.academic_term_id', $exam2->academic_term_id)
            ->where('exams.section_id', $exam2->section_id)
            ->count();
        $this->assertEquals(1, $marksWithExams, 'Should find 1 Term 2 mark via join');

        // Test the constrained eager load directly (not via learner())
        // This verifies the fix to the with() constraint works
        $userWithScopedMarks = \App\Models\User::with([
            'marks' => function ($q) use ($school, $exam2) {
                $q->with('exam')
                  ->whereHas('exam', function ($q2) use ($school, $exam2) {
                      $q2->forSchool($school->id)
                         ->where('section_id', $exam2->section_id)
                         ->where('academic_year_id', $exam2->academic_year_id)
                         ->where('academic_term_id', $exam2->academic_term_id);
                  });
            },
        ])->where('id', $student->id)->first();

        $this->assertNotNull($userWithScopedMarks);
        $this->assertCount(1, $userWithScopedMarks->marks,
            'Constrained eager load should only load Term 2 marks');
        $this->assertEquals(100, (int) $userWithScopedMarks->marks->sum('marks'));

        // Also test via learner() — this verifies the combined whereHas + with fix
        $helper = app(StudentReportHelperService::class);
        $learner = $helper->learner($school->id, $student, $exam2);
        $this->assertNotNull($learner);
        $this->assertCount(1, $learner->marks, 'learner() should only load Term 2 marks');
        $this->assertEquals(100, (int) $learner->marks->sum('marks'));
    }

    /** @test */
    public function enter_mark_updates_instead_of_duplicating()
    {
        $school = $this->createSchool();
        $std = \App\Models\Standard::create(['school_id' => $school->id, 'name' => 'P4', 'order' => 1, 'status' => 1]);
        $section = \App\Models\Section::create(['school_id' => $school->id, 'name' => 'A', 'status' => 1]);
        $year = \App\Models\AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $term = \App\Models\AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'T1', 'status' => 'current',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-04-01',
        ]);
        $subject = \App\Models\Subject::create([
            'school_id' => $school->id, 'standard_id' => $std->id, 'section_id' => $section->id,
            'academic_year_id' => $year->id, 'name' => 'Math', 'type' => 'core', 'status' => 1,
        ]);
        ExamType::create(['id' => 1, 'name' => 'T', 'code' => 'T']);

        $studentId = \DB::table('users')->insertGetId([
            'school_id' => $school->id, 'usergroup_id' => 6, 'name' => 'Stu',
            'email' => 'stu' . uniqid() . '@t.sch.ug', 'password' => bcrypt('pw'),
            'status' => 'active', 'email_verified' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $teacherId = \DB::table('users')->insertGetId([
            'school_id' => $school->id, 'usergroup_id' => 3, 'name' => 'Tch',
            'email' => 'tch' . uniqid() . '@t.sch.ug', 'password' => bcrypt('pw'),
            'status' => 'active', 'email_verified' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = \App\Models\User::find($studentId);
        $teacher = \App\Models\User::find($teacherId);

        $exam = Exam::create([
            'school_id' => $school->id, 'subject_id' => $subject->id, 'standard_id' => $std->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id,
            'academic_term_id' => $term->id, 'exam_type_id' => 1,
            'teacher_id' => $teacher->id, 'status' => 'undone',
        ]);

        $this->actingAs($teacher);
        ToshiActionService::$bypassConfirm = true;

        // First entry: mark = 60
        $r1 = ToshiActionService::enterMark($teacher, [
            'student_id' => $student->id, 'exam_id' => $exam->id, 'marks' => 60,
        ]);
        $this->assertTrue($r1['success']);
        $this->assertEquals(1, Marks::where('student_id', $student->id)->where('exam_id', $exam->id)->count());

        // Correction: mark = 75
        $r2 = ToshiActionService::enterMark($teacher, [
            'student_id' => $student->id, 'exam_id' => $exam->id, 'marks' => 75,
        ]);
        $this->assertTrue($r2['success']);
        $this->assertEquals(1, Marks::where('student_id', $student->id)->where('exam_id', $exam->id)->count(),
            'Correction must update existing row, not insert duplicate');
        $this->assertEquals(
            75,
            (int) Marks::where('student_id', $student->id)->where('exam_id', $exam->id)->value('marks'),
            'Updated mark should show corrected value'
        );
    }
}
