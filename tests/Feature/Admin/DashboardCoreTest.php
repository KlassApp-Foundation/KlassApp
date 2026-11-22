<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace Tests\Feature\Admin;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\SchoolMcpConnector;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\Reports\ReportScoring;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\ReleasedExam;
use Tests\TestCase;

/**
 * Admin dashboard, core design: WhatsApp first, Latest results from the results service,
 * honest fees empty state and the connectors card. Synthetic data only.
 */
class DashboardCoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        config(['dashboard.v2_enabled' => true]);
    }

    private function school(): School
    {
        return School::create([
            'name' => 'Core School '.uniqid(), 'slug' => 'core-'.uniqid(),
            'email' => uniqid().'@core.test', 'phone' => '07'.random_int(70000000, 78999999), 'status' => 1,
        ]);
    }

    private function admin(School $school): User
    {
        $admin = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 3, 'status' => 'active', 'email_verified' => 1]);
        Userprofile::create(['user_id' => $admin->id, 'school_id' => $school->id, 'usergroup_id' => 3, 'firstname' => 'Demo', 'lastname' => 'Admin', 'status' => 'active']);

        return $admin;
    }

    private function html(User $admin): string
    {
        return $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->getContent();
    }

    /** @return array{school: School, admin: User, term: AcademicTerm, section: Section, exam: Exam, students: list<User>} */
    private function releasedClass(): array
    {
        $school = $this->school();
        $admin = $this->admin($school);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026', 'description' => 'y', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1]);
        $term = AcademicTerm::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term I', 'status' => 'current', 'starts_on' => '2026-01-05', 'ends_on' => '2026-12-01']);
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'Class A', 'status' => 1]);
        $link = StandardLink::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id, 'section_id' => $section->id, 'status' => 1]);
        foreach ([['D1', 1, 80, 100], ['C4', 4, 50, 79], ['F9', 9, 0, 49]] as [$grade, $points, $min, $max]) {
            SchoolGradingSystem::create(['school_id' => $school->id, 'standard_id' => $standard->id, 'grade' => $grade, 'points' => $points, 'min_score' => $min, 'max_score' => $max, 'remark' => $grade]);
        }
        $teacher = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 5, 'status' => 'active']);
        DB::table('exam_types')->insert(['id' => 1, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $subjects = [];
        foreach (['Maths', 'English'] as $name) {
            $subjects[] = Subject::create(['school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id, 'academic_year_id' => $year->id, 'name' => $name, 'type' => 'core', 'status' => 1]);
        }

        $students = [];
        $scores = [['Ada Learner', 90, 80], ['Ben Learner', 55, 62], ['Cleo Learner', 30, 41]];
        $exam = null;
        foreach ($subjects as $si => $subject) {
            $exam = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
                'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'exam_type_id' => 1,
                'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
                'scheduled_at' => '2026-03-0'.($si + 1), 'status' => 'submitted',
            ]));
            ReleasedExam::release($exam);
            foreach ($scores as $i => $row) {
                $students[$i] ??= $this->student($school, $year, $link, $row[0]);
                Marks::create(['student_id' => $students[$i]->id, 'exam_id' => $exam->id, 'school_id' => $school->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id, 'marks' => $row[1 + $si], 'grade' => 'stored']);
            }
        }

        return compact('school', 'admin', 'term', 'section', 'exam', 'students');
    }

    private function student(School $school, AcademicYear $year, StandardLink $link, string $name): User
    {
        $student = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 6, 'status' => 'active', 'name' => $name]);
        StudentAcademic::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'user_id' => $student->id, 'standardLink_id' => $link->id]);

        return $student;
    }

    public function test_whatsapp_tile_renders_first_and_shows_the_linked_number_count(): void
    {
        $school = $this->school();
        $admin = $this->admin($school);
        foreach (['256700000011', '256700000012'] as $phone) {
            $parent = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 3, 'status' => 'active']);
            WhatsAppUser::create(['school_id' => $school->id, 'user_id' => $parent->id, 'phone' => $phone, 'verified_at' => now(), 'opted_in' => true]);
        }
        $expected = app(\App\Services\WhatsApp\LinkRequestBoard::class)->linkedNumberCount((int) $school->id);

        $html = $this->html($admin);

        $kpis = substr($html, strpos($html, 'data-testid="dashboard-v2-kpis"'));
        $this->assertLessThan(strpos($kpis, 'dashboard-v2-kpi-students'), strpos($kpis, 'dashboard-v2-kpi-whatsapp'), 'WhatsApp tile comes before Students');
        $this->assertStringContainsString('data-testid="dashboard-connected-whatsapp-count">'.$expected.'<', $html);
        $this->assertStringContainsString('parent', $kpis);
    }

    public function test_latest_results_match_the_results_service_for_a_released_class(): void
    {
        $f = $this->releasedClass();
        $html = $this->html($f['admin']);

        $this->assertStringContainsString('Latest results · Class A', $html);
        $this->assertStringContainsString('Open release sheet', $html);

        $policy = app(ReportScoring::class)->policyForTerm(
            $f['school']->id,
            app(ReportScoring::class)->standardIdForSection($f['school']->id, $f['section']->id),
            $f['section']->id,
            $f['term']->id,
        );
        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: $f['school']->id, sectionId: $f['section']->id,
            academicTermId: $f['term']->id, examTypeId: 1,
        ), $policy);

        $checked = 0;
        foreach ([$f['students'][0], $f['students'][2]] as $student) {
            $row = $sheet->student($student->id);
            $this->assertNotNull($row);
            $this->assertNotNull($row->aggregate);
            $pattern = '/'.preg_quote($student->name, '/').'<\/th>\s*<td data-testid="latest-total">'.preg_quote(\App\Services\Results\ResultsPolicy::printWhole((float) $row->displayTotal), '/')
                .'<\/td>\s*<td data-testid="latest-agg">'.preg_quote((string) $row->aggregate, '/')
                .'<\/td>\s*<td data-testid="latest-div">'.preg_quote((string) $row->division, '/').'<\/td>/';
            $this->assertMatchesRegularExpression($pattern, $html, "Latest results row for {$student->name}");
            $checked++;
        }
        $this->assertSame(2, $checked);

        // The link lands on the release that was just made.
        $this->assertMatchesRegularExpression('#href="[^"]*/marks/releases/\d+[^"]*"[^>]*data-testid="dashboard-latest-results-open"#', $html);
    }

    public function test_latest_results_has_an_empty_state_when_nothing_is_released(): void
    {
        $html = $this->html($this->admin($this->school()));

        $this->assertStringContainsString('No results released yet. Release a class to see its results here.', $html);
        $this->assertStringNotContainsString('Open release sheet', $html);
    }

    public function test_fees_shows_not_set_up_yet_and_never_a_zero_of_zero(): void
    {
        $html = $this->html($this->admin($this->school()));

        $this->assertStringContainsString('Not set up yet', $html);
        $this->assertStringContainsString('Set up fees', $html);
        $this->assertStringNotContainsString('UGX 0 of UGX 0', $html);
        $this->assertStringNotContainsString('0 of 0', $html);
    }

    public function test_connectors_are_in_order_with_their_statuses_and_drive_state(): void
    {
        $school = $this->school();
        $admin = $this->admin($school);

        $html = $this->html($admin);
        $order = ['whatsapp', 'sms', 'email', 'google-drive', 'google-calendar', 'slack', 'more'];
        $last = -1;
        foreach ($order as $key) {
            $pos = strpos($html, 'data-testid="dashboard-connector-'.$key.'"');
            $this->assertNotFalse($pos, "connector {$key} renders");
            $this->assertGreaterThan($last, $pos, "connector {$key} is in order");
            $last = $pos;
        }
        $this->assertMatchesRegularExpression('/dashboard-connector-google-drive"[^>]*data-status="Available"/', $html);
        $this->assertMatchesRegularExpression('/dashboard-connector-google-calendar"[^>]*data-status="Preview"/', $html);
        $this->assertMatchesRegularExpression('/dashboard-connector-more"[^>]*data-status="Coming"/', $html);
        $this->assertStringContainsString('CORE', $html);
        $this->assertStringContainsString('Onboard from your checklist', $html);
        $this->assertStringNotContainsString('dashboard-connected-drive', $html);

        SchoolMcpConnector::query()->create([
            'school_id' => $school->id, 'connector_type' => 'google-drive', 'status' => 'active', 'external_team_id' => 'demo-team', 'credentials' => ['placeholder' => 'none'],
        ]);
        $connected = $this->html(User::findOrFail($admin->id));
        $this->assertMatchesRegularExpression('/dashboard-connector-google-drive"[^>]*data-status="Connected"/', $connected);
        $this->assertStringContainsString('dashboard-connected-drive', $connected);
    }
}
