<?php

namespace Tests\Feature\Marks;

use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Toshi\TeacherActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarksPhase2EntryTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $subjectTeacher;

    private User $classTeacher;

    private User $peerTeacher;

    private User $student;

    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeTeacher::class);

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Phase 2 Marks School',
            'slug' => 'phase2-marks-'.uniqid(),
            'email' => uniqid().'@phase2.test',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
        ]);

        $this->subjectTeacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $this->school->id, 'name' => 'Subject Teacher',
        ]);
        $this->classTeacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $this->school->id, 'name' => 'Class Teacher',
        ]);
        $this->peerTeacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $this->school->id, 'name' => 'Peer Teacher',
        ]);
        $this->student = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $this->school->id,
            'name' => 'Amina Phase Two', 'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $this->student->id, 'school_id' => $this->school->id, 'usergroup_id' => 6,
            'firstname' => 'Amina', 'lastname' => 'Phase', 'status' => 'active',
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'name' => 'Term 1', 'status' => 'current',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30',
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->school->id, 'name' => 'P.5', 'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id,
            'class_teacher_id' => $this->classTeacher->id, 'stream' => 'A', 'status' => 1,
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'user_id' => $this->student->id, 'standardLink_id' => $link->id,
        ]);
        $subject = Subject::create([
            'school_id' => $this->school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
            'academic_year_id' => $year->id, 'name' => 'Mathematics', 'type' => 'core', 'status' => 1,
        ]);
        $type = ExamType::create(['name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 1]);
        SchoolGradingSystem::create([
            'school_id' => $this->school->id, 'standard_id' => $standard->id,
            'grade' => 'A', 'points' => 1, 'min_score' => 80, 'max_score' => 100, 'remark' => 'Excellent',
        ]);
        SchoolGradingSystem::create([
            'school_id' => $this->school->id, 'standard_id' => $standard->id,
            'grade' => 'C', 'points' => 3, 'min_score' => 0, 'max_score' => 79, 'remark' => 'Fair',
        ]);

        $this->exam = Exam::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id, 'academic_term_id' => $term->id,
            'standard_id' => $standard->id, 'section_id' => $section->id, 'subject_id' => $subject->id,
            'teacher_id' => $this->subjectTeacher->id, 'exam_type_id' => $type->id, 'status' => 'undone',
        ]);
    }

    public function test_entry_form_shows_the_saved_mark_and_stays_on_the_page(): void
    {
        $this->actingAs($this->subjectTeacher)
            ->post(route('teacher.exam.marks.save', $this->exam), [
                'marks' => [$this->student->id => 88],
            ])
            ->assertRedirect(route('teacher.exam.marks.enter', $this->exam))
            ->assertSessionHas('successmessage');

        $html = $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="88"', $html);
        $this->assertStringContainsString('data-testid="marks-submit-button"', $html);
        $this->assertSame(88, (int) Marks::where('exam_id', $this->exam->id)->where('student_id', $this->student->id)->value('marks'));
        $this->assertSame('A', Marks::where('exam_id', $this->exam->id)->where('student_id', $this->student->id)->value('grade'));
    }

    public function test_submit_for_review_is_absent_until_a_mark_is_saved(): void
    {
        $empty = $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('data-testid="marks-submit-button"', $empty);

        $this->actingAs($this->subjectTeacher)
            ->post(route('teacher.exam.marks.submit', $this->exam))
            ->assertRedirect(route('teacher.exam.marks.enter', $this->exam));
        $this->assertDatabaseCount('exam_marks_submissions', 0);
    }

    public function test_class_teacher_toshi_entry_writes_through_the_same_writer(): void
    {
        $saved = TeacherActionService::enterMarks($this->classTeacher, [
            'exam_id' => $this->exam->id,
            'student' => 'Amina Phase Two',
            'marks' => 81,
        ]);

        $this->assertTrue($saved['success'], $saved['message'] ?? '');
        $mark = Marks::where('exam_id', $this->exam->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($mark);
        $this->assertSame(81, (int) $mark->marks);
        $this->assertSame('A', $mark->grade);
        $this->assertSame((int) $this->subjectTeacher->id, (int) $mark->teacher_id);
        $this->assertSame((int) $this->school->id, (int) $mark->school_id);
    }

    public function test_a_peer_teacher_cannot_enter_a_mark_through_toshi(): void
    {
        $refused = TeacherActionService::enterMarks($this->peerTeacher, [
            'exam_id' => $this->exam->id,
            'student' => 'Amina Phase Two',
            'marks' => 40,
        ]);

        $this->assertFalse($refused['success']);
        $this->assertSame(0, Marks::count());
    }
}
