<?php

namespace Tests\Feature\Parent;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Parent\ParentPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Support\ReleasedExam;
use Tests\TestCase;

class ParentStudentPortalFixesTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;

    protected User $parent;

    protected User $student;

    protected User $teacher;

    protected StandardLink $link;

    protected AcademicYear $currentYear;

    protected AcademicYear $pastYear;

    private Subject $currentSubject;

    private Subject $pastSubject;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Portal Fixes School',
            'email' => 'portal-fixes@test.sch.ug',
            'status' => 1,
        ]);

        $standard = Standard::create(['school_id' => $this->school->id, 'name' => 'Primary', 'order' => 1]);
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'P.4']);

        $this->currentYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $this->pastYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025',
            'description' => 'Past',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 0,
        ]);
        Cache::flush();

        $this->link = StandardLink::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $this->currentYear->id,
        ]);

        $this->teacher = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'status' => 'active',
            'name' => 'Portal Teacher',
        ]);

        $this->student = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'status' => 'active',
            'name' => 'AMINA NAKATO',
        ]);
        Userprofile::create([
            'user_id' => $this->student->id,
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'firstname' => 'AMINA',
            'lastname' => 'NAKATO',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->currentYear->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $this->link->id,
        ]);

        $this->parent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'status' => 'active',
            'name' => 'Portal Parent',
            'email' => 'portal.parent@test.sch.ug',
        ]);
        Userprofile::create([
            'user_id' => $this->parent->id,
            'usergroup_id' => 7,
            'school_id' => null,
            'firstname' => 'Portal',
            'lastname' => 'Parent',
            'status' => 'active',
        ]);
        StudentParentLink::create([
            'parent_id' => $this->parent->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'status' => 1,
        ]);

        $currentTerm = $this->term($this->currentYear, 'Term I', 'current', '2026-01-15', '2026-04-30');
        $pastTerm = $this->term($this->pastYear, 'Term III', 'last', '2025-09-01', '2025-11-30');
        $this->currentSubject = $this->subject($this->currentYear, 'Mathematics');
        $this->pastSubject = $this->subject($this->pastYear, 'English');

        for ($i = 1; $i <= 16; $i++) {
            $this->sitting($this->currentYear, $currentTerm, $this->currentSubject, sprintf('Set %02d', $i), 60 + $i, true);
        }
        $this->sitting($this->currentYear, $currentTerm, $this->currentSubject, 'Hidden Now', 11, false);
        $this->sitting($this->pastYear, $pastTerm, $this->pastSubject, 'Past Set A', 71, true);
        $this->sitting($this->pastYear, $pastTerm, $this->pastSubject, 'Past Set B', 82, true);
        $this->sitting($this->pastYear, $pastTerm, $this->pastSubject, 'Hidden Past', 13, false);

        FeesCategories::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->link->standard_id,
            'name' => 'Tuition Structure',
            'amount' => 80000,
        ]);
        FeePayment::create([
            'school_id' => $this->school->id,
            'fee_category_id' => FeesCategories::where('school_id', $this->school->id)->value('id'),
            'user_id' => $this->student->id,
            'amount' => 30000,
            'paid_on' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'paid',
            'recorded_by' => $this->teacher->id,
        ]);

        \App\Models\Attendance::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->currentYear->id,
            'user_id' => $this->student->id,
            'date' => now()->subDays(3)->toDateString(),
            'session' => 'forenoon',
            'status' => 1,
            'recorded_by' => $this->teacher->id,
        ]);
        \App\Models\Attendance::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->currentYear->id,
            'user_id' => $this->student->id,
            'date' => now()->subDays(10)->toDateString(),
            'session' => 'forenoon',
            'status' => 0,
            'recorded_by' => $this->teacher->id,
        ]);
    }

    public function test_released_results_from_two_years_and_many_sets_are_shown_and_unreleased_are_not(): void
    {
        $portal = app(ParentPortalService::class);

        $all = $portal->grades($this->parent, null, $this->student->id, 'all');
        $this->assertTrue($all['success']);
        $labels = array_column($all['data']['exam_groups'], 'exam_type');
        $this->assertGreaterThan(15, count($labels));
        $joined = implode("\n", $labels);
        $this->assertStringContainsString('Set 01', $joined);
        $this->assertStringContainsString('Set 16', $joined);
        $this->assertStringContainsString('Past Set A', $joined);
        $this->assertStringContainsString('Past Set B', $joined);
        $this->assertStringNotContainsString('Hidden Now', $joined);
        $this->assertStringNotContainsString('Hidden Past', $joined);

        $current = $portal->grades($this->parent, null, $this->student->id, null);
        $this->assertSame($this->currentYear->id, $current['data']['selected_year']);
        $currentLabels = implode("\n", array_column($current['data']['exam_groups'], 'exam_type'));
        $this->assertStringContainsString('Set 16', $currentLabels);
        $this->assertStringNotContainsString('Past Set A', $currentLabels);
        $this->assertStringNotContainsString('Hidden Now', $currentLabels);

        $past = $portal->grades($this->parent, null, $this->student->id, $this->pastYear->id);
        $pastLabels = implode("\n", array_column($past['data']['exam_groups'], 'exam_type'));
        $this->assertStringContainsString('Past Set A', $pastLabels);
        $this->assertStringContainsString('Past Set B', $pastLabels);
        $this->assertStringNotContainsString('Hidden Past', $pastLabels);
        $this->assertStringNotContainsString('Set 01', $pastLabels);

        $parentPages = [
            route('parent.children.grades', ['student' => $this->student->id]),
            route('parent.children.grades', ['student' => $this->student->id, 'year' => 'all']),
            route('parent.children.grades', ['student' => $this->student->id, 'year' => $this->pastYear->id]),
            route('parent.children.grades', ['student' => $this->student->id, 'year' => $this->currentYear->id]),
        ];
        foreach ($parentPages as $url) {
            $html = $this->actingAs($this->parent)->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('Hidden Now', $html);
            $this->assertStringNotContainsString('Hidden Past', $html);
        }

        $defaultPage = $this->actingAs($this->parent)->get(route('parent.children.grades', ['student' => $this->student->id]));
        $defaultPage->assertOk();
        $defaultPage->assertSee('Set 16');
        $defaultPage->assertSee('2026 (current)');
        $defaultPage->assertSee('data-testid="results-year-filter"', false);
        $defaultPage->assertSee('data-testid="results-performance-chart"', false);
        $defaultPage->assertDontSee('Past Set A');

        $allPage = $this->actingAs($this->parent)->get(route('parent.children.grades', [
            'student' => $this->student->id,
            'year' => 'all',
        ]));
        $allPage->assertOk();
        $allPage->assertSee('Set 01');
        $allPage->assertSee('Set 16');
        $allPage->assertSee('Past Set A');
        $allPage->assertSee('Past Set B');
        $allPage->assertDontSee('Hidden Now');
        $allPage->assertDontSee('Hidden Past');

        $studentAll = $this->actingAs($this->student)->get(route('student.marks', ['year' => 'all']));
        $studentAll->assertOk();
        $studentAll->assertSee('Set 16');
        $studentAll->assertSee('Past Set A');
        $studentAll->assertDontSee('Hidden Now');
        $studentAll->assertDontSee('Hidden Past');

        $studentPast = $this->actingAs($this->student)->get(route('student.marks', ['year' => $this->pastYear->id]));
        $studentPast->assertOk();
        $studentPast->assertSee('Past Set B');
        $studentPast->assertDontSee('Hidden Past');
        $studentPast->assertDontSee('Set 01');
    }

    public function test_dashboard_shows_the_child_name_once_in_title_case_without_changing_stored_data(): void
    {
        $html = $this->actingAs($this->parent)
            ->get(route('parent.dashboard', ['child' => $this->student->id]))
            ->assertOk()
            ->getContent();

        $this->assertGreaterThanOrEqual(1, substr_count($html, 'Amina Nakato'));
        $this->assertStringNotContainsString('AMINA NAKATO', $html);
        $this->assertStringContainsString('P.4', $html);
        $this->assertSame('AMINA NAKATO', $this->student->fresh()->name);
        $this->assertSame('AMINA', $this->student->userprofile->fresh()->firstname);
    }

    public function test_dashboard_stat_cards_link_to_marks_attendance_and_fees(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.dashboard', ['child' => $this->student->id]))
            ->assertOk()
            ->assertSee(route('parent.children.fees', $this->student->id), false)
            ->assertSee(route('parent.children.attendance', $this->student->id), false)
            ->assertSee(route('parent.children.grades', $this->student->id), false);

        $fees = $this->actingAs($this->parent)->get(route('parent.children.fees', $this->student->id));
        $fees->assertOk();
        $fees->assertSee('data-testid="fee-structure"', false);
        $fees->assertSee('Tuition Structure');
        $fees->assertSee('data-testid="fee-paid"', false);
        $fees->assertSee('30,000');
        $fees->assertSee('data-testid="fee-balance"', false);
        $fees->assertSee('50,000');
        $fees->assertSee('data-testid="fees-chart"', false);

        $this->actingAs($this->parent)
            ->get(route('parent.children.attendance', $this->student->id))
            ->assertOk()
            ->assertSee('data-testid="attendance-chart"', false);

        $this->actingAs($this->student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee(route('student.marks'), false)
            ->assertSee(route('student.attendance'), false)
            ->assertSee(route('student.fees'), false);

        $studentFees = $this->actingAs($this->student)->get(route('student.fees'));
        $studentFees->assertOk();
        $studentFees->assertSee('Tuition Structure');
        $studentFees->assertSee('data-testid="fee-paid"', false);
        $studentFees->assertSee('data-testid="fee-balance"', false);
        $studentFees->assertSee('data-testid="fees-chart"', false);

        $this->actingAs($this->student)
            ->get(route('student.attendance'))
            ->assertOk()
            ->assertSee('data-testid="attendance-chart"', false);
    }

    public function test_parent_and_student_sidebars_use_the_admin_active_style_on_the_real_routes(): void
    {
        $fees = $this->actingAs($this->parent)
            ->get(route('parent.children.fees', $this->student->id))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<li class="[^"]*dashboard-active[^"]*">(?:(?!<li\b).)*Fees/s',
            $fees,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<li class="[^"]*dashboard-active[^"]*">(?:(?!<li\b).)*Children/s',
            $fees,
        );

        $marks = $this->actingAs($this->student)->get(route('student.marks'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression(
            '/<li class="[^"]*dashboard-active[^"]*">(?:(?!<li\b).)*Marks/s',
            $marks,
        );
        $css = file_get_contents(public_path('css/dashboard-refresh.css'));
        $this->assertStringContainsString('.parent-sidebar .dashboard-menu-item.dashboard-active', $css);
        $this->assertStringContainsString('.student-sidebar .dashboard-menu-item.dashboard-active', $css);
        $this->assertStringContainsString('color: #1E293B', $css);
    }

    public function test_parent_header_uses_the_shared_profile_menu(): void
    {
        $html = $this->actingAs($this->parent)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('dusk="profile-menu"', $html);
        $this->assertStringContainsString('Log out', $html);
        $this->assertStringContainsString('Parent', $html);
        $this->assertStringNotContainsString('>Logout<', $html);
        $this->assertStringNotContainsString('/admin/changepassword', $html);
        $this->assertStringNotContainsString('/admin/editprofile', $html);
    }

    private function term(AcademicYear $year, string $name, string $status, string $starts, string $ends): AcademicTerm
    {
        return AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => $name,
            'starts_on' => $starts,
            'ends_on' => $ends,
            'status' => $status,
        ]);
    }

    private function subject(AcademicYear $year, string $name): Subject
    {
        return Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $this->link->standard_id,
            'section_id' => $this->link->section_id,
            'name' => $name,
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function sitting(
        AcademicYear $year,
        AcademicTerm $term,
        Subject $subject,
        string $setName,
        int $score,
        bool $release,
    ): void {
        $type = ExamType::create([
            'name' => $setName,
            'code' => 'SET-'.uniqid(),
            'contributes_to_report_total' => true,
        ]);

        $exam = Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->link->standard_id,
            'section_id' => $this->link->section_id,
            'academic_year_id' => $year->id,
            'academic_term_id' => $term->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'exam_type_id' => $type->id,
            'status' => 'done',
        ]);

        Marks::create([
            'student_id' => $this->student->id,
            'subject_id' => $subject->id,
            'section_id' => $this->link->section_id,
            'exam_id' => $exam->id,
            'teacher_id' => $this->teacher->id,
            'school_id' => $this->school->id,
            'marks' => $score,
            'grade' => 'C3',
        ]);

        if ($release) {
            ReleasedExam::release($exam);
        }
    }
}
