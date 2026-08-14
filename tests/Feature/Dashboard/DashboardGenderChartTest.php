<?php

namespace Tests\Feature\Dashboard;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: the Students donut summed only maleCount + femaleCount while the
 * headline card showed studentCount, silently dropping students whose
 * userprofile gender is NULL/blank (MySQL collapses imported "U" values to ''
 * because the column enum only allows male/female). The chart must include an
 * "Unspecified" segment and center on the real studentCount.
 */
class DashboardGenderChartTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Gender Chart School',
            'email' => 'gender.chart@test.sch.ug',
            'phone' => '0700000101',
            'slug' => 'gender-chart-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);
    }

    private function dashboardData(array $overrides = []): array
    {
        return array_merge([
            'studentCount' => 1250,
            'teacherCount' => 1,
            'parentCount' => 0,
            'nonteachingCount' => 0,
            'femaleCount' => 476,
            'maleCount' => 610,
            'unknownCount' => 164,
            'setupIncomplete' => false,
            'whatsapp' => ['parentsOptedIn' => 0, 'messagesThisMonth' => 0],
            'noticeboard' => [],
            'feedbacks' => [],
            'events' => [],
            'products' => [],
            'upcomingExam' => [],
            'standardStudentCounts' => collect(),
            'work' => [],
            'task' => [],
            'event' => [],
            'reminder' => [],
            'fee' => [],
            'birthday' => [],
        ], $overrides);
    }

    private function renderDashboard(array $dashboard): \Illuminate\Testing\TestView
    {
        return $this->view('admin.dashboard.dashboard', [
            'dashboard' => $dashboard,
            'standardLink' => null,
            'selected_teacher' => null,
            'plan' => null,
            'planUsage' => ['students' => ['used' => 0, 'limit' => 0], 'teachers' => ['used' => 0, 'limit' => 0]],
            'onboardingMissing' => [],
            'onboardingSteps' => [],
            'setupIncomplete' => false,
            'openToshiOnboarding' => false,
            'pendingApprovals' => 0,
            'trendPeriod' => 'month',
            'feeTrend' => ['labels' => [], 'values' => []],
        ]);
    }

    public function test_donut_includes_unspecified_segment_so_total_matches_student_count(): void
    {
        $view = $this->renderDashboard($this->dashboardData());

        $view->assertSee('var femaleCount = 476;', false);
        $view->assertSee('var maleCount = 610;', false);
        $view->assertSee('var unknownCount = 164;', false);
        $view->assertSee('var totalStudents = 1250;', false);
        $view->assertSee('data: [maleCount,femaleCount,unknownCount],', false);
        $view->assertSee('"Male Students", "Female Students", "Unspecified"', false);
        $view->assertSee('"#ffa601", "#304ffe", "#cbd5e1"', false);
    }

    public function test_donut_center_is_student_count_not_gender_sum(): void
    {
        $view = $this->renderDashboard($this->dashboardData());

        $view->assertDontSee('var totalStudents = femaleCount + maleCount;', false);
    }

    public function test_gender_stat_boxes_show_unspecified_count(): void
    {
        $view = $this->renderDashboard($this->dashboardData());

        $view->assertSee('Unspecified', false);
        $view->assertSee('476', false);
        $view->assertSee('610', false);
        $view->assertSee('164', false);
    }

    public function test_per_class_bar_chart_includes_unspecified_dataset(): void
    {
        $link = (object) [
            'id' => 1,
            'section' => (object) ['name' => 'P7'],
            'studentCount' => 40,
            'maleCount' => 25,
            'femaleCount' => 10,
            'unknownCount' => 5,
        ];

        $view = $this->renderDashboard($this->dashboardData([
            'standardStudentCounts' => collect([$link]),
        ]));

        $view->assertSee('"unknown":5', false);
        $view->assertSee("label: 'Unspecified'", false);
    }

    public function test_donut_falls_back_when_no_student_data_at_all(): void
    {
        $view = $this->renderDashboard($this->dashboardData([
            'studentCount' => 0,
            'femaleCount' => 0,
            'maleCount' => 0,
            'unknownCount' => 0,
        ]));

        $view->assertSee('No gender data', false);
        $view->assertSee('—', false);
    }
}
