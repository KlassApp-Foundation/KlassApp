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
use App\Models\Userprofile;
use App\Services\ParentLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentDashboardPhase5Test extends TestCase
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
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = School::create(['name' => 'Alpha Primary', 'email' => 'alpha@test.sch.ug', 'status' => 1]);
        $this->schoolB = School::create(['name' => 'Beta Senior', 'email' => 'beta@test.sch.ug', 'status' => 1]);

        $this->linkA = $this->createStandardLink($this->schoolA, 'P.1');
        $this->linkB = $this->createStandardLink($this->schoolB, 'S.1');

        $this->studentA = $this->createStudent($this->schoolA, $this->linkA, 'Child Alpha');
        $this->studentB = $this->createStudent($this->schoolB, $this->linkB, 'Child Beta');

        app(ParentLinkService::class)->linkByStudentId('+256700555001', $this->studentA->id, 'Dash Parent');
        app(ParentLinkService::class)->linkByStudentId('+256700555001', $this->studentB->id, 'Dash Parent');

        $this->parent = User::where('usergroup_id', 7)->firstOrFail();
        $this->parent->update([
            'email' => 'parent.dash@test.sch.ug',
            'password' => bcrypt('dash-pass-123'),
            'status' => 'active',
        ]);

        Userprofile::firstOrCreate(
            ['user_id' => $this->parent->id],
            [
                'usergroup_id' => 7,
                'school_id' => null,
                'firstname' => 'Dash',
                'lastname' => 'Parent',
                'status' => 'active',
            ]
        );

        FeesCategories::create([
            'school_id' => $this->schoolA->id,
            'standard_id' => $this->linkA->standard_id,
            'name' => 'Alpha-Only Tuition',
            'amount' => 450000,
        ]);

        FeesCategories::create([
            'school_id' => $this->schoolB->id,
            'standard_id' => $this->linkB->standard_id,
            'name' => 'Beta-Only Tuition',
            'amount' => 650000,
        ]);

        $this->seedExamMarkForStudent($this->schoolB, $this->linkB, $this->studentB, 'Beta Maths', 88);
    }

    public function test_dashboard_shows_empty_state_when_no_children_linked(): void
    {
        $lonely = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'status' => 'active',
            'email' => 'lonely.parent@test.sch.ug',
            'password' => bcrypt('lonely-pass'),
        ]);

        Userprofile::create([
            'user_id' => $lonely->id,
            'usergroup_id' => 7,
            'school_id' => null,
            'firstname' => 'Lonely',
            'lastname' => 'Parent',
            'status' => 'active',
        ]);

        $this->actingAs($lonely)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('No children linked')
            ->assertSee('parent-empty-children', false)
            ->assertDontSee('Alpha-Only Tuition')
            ->assertDontSee('Beta-Only Tuition');
    }

    public function test_dashboard_groups_children_by_school_and_shows_selected_child_fees(): void
    {
        $response = $this->actingAs($this->parent)
            ->get(route('parent.dashboard', ['child' => $this->studentB->id]));

        $response->assertOk();
        $response->assertSee('Alpha Primary');
        $response->assertSee('Beta Senior');
        $response->assertSee('Child Alpha');
        $response->assertSee('Child Beta');
        $response->assertSee('Beta-Only Tuition');
        $response->assertSee('650,000');
        $response->assertDontSee('Alpha-Only Tuition');
        $response->assertSee('Fee Balance');
        $response->assertSee('Attendance');
        $response->assertSee('Grades');
        $response->assertSee('BETA MATHS');
        $response->assertSee('88');
        $html = $response->getContent();
        $this->assertStringContainsString('ds-kpi-card', $html);
        $this->assertStringNotContainsString('dashboard-kpi-card', $html);
    }

    public function test_dashboard_defaults_to_first_child_and_rejects_unlinked_child_param(): void
    {
        $stranger = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 6,
            'name' => 'Stranger Kid',
        ]);

        $this->actingAs($this->parent)
            ->get(route('parent.dashboard', ['child' => $stranger->id]))
            ->assertOk()
            ->assertSee('Child Alpha')
            ->assertSee('Alpha-Only Tuition')
            ->assertDontSee('Stranger Kid');
    }

    public function test_dashboard_shows_empty_grades_message_when_none_published(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.dashboard', ['child' => $this->studentA->id]))
            ->assertOk()
            ->assertSee('No results published')
            ->assertSee('Alpha-Only Tuition');
    }

    public function test_children_page_also_groups_by_school(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.children'))
            ->assertOk()
            ->assertSee('Alpha Primary')
            ->assertSee('Beta Senior')
            ->assertSee('Child Alpha')
            ->assertSee('Child Beta');
    }

    private function createStandardLink(School $school, string $sectionName): StandardLink
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
        $section = Section::create(['school_id' => $school->id, 'name' => $sectionName]);

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
