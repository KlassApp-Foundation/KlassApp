<?php

namespace Tests\Feature\Teacher;

use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Tests\Support\AccountRoleFixture;
use Tests\TestCase;

/** The teacher dashboard's stat tiles are the school admin dashboard's tiles (same component, same classes). */
class TeacherDashboardKpiTilesTest extends TestCase
{
    use AccountRoleFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->buildRoleFixture();
        AcademicYear::create([
            'school_id' => $this->school->id, 'name' => (string) now()->year, 'description' => 'AY', 'status' => 1,
            'start_date' => now()->startOfYear()->toDateString(), 'end_date' => now()->endOfYear()->toDateString(),
        ]);
    }

    public function test_the_five_teacher_tiles_use_the_admin_tile_component_and_styles(): void
    {
        $html = $this->actingAs($this->userFor(5))->get('/teacher/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('data-testid="dashboard-kpi-grid"', $html);
        $this->assertStringContainsString('class="dv2-kpis dv2-kpis--five"', $html);
        $this->assertStringContainsString('css/dashboard-v2.css', $html, 'the admin dashboard stylesheet is loaded');
        $this->assertStringNotContainsString('ds-kpi-card', $html, 'the old teacher-only card is gone');

        foreach (['kpi-my-students' => 'My Students', 'kpi-my-classes' => 'My Classes', 'kpi-upcoming-exams' => 'Upcoming Exams', 'kpi-whatsapp-linked' => 'WhatsApp Linked', 'kpi-marks-needing-you' => 'Marks needing you'] as $testid => $label) {
            $this->assertMatchesRegularExpression('#<div[^>]*data-testid="'.$testid.'"[^>]*>\s*<(?:a|div) class="dv2-kpi-link".*?'.preg_quote($label, '#').'#s', $html, $label);
            $this->assertMatchesRegularExpression('#<div[^>]*class="dv2-kpi[ "][^>]*data-testid="'.$testid.'"|<div[^>]*data-testid="'.$testid.'"[^>]*class="dv2-kpi[ "]#', $html, $label.' is a dv2-kpi tile');
        }
    }

    public function test_the_tile_markup_uses_the_same_class_names_as_the_admin_dashboard(): void
    {
        $admin = file_get_contents(resource_path('views/admin/dashboard/dashboard_v2.blade.php'));
        $html = Blade::render('<x-dv2-kpi icon="graduation-cap" label="My Students" :value="3" sub="in your classes" href="/teacher/classes" />');

        foreach (['dv2-kpi', 'dv2-kpi-link', 'dv2-kpi-label', 'dv2-kpi-value', 'dv2-kpi-sub'] as $class) {
            $this->assertStringContainsString('class="'.$class, $html, $class.' in the component');
            $this->assertStringContainsString($class, $admin, $class.' is what the admin dashboard uses');
        }
        // The icon is the admin's too: a muted 16px Lucide icon beside the label.
        $this->assertStringContainsString('ka-icon--muted', $html);
        $this->assertStringContainsString('width="16"', $html);
    }

    public function test_a_tile_without_a_link_is_not_clickable_and_a_waiting_tile_gets_the_admin_amber_treatment(): void
    {
        $plain = Blade::render('<x-dv2-kpi label="WhatsApp Linked" :value="0" />');
        $this->assertStringNotContainsString('<a ', $plain);
        $this->assertStringContainsString('cursor:default', $plain);

        $waiting = Blade::render('<x-dv2-kpi label="Marks needing you" :value="4" sub="Open my exams" sub-tone="warn" :waiting="true" href="/teacher/exam/marks" />');
        $this->assertStringContainsString('dv2-kpi--waiting', $waiting);
        $this->assertStringContainsString('dv2-kpi-sub warn', $waiting);
        $this->assertStringContainsString('aria-label="Marks needing you, 4 Open my exams, open"', $waiting);
    }
}
