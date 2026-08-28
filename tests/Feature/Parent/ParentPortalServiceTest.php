<?php

namespace Tests\Feature\Parent;

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
use App\Services\Parent\ParentPortalService;
use App\Services\ParentLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentPortalServiceTest extends TestCase
{
    use RefreshDatabase;

    private ParentPortalService $portal;

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

        $this->portal = app(ParentPortalService::class);

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

        app(ParentLinkService::class)->linkByStudentId('+256700999888', $this->studentA->id, 'Cross Parent');
        app(ParentLinkService::class)->linkByStudentId('+256700999888', $this->studentB->id, 'Cross Parent');

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

    public function test_fee_balance_for_school_b_child_uses_school_b_fee_categories(): void
    {
        $result = $this->portal->feeBalance($this->parent, 'Child Beta');

        $this->assertTrue($result['success']);
        $this->assertSame($this->schoolB->id, $result['data']['school_id']);
        $this->assertSame(250000.0, (float) $result['data']['total_balance']);
        $this->assertSame('Beta-Only Tuition', $result['data']['categories'][0]['name']);
    }

    public function test_grades_for_school_b_child_use_school_b_exams(): void
    {
        $result = $this->portal->grades($this->parent, 'Child Beta');

        $this->assertTrue($result['success']);
        $this->assertSame($this->schoolB->id, $result['data']['school_id']);
        $this->assertNotEmpty($result['data']['exam_groups']);
        $this->assertSame('BETA MATHS', $result['data']['exam_groups'][0]['subjects'][0]['name']);
        $this->assertSame(88.0, $result['data']['exam_groups'][0]['subjects'][0]['score']);
    }

    public function test_resolve_child_rejects_unlinked_student_id(): void
    {
        $stranger = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 6,
            'name' => 'Stranger Student',
        ]);

        $resolved = $this->portal->resolveChild($this->parent, null, $stranger->id);

        $this->assertFalse($resolved['ok']);
        $this->assertTrue($resolved['denied']);
        $this->assertStringContainsString('not linked', $resolved['message']);
    }

    public function test_fee_balance_by_unlinked_student_id_is_denied_not_empty_success(): void
    {
        $stranger = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 6,
        ]);

        FeesCategories::create([
            'school_id' => $this->schoolA->id,
            'standard_id' => $this->linkA->standard_id,
            'name' => 'Secret Fees',
            'amount' => 999999,
        ]);

        $result = $this->portal->feeBalance($this->parent, null, $stranger->id);

        $this->assertFalse($result['success']);
        $this->assertTrue($result['denied']);
        $this->assertArrayNotHasKey('data', $result);
    }

    public function test_list_children_returns_both_cross_school_links(): void
    {
        $result = $this->portal->listChildren($this->parent);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['count']);
        $schoolIds = collect($result['children'])->pluck('school_id')->sort()->values()->all();
        $this->assertSame([$this->schoolA->id, $this->schoolB->id], $schoolIds);
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
