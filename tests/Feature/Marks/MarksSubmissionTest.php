<?php

namespace Tests\Feature\Marks;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use App\Support\SidebarCounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarksSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $teacherA;

    private User $adminA;

    private Exam $examA;

    private Section $sectionA;

    private Subject $subjectA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('exam_types')->insert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0,
                'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = School::create([
            'name' => 'School A', 'slug' => 'sub-a',
            'email' => 'a@sub.test', 'phone' => '+256700000011',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $this->schoolB = School::create([
            'name' => 'School B', 'slug' => 'sub-b',
            'email' => 'b@sub.test', 'phone' => '+256700000012',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        foreach ([$this->schoolA, $this->schoolB] as $school) {
            $year = AcademicYear::create([
                'school_id' => $school->id, 'name' => '2026 Test',
                'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
            ]);
            AcademicTerm::create([
                'school_id' => $school->id, 'academic_year_id' => $year->id,
                'name' => 'Term 1', 'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30',
                'status' => 'current',
            ]);
            Standard::create([
                'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
            ]);
            Section::create([
                'school_id' => $school->id, 'name' => 'P.1', 'status' => 1,
            ]);
        }

        $this->teacherA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 5,
            'email' => 'teacher.a@sub.test', 'name' => 'Teacher A',
        ]);
        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 3,
            'email' => 'admin.a@sub.test', 'name' => 'Admin A',
        ]);

        $yearA = AcademicYear::where('school_id', $this->schoolA->id)->first();
        $termA = AcademicTerm::where('school_id', $this->schoolA->id)->first();
        $standardA = Standard::where('school_id', $this->schoolA->id)->first();
        $this->sectionA = Section::where('school_id', $this->schoolA->id)->first();
        $this->subjectA = Subject::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $yearA->id,
            'standard_id' => $standardA->id,
            'section_id' => $this->sectionA->id,
            'name' => 'Math A',
            'type' => 'core',
        ]);

        $this->examA = Exam::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $yearA->id,
            'academic_term_id' => $termA->id,
            'standard_id' => $standardA->id,
            'section_id' => $this->sectionA->id,
            'subject_id' => $this->subjectA->id,
            'teacher_id' => $this->teacherA->id,
            'exam_type_id' => 1,
            'status' => 'undone',
        ]);
    }

    public function test_owner_submit_writes_a_row_and_leaves_exam_status(): void
    {
        $this->savedMark();

        $response = $this->actingAs($this->teacherA)
            ->post(route('teacher.exam.marks.submit', $this->examA));

        $response->assertRedirect(route('teacher.exam.marks.view', $this->examA));

        $this->assertDatabaseHas('exam_marks_submissions', [
            'school_id' => $this->schoolA->id,
            'exam_id' => $this->examA->id,
            'class_id' => $this->sectionA->id,
            'subject_id' => $this->subjectA->id,
            'teacher_id' => $this->teacherA->id,
            'status' => 'submitted',
            'approval_status' => 'pending',
        ]);
        $this->assertDatabaseHas('exams', [
            'id' => $this->examA->id,
            'status' => 'undone',
        ]);
    }

    public function test_saving_marks_does_not_advance_exam_status(): void
    {
        $this->actingAs($this->teacherA)
            ->post(route('teacher.exam.marks.save', $this->examA), ['marks' => []])
            ->assertRedirect(route('teacher.exam.marks.enter', $this->examA));

        $this->assertDatabaseHas('exams', [
            'id' => $this->examA->id,
            'status' => 'undone',
        ]);
        $this->assertDatabaseCount('exam_marks_submissions', 0);
    }

    public function test_another_teacher_and_another_school_cannot_submit(): void
    {
        $other = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 5,
            'email' => 'other.a@sub.test',
        ]);
        $teacherB = User::factory()->create([
            'school_id' => $this->schoolB->id, 'usergroup_id' => 5,
            'email' => 'teacher.b@sub.test',
        ]);

        $this->actingAs($other)
            ->post(route('teacher.exam.marks.submit', $this->examA))
            ->assertForbidden();
        $this->actingAs($teacherB)
            ->post(route('teacher.exam.marks.submit', $this->examA))
            ->assertForbidden();

        $this->assertDatabaseCount('exam_marks_submissions', 0);
        $this->assertDatabaseHas('exams', [
            'id' => $this->examA->id,
            'status' => 'undone',
        ]);
    }

    public function test_admin_queue_lists_the_submission_and_hides_another_school(): void
    {
        $this->savedMark();
        $this->actingAs($this->teacherA)
            ->post(route('teacher.exam.marks.submit', $this->examA))
            ->assertRedirect();

        $yearB = AcademicYear::where('school_id', $this->schoolB->id)->first();
        $termB = AcademicTerm::where('school_id', $this->schoolB->id)->first();
        $standardB = Standard::where('school_id', $this->schoolB->id)->first();
        $sectionB = Section::where('school_id', $this->schoolB->id)->first();
        $teacherB = User::factory()->create([
            'school_id' => $this->schoolB->id, 'usergroup_id' => 5,
            'email' => 'teacher.b2@sub.test',
        ]);
        $subjectB = Subject::create([
            'school_id' => $this->schoolB->id,
            'academic_year_id' => $yearB->id,
            'standard_id' => $standardB->id,
            'section_id' => $sectionB->id,
            'name' => 'Science B',
            'type' => 'core',
        ]);
        $examB = Exam::create([
            'school_id' => $this->schoolB->id,
            'academic_year_id' => $yearB->id,
            'academic_term_id' => $termB->id,
            'standard_id' => $standardB->id,
            'section_id' => $sectionB->id,
            'subject_id' => $subjectB->id,
            'teacher_id' => $teacherB->id,
            'exam_type_id' => 1,
            'status' => 'undone',
        ]);
        ExamMarksSubmission::create([
            'school_id' => $this->schoolB->id,
            'exam_id' => $examB->id,
            'class_id' => $sectionB->id,
            'subject_id' => $subjectB->id,
            'teacher_id' => $teacherB->id,
            'status' => 'submitted',
            'approval_status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->adminA)
            ->get(route('admin.marks.submissions'))
            ->assertOk()
            ->assertSee('MATH A')
            ->assertSee('P.1')
            ->assertSee('data-testid="marks-submissions-count"', false)
            ->assertSee('1 awaiting review')
            ->assertDontSee('SCIENCE B');

        $this->actingAs($this->adminA);
        $this->assertSame(1, SidebarCounts::forSchool($this->schoolA->id)['mark_submissions']);

        $menu = view('layouts.partials.sidebar-menu', ['role' => 'admin'])->render();
        $this->assertStringContainsString('>Mark submissions</span>', $menu);
        $this->assertMatchesRegularExpression(
            '/aria-label="Mark submissions"[\s\S]{0,500}sidebar-v3-count">1</',
            $menu
        );
    }

    public function test_enter_marks_page_has_a_separate_submit_control(): void
    {
        $empty = $this->actingAs($this->teacherA)
            ->get(route('teacher.exam.marks.enter', $this->examA))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-testid="marks-submit-button"', $empty);

        $this->savedMark();

        $html = $this->actingAs($this->teacherA)
            ->get(route('teacher.exam.marks.enter', $this->examA))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-testid="marks-submit-button"', $html);
        $this->assertStringContainsString(route('teacher.exam.marks.submit', $this->examA), $html);
        $this->assertDoesNotMatchRegularExpression(
            '/<form[^>]*marks\/save[\s\S]*marks-submit-button/',
            $html
        );
    }

    public function test_submit_with_nothing_saved_does_not_write_a_row(): void
    {
        $this->actingAs($this->teacherA)
            ->post(route('teacher.exam.marks.submit', $this->examA))
            ->assertRedirect(route('teacher.exam.marks.enter', $this->examA));

        $this->assertDatabaseCount('exam_marks_submissions', 0);
    }

    private function savedMark(): void
    {
        Marks::create([
            'student_id' => $this->teacherA->id,
            'subject_id' => $this->subjectA->id,
            'section_id' => $this->sectionA->id,
            'exam_id' => $this->examA->id,
            'teacher_id' => $this->teacherA->id,
            'school_id' => $this->schoolA->id,
            'marks' => 70,
            'grade' => 'C',
        ]);
    }
}
