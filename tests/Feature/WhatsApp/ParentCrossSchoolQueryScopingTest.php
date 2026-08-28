<?php

namespace Tests\Feature\WhatsApp;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\ParentLinkService;
use App\Services\Toshi\ParentActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ParentCrossSchoolQueryScopingTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $parent;

    private User $studentA;

    private User $studentB;

    private StandardLink $linkA;

    private StandardLink $linkB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = School::create(['name' => 'Alpha Primary', 'email' => 'alpha@test.sch.ug', 'status' => 1]);
        $this->schoolB = School::create(['name' => 'Beta Senior', 'email' => 'beta@test.sch.ug', 'status' => 1]);

        $this->linkA = $this->createStandardLink($this->schoolA);
        $this->linkB = $this->createStandardLink($this->schoolB);

        $this->studentA = $this->createStudent($this->schoolA, $this->linkA, 'Child Alpha');
        $this->studentB = $this->createStudent($this->schoolB, $this->linkB, 'Child Beta');

        $linkService = app(ParentLinkService::class);
        $linkService->linkByStudentId('+256700999888', $this->studentA->id, 'Cross Parent');
        $linkService->linkByStudentId('+256700999888', $this->studentB->id, 'Cross Parent');

        $this->parent = User::where('usergroup_id', 7)->firstOrFail();
        $this->assertNull($this->parent->school_id);

        FeesCategories::create([
            'school_id' => $this->schoolA->id,
            'standard_id' => $this->linkA->standard_id,
            'name' => 'Alpha-Only Tuition',
            'amount' => 100000,
        ]);

        FeesCategories::create([
            'school_id' => $this->schoolB->id,
            'standard_id' => $this->linkB->standard_id,
            'name' => 'Beta-Only Tuition',
            'amount' => 250000,
        ]);

        $this->seedExamMarkForStudent(
            $this->schoolB,
            $this->linkB,
            $this->studentB,
            'Beta Maths',
            88,
        );
    }

    /** @test */
    public function fee_balance_for_school_b_child_uses_school_b_fee_categories(): void
    {
        $result = ParentActionService::feeBalance($this->parent, 'Child Beta');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Beta-Only Tuition', $result['message']);
        $this->assertStringNotContainsString('Alpha-Only Tuition', $result['message']);
        $this->assertSame(250000.0, (float) $result['data']['total_balance']);
    }

    /** @test */
    public function grades_for_school_b_child_use_school_b_exams(): void
    {
        $result = ParentActionService::grades($this->parent, 'Child Beta');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('MATHS', strtoupper($result['message']));
        $this->assertStringContainsString('88', $result['message']);
    }

    /** @test */
    public function checkschool_allows_parent_with_null_school_id_when_linked_school_is_active(): void
    {
        $this->parent->update([
            'email' => 'parent.portal@test.sch.ug',
            'password' => bcrypt('secret-pass'),
        ]);

        $response = $this->post('/login', [
            'email' => 'parent.portal@test.sch.ug',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionDoesntHaveErrors([
            'password' => 'Invalid Credentials.You are not in this school',
        ]);
    }

    /** @test */
    public function toshi_parent_action_gate_allows_null_school_parent_with_active_links(): void
    {
        $this->assertTrue(Gate::forUser($this->parent)->allows('toshi-parent-action'));
    }

    private function createStandardLink(School $school): StandardLink
    {
        $ay = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
            'status' => 1,
        ]);

        $standard = Standard::create(['school_id' => $school->id, 'name' => 'Primary', 'order' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'P1']);

        return StandardLink::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $ay->id,
        ]);
    }

    private function createStudent(School $school, StandardLink $link, string $name): User
    {
        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => $name,
        ]);

        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $link->academic_year_id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);

        return $student;
    }

    private function seedExamMarkForStudent(
        School $school,
        StandardLink $standardLink,
        User $student,
        string $subjectName,
        int $score,
    ): void {
        $ay = AcademicYear::where('school_id', $school->id)->first();
        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'name' => 'Term I',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);

        $subject = Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'standard_id' => $standardLink->standard_id,
            'section_id' => $standardLink->section_id,
            'name' => $subjectName,
            'type' => 'core',
            'status' => 1,
        ]);

        $examType = ExamType::create([
            'name' => 'Mid Term',
            'code' => 'MID',
            'contributes_to_report_total' => true,
        ]);

        $teacher = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
        ]);

        $exam = Exam::create([
            'school_id' => $school->id,
            'standard_id' => $standardLink->standard_id,
            'section_id' => $standardLink->section_id,
            'academic_year_id' => $ay->id,
            'academic_term_id' => $term->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => $examType->id,
            'status' => 'done',
        ]);

        Marks::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'section_id' => $standardLink->section_id,
            'exam_id' => $exam->id,
            'teacher_id' => $teacher->id,
            'school_id' => $school->id,
            'marks' => $score,
            'grade' => 'D1',
        ]);
    }
}
