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
            'feeTrend' => [],
            'greeting' => ['phrase' => 'Good morning', 'name' => 'Admin'],
            'dashboardContextLine' => 'School overview · 1,250 students enrolled',
            'eotKpis' => ['perClass' => [], 'perSubject' => [], 'perGender' => []],
        ]);
    }

    /**
     * Decoded Chart.js configs carried on the x-chart shells' data attributes.
     *
     * @return array<int, array<string, mixed>>
     */
    private function chartConfigs(string $html): array
    {
        preg_match_all('/data-chart-config="([^"]*)"/', $html, $m);

        return array_map(
            fn ($raw) => json_decode(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5), true),
            $m[1]
        );
    }

    private function chartOfType(string $html, string $type): ?array
    {
        foreach ($this->chartConfigs($html) as $cfg) {
            if (($cfg['type'] ?? null) === $type) {
                return $cfg;
            }
        }

        return null;
    }

    /**
     * Regression: an unescaped `['studentCount']` subscript inside the doughnut's
     * single-quoted options-js attribute broke Blade's component-tag match, so
     * the raw <x-chart> markup shipped and the doughnut never rendered.
     */
    public function test_every_chart_component_compiles(): void
    {
        $html = (string) $this->renderDashboard($this->dashboardData([
            'standardStudentCounts' => collect([(object) [
                'id' => 1, 'section' => (object) ['name' => 'P7'],
                'studentCount' => 40, 'maleCount' => 25, 'femaleCount' => 10, 'unknownCount' => 5,
            ]]),
        ]));

        $this->assertStringNotContainsString('<x-chart', $html);
        $this->assertNotNull($this->chartOfType($html, 'doughnut'), 'doughnut shell missing');
        $this->assertNotNull($this->chartOfType($html, 'bar'), 'class bar shell missing');
    }

    public function test_donut_includes_unspecified_segment_so_total_matches_student_count(): void
    {
        $html = (string) $this->renderDashboard($this->dashboardData());
        $donut = $this->chartOfType($html, 'doughnut');

        $this->assertNotNull($donut);
        $this->assertSame(['Male Students', 'Female Students', 'Unspecified'], $donut['data']['labels']);
        $this->assertSame([610, 476, 164], $donut['data']['datasets'][0]['data']);
        // Same series colours as the per-class bar: boys blue, girls #B45309, unspecified #64748B.
        $this->assertSame(['#304ffe', '#B45309', '#64748B'], $donut['data']['datasets'][0]['backgroundColor']);
        $this->assertSame('1250', $donut['options']['plugins']['dsCenterValue']['value']);
    }

    public function test_donut_center_is_student_count_not_gender_sum(): void
    {
        $html = (string) $this->renderDashboard($this->dashboardData());

        // 610 + 476 = 1086 would be the pre-fix gender-only sum.
        $this->assertSame('1250', $this->chartOfType($html, 'doughnut')['options']['plugins']['dsCenterValue']['value']);
        $this->assertStringContainsString('var t = 1250;', $html);
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

        $html = (string) $this->renderDashboard($this->dashboardData([
            'standardStudentCounts' => collect([$link]),
        ]));
        $bar = $this->chartOfType($html, 'bar');

        $this->assertNotNull($bar);
        $this->assertSame(['P7'], $bar['data']['labels']);
        $byLabel = collect($bar['data']['datasets'])->keyBy('label');
        $this->assertSame([5], $byLabel['Unspecified']['data']);
        $this->assertSame('#64748B', $byLabel['Unspecified']['backgroundColor']);
        $this->assertSame('#B45309', $byLabel['Girls']['backgroundColor']);
        $this->assertSame('#304ffe', $byLabel['Boys']['backgroundColor']);
    }

    public function test_donut_falls_back_when_no_student_data_at_all(): void
    {
        $view = $this->renderDashboard($this->dashboardData([
            'studentCount' => 0,
            'femaleCount' => 0,
            'maleCount' => 0,
            'unknownCount' => 0,
        ]));

        $view->assertSee('No students enrolled yet', false);
        $view->assertDontSee('No gender data', false);
        $view->assertSee('—', false);
    }
}
