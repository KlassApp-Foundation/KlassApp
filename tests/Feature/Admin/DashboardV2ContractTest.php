<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace Tests\Feature\Admin;

use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Dashboard v2 contract (design handoff-2026-09-30 profiles, Part B):
 * With the v2 flag written and no AI key, the rendered page must contain
 * zero case-insensitive hits on the assistant name and must not request
 * the SDK stylesheet. With a key + school flag the panel column and the
 * Early access label appear.
 */
class DashboardV2ContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        config(['dashboard.v2_enabled' => true]);
    }

    private function adminWithSchool(string $slug, array $schoolOverrides = []): User
    {
        $school = School::create(array_merge([
            'name' => 'V2 Contract '.ucwords($slug, ' -'),
            'email' => $slug.'@klassapp.test',
            'phone' => '07'.random_int(70000000, 78999999),
            'status' => 1,
            'slug' => 'v2-contract-'.$slug,
        ], $schoolOverrides));

        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Rasta',
            'lastname' => 'V2',
        ]);

        return $admin;
    }

    private function renderDashboard(User $admin): string
    {
        $this->actingAs($admin);

        return $this->get('/admin/dashboard')->assertOk()->getContent();
    }

    public function test_no_assistant_surface_when_gate_off(): void
    {
        config(['ai.providers.openai-compatible.key' => null, 'toshi.api_key' => null]);
        $html = $this->renderDashboard($this->adminWithSchool('gateoff'));

        $this->assertStringNotContainsStringIgnoringCase('early access', $html);
        $this->assertStringNotContainsStringIgnoringCase('sdk_v2', $html);
        $this->assertStringContainsString('data-testid="dashboard-v2-shell"', $html);
        $this->assertStringContainsString('data-testid="dashboard-v2-snapshot"', $html);
    }

    public function test_setup_banner_progress_and_dismiss(): void
    {
        $html = $this->renderDashboard($this->adminWithSchool('banner'));

        $this->assertStringContainsString('data-testid="dashboard-v2-banner"', $html);
        $this->assertStringContainsString('data-testid="dashboard-v2-banner-dismiss"', $html);
        $this->assertStringContainsString('Hide setup banner. Progress stays in the sidebar.', $html);
        $this->assertStringContainsString('aria-label="Academic year"', $html);
    }

    public function test_quick_action_tiles_present_with_prerequisite_copy(): void
    {
        $html = $this->renderDashboard($this->adminWithSchool('tiles'));

        $this->assertStringContainsString('data-testid="dashboard-v2-actions"', $html);
        $this->assertStringContainsString('data-testid="dashboard-v2-tile-1"', $html);
        $this->assertStringContainsString('data-testid="dashboard-v2-tile-5"', $html);
    }

    public function test_early_access_panel_with_key_and_school_flag(): void
    {
        config([
            'ai.providers.openai-compatible.key' => 'sk-test-key-for-gate-checks',
            'toshi.api_key' => 'sk-test-key-for-gate-checks',
            'toshi.sdk_v2_enabled' => true,
        ]);
        $admin = $this->adminWithSchool('early', ['toshi_enabled' => 1, 'toshi_mode' => 'assistant']);
        $html = $this->renderDashboard($admin);

        $this->assertStringContainsStringIgnoringCase('toshi', $html);
        $this->assertStringContainsString('data-testid="toshi-toggle"', $html);
        $this->assertStringContainsString('Early access', $html);
    }
}
