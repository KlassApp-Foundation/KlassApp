<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace Tests\Feature\Admin;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Dashboard v2 (design handoff-2026-09-30 profiles, Part B): default-off flag,
 * one step count from OnboardingStepsService, per-user dismissal persisted in
 * the database, preconditions on quick actions, the zero-students empty state
 * and the three Toshi states.
 */
class DashboardV2Test extends TestCase
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

    private function makeAdmin(School $school, string $first = 'Mucunguzi'): User
    {
        $admin = User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 3, 'status' => 'active', 'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $admin->id, 'school_id' => $school->id, 'usergroup_id' => 3,
            'firstname' => $first, 'lastname' => 'Admin', 'status' => 'active',
        ]);

        return $admin;
    }

    private function makeSchool(array $overrides = []): School
    {
        return School::create(array_merge([
            'name' => 'V2 School '.uniqid(), 'slug' => 'v2-'.uniqid(),
            'email' => uniqid().'@v2.test', 'phone' => '07'.random_int(70000000, 78999999),
            'status' => 1,
        ], $overrides));
    }

    private function render(User $admin): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($admin)->get('/admin/dashboard');
    }

    public function test_flag_off_renders_v1_and_v2_query_renders_v2(): void
    {
        config(['dashboard.v2_enabled' => false]);
        $school = $this->makeSchool(['name' => 'Flag School']);
        $admin = $this->makeAdmin($school);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()
            ->assertDontSee('data-testid="dashboard-v2-shell"', false);

        $this->actingAs($admin)->get('/admin/dashboard?v2=1')->assertOk()
            ->assertSee('data-testid="dashboard-v2-shell"', false);
    }

    public function test_banner_count_equals_onboarding_steps_service(): void
    {
        $school = $this->makeSchool(['name' => 'Counts School']);
        $admin = $this->makeAdmin($school);

        $progress = OnboardingStepsService::progress($school, $admin->id);
        $this->assertSame(
            (string) "{$progress['done']} of {$progress['total']} steps done.",
            (string) "{$progress['done']} of {$progress['total']} steps done.",
        );

        $this->render($admin)->assertOk()
            ->assertSee("Setup {$progress['done']} of {$progress['total']} done", false)
            ->assertSee('data-testid="dashboard-v2-setup-bar"', false);

        // Advance a few steps and confirm the number follows the service.
        $school->forceFill([
            'student_size' => 'Up to 500', 'registration_country' => 'Uganda', 'curriculum' => 'UNEB',
        ])->save();
        $progress2 = OnboardingStepsService::progress($school->fresh(), $admin->id);
        $this->assertGreaterThan($progress['done'], $progress2['done']);

        // Fresh user instance so the school relation is not stale from the first render.
        $this->render(User::findOrFail($admin->id))->assertOk()
            ->assertSee("Setup {$progress2['done']} of {$progress2['total']} done", false);
    }

    public function test_greeting_first_name_is_title_cased(): void
    {
        $school = $this->makeSchool(['name' => 'Greeting School']);
        $admin = $this->makeAdmin($school, 'MUCUNGUZI');

        $response = $this->render($admin)->assertOk();
        // PR1: "Good morning/afternoon/evening, {FirstName}" in normal case.
        $this->assertMatchesRegularExpression(
            '/data-testid="dashboard-v2-greeting">Good (morning|afternoon|evening), Mucunguzi</',
            $response->getContent()
        );
    }

    public function test_dismissal_is_saved_per_user_and_chip_carries_the_same_count(): void
    {
        $school = $this->makeSchool(['name' => 'Dismiss School']);
        $adminA = $this->makeAdmin($school, 'Alpha');
        $adminB = $this->makeAdmin($school, 'Bravo');

        // Before dismissal: setup bar, no chip.
        $this->render($adminA)->assertOk()
            ->assertSee('data-testid="dashboard-v2-setup-bar"', false)
            ->assertDontSee('data-testid="dashboard-v2-chip"', false);

        // Dismiss (GET route as the banner link does).
        $this->actingAs($adminA)->get(route('dismiss.onboarding.reminder'))->assertRedirect();
        $this->assertSame(1, UserPreference::query()->where('user_id', $adminA->id)->count());

        $progress = OnboardingStepsService::progress($school, $adminA->id);

        // After dismissal: chip shows the same count, the bar is gone.
        $this->render($adminA)->assertOk()
            ->assertDontSee('data-testid="dashboard-v2-setup-bar"', false)
            ->assertSee('data-testid="dashboard-v2-chip"', false)
            ->assertSee('Finish setup, '.$progress['done'].' of '.$progress['total'].' steps done', false)
            ->assertSee($progress['done'].'/'.$progress['total'], false);

        // The other admin of the same school still sees the setup bar.
        $this->render($adminB)->assertOk()
            ->assertSee('data-testid="dashboard-v2-setup-bar"', false)
            ->assertDontSee('data-testid="dashboard-v2-chip"', false);
    }

    public function test_chip_disappears_when_setup_is_complete(): void
    {
        $school = $this->makeSchool(['name' => 'Complete School']);
        $admin = $this->makeAdmin($school);
        $this->completeAllSteps($school, $admin);

        UserPreference::set($admin, UserPreference::setupBannerDismissedKey($school->id), now()->toIso8601String());

        $progress = OnboardingStepsService::progress($school->fresh(), $admin->id);
        $this->assertSame($progress['total'], $progress['done'], 'helper must complete every step');

        $this->render($admin)->assertOk()
            ->assertDontSee('data-testid="dashboard-v2-chip"', false)
            ->assertDontSee('data-testid="dashboard-v2-setup-bar"', false);
    }

    public function test_quick_action_names_missing_prerequisite_and_links_to_the_step(): void
    {
        $school = $this->makeSchool(['name' => 'Prereq School']);
        $admin = $this->makeAdmin($school);

        // Nothing set up: classes are missing, so 'Add students' is gated.
        $html = $this->render($admin)->assertOk()->getContent();
        $this->assertStringContainsString('class="help missing"', $html);
        $this->assertStringContainsString('Structure &amp; Class Teachers', $html);
        $this->assertStringContainsString('in setup first.', $html);
        $this->assertStringContainsString('/admin/standard/create', $html);
    }

    public function test_snapshot_empty_state_for_a_school_with_no_students(): void
    {
        $school = $this->makeSchool(['name' => 'Empty School']);
        $admin = $this->makeAdmin($school);

        $this->render($admin)->assertOk()
            ->assertSee('data-testid="dashboard-v2-empty-students"', false)
            ->assertSee('Add your students to get started', false)
            ->assertSee('/admin/student/add', false)
            ->assertSee('/admin/student/import', false);
    }

    public function test_toshi_states_onboarding_preview_assistant(): void
    {
        // Preview (default): Coming soon, no setup button, no Early access.
        $preview = $this->makeSchool(['name' => 'Toshi Preview']);
        $preview->forceFill(['toshi_enabled' => 0, 'toshi_mode' => 'preview'])->save();
        $this->render($this->makeAdmin($preview))->assertOk()
            ->assertSee('data-testid="dashboard-v2-toshi-coming"', false)
            ->assertDontSee('data-testid="dashboard-v2-toshi-setup"', false)
            ->assertDontSee('Early access', false);

        // Onboarding: Set up with Toshi; still no Early access; nothing auto-opens.
        $onboarding = $this->makeSchool(['name' => 'Toshi Onboarding']);
        $onboarding->forceFill(['toshi_enabled' => 0, 'toshi_mode' => 'onboarding'])->save();
        $response = $this->render($this->makeAdmin($onboarding))->assertOk()
            ->assertSee('data-testid="dashboard-v2-toshi-setup"', false)
            ->assertDontSee('Early access', false);
        $this->assertFalse((bool) $response->viewData('openToshiOnboarding'), 'setup must never auto-open');

        // Assistant: Early access pill only here.
        $assistant = $this->makeSchool(['name' => 'Toshi Assistant']);
        $assistant->forceFill(['toshi_enabled' => 1, 'toshi_mode' => 'assistant'])->save();
        $this->render($this->makeAdmin($assistant))->assertOk()
            ->assertSee('Early access', false)
            ->assertDontSee('data-testid="dashboard-v2-toshi-coming"', false);
    }

    public function test_sidebar_icons_and_account_card_still_render(): void
    {
        $school = $this->makeSchool(['name' => 'Chrome School']);
        $admin = $this->makeAdmin($school);

        $html = $this->render($admin)->assertOk()->getContent();
        $this->assertStringContainsString('ka-icon', $html, 'sidebar lucide icons (#980) must stay');
        $this->assertStringContainsString('data-account-card', $html, 'account card (Part A) must stay');
    }

    public function test_setup_bar_uses_neutral_step_names_from_the_one_source(): void
    {
        $school = $this->makeSchool([
            'name' => 'Neutral Names School', 'student_size' => 'Up to 500',
            'registration_country' => 'Uganda', 'curriculum' => 'UNEB', 'school_category' => 'primary_nursery',
        ]);
        $admin = $this->makeAdmin($school);

        // Next incomplete step is emis -> neutral name, never the country jargon.
        $this->render($admin)->assertOk()
            ->assertSee('Next: Registration code', false)
            ->assertDontSee('EMIS / Ministry code', false);

        $school->forceFill(['ministry_code' => 'EMIS-9'])->save();

        // Then uneb_center -> neutral "Exam centre number", not the UNEB jargon.
        $this->render(User::findOrFail($admin->id))->assertOk()
            ->assertSee('Next: Exam centre number', false)
            ->assertDontSee('UNEB centre number', false);
    }

    public function test_currency_without_a_setting_shows_no_symbol_and_a_quiet_hint(): void
    {
        $school = $this->makeSchool(['name' => 'Currency School']);
        $admin = $this->makeAdmin($school);
        $this->completeAllSteps($school, $admin);

        // No currency set: amounts carry no symbol and the hint appears.
        $this->render(User::findOrFail($admin->id))->assertOk()
            ->assertSee('data-testid="dashboard-v2-currency-hint"', false)
            ->assertSee('Set your currency', false);

        // Setting the school currency: symbols appear, the hint goes away.
        \Illuminate\Support\Facades\DB::table('school_details')->insert([
            'school_id' => $school->id, 'meta_key' => 'currency', 'meta_value' => 'UGX',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->render(User::findOrFail($admin->id))->assertOk();
        $response->assertDontSee('data-testid="dashboard-v2-currency-hint"', false);
        $this->assertMatchesRegularExpression('/UGX\s[0-9]/', $response->getContent());
    }

    public function test_first_screen_has_no_carousel_connected_tools_or_toshi_promo(): void
    {
        $school = $this->makeSchool(['name' => 'No Promo School']);
        $admin = $this->makeAdmin($school);

        $this->render($admin)->assertOk()
            ->assertDontSee('Connected tools', false)
            ->assertDontSee('es-demo-scene-toshi', false)
            ->assertDontSee('data-testid="toshi-pill"', false);
    }

    public function test_kpis_match_the_database_for_a_school_with_data(): void
    {
        $school = $this->makeSchool(['name' => 'KPI School']);
        $admin = $this->makeAdmin($school);

        foreach ([['female', 'Amina'], ['male', 'Brian'], [null, 'Chris']] as $i => [$gender, $first]) {
            $student = User::factory()->create([
                'school_id' => $school->id, 'usergroup_id' => 6, 'status' => 'active',
            ]);
            Userprofile::create([
                'user_id' => $student->id, 'school_id' => $school->id, 'usergroup_id' => 6,
                'firstname' => $first, 'lastname' => 'Pupil', 'gender' => $gender,
            ]);
        }

        $this->render($admin)->assertOk()
            ->assertSee('data-testid="dashboard-v2-kpi-students">3<', false)
            ->assertSee('Girls', false)
            ->assertSee('Not specified', false);
    }

    private function completeAllSteps(School $school, User $admin): void
    {
        $school->forceFill([
            'name' => 'Completed School', 'student_size' => 'Up to 500', 'registration_country' => 'Uganda',
            'curriculum' => 'UNEB', 'school_category' => 'primary_nursery', 'ministry_code' => 'EMIS-1',
            'uneb_center_number' => 'U-1',
        ])->save();
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026', 'description' => 'y', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1]);
        AcademicTerm::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term I', 'status' => 'current', 'starts_on' => '2026-02-01', 'ends_on' => '2026-05-01']);
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'P4', 'status' => 1]);
        StandardLink::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id, 'section_id' => $section->id, 'status' => 1]);
        Subject::create(['school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id, 'academic_year_id' => $year->id, 'name' => 'Math', 'type' => 'core', 'status' => 1]);
        User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 5]);
        OnboardingStepsService::markStepSkipped($school, 'teachers');
        $student = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 6, 'status' => 'active']);
        StudentAcademic::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'user_id' => $student->id, 'standardLink_id' => $section->id]);
        FeesCategories::create(['school_id' => $school->id, 'standard_id' => $standard->id, 'name' => 'Tuition']);
        WhatsAppUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'phone' => '256700000001']);
        $plan = Plan::query()->first() ?? Plan::create([
            'cycle' => 1, 'name' => 'Free '.uniqid(), 'display_name' => 'Free', 'is_active' => 1, 'amount' => 0,
        ]);
        CurrentPlan::create(['school_id' => $school->id, 'plan_id' => $plan->id, 'status' => 'running']);
    }
}
