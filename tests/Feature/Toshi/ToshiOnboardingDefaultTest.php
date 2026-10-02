<?php

namespace Tests\Feature\Toshi;

use App\Enums\ToshiMode;
use App\Livewire\AgentToshi;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingStepsService;
use App\Services\SchoolSignupBootstrapService;
use App\Services\Toshi\ToshiUiSwitch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Configured signup default is preview (TOSHI_DEFAULT_MODE). An explicit
 * onboarding school still gets the scripted guide. Never auto-maximize.
 */
class ToshiOnboardingDefaultTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('plans')->upsert([
            [
                'id' => 1,
                'cycle' => 30,
                'name' => 'Freemium',
                'display_name' => 'Freemium',
                'order' => 1,
                'is_active' => 1,
                'amount' => 0,
                'no_of_students' => 0,
                'no_of_users' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], 'id');

        Config::set('ai.providers.openai-compatible.key', '');
        Config::set('toshi.api_key', '');
    }

    public function test_bootstrap_creates_school_in_configured_preview_mode(): void
    {
        $admin = app(SchoolSignupBootstrapService::class)->bootstrap([
            'name' => 'Ada Lovelace',
            'email' => 'ada-'.Str::random(6).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'password' => 'secret123',
        ]);

        $school = School::find($admin->school_id);
        $this->assertInstanceOf(School::class, $school);
        $this->assertSame(0, (int) $school->toshi_enabled);
        $this->assertSame(ToshiMode::Preview, $school->toshi_mode);

        $switch = app(ToshiUiSwitch::class);
        $this->assertFalse($switch->onboardingEnabled($admin->fresh()));
        $this->assertTrue($switch->previewMode($admin->fresh()));
        $this->assertFalse($switch->assistantEnabled($admin->fresh()));
    }

    public function test_school_create_omitting_toshi_mode_uses_configured_default(): void
    {
        $school = School::create([
            'name' => 'Default Mode School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
            // intentionally omit toshi_mode
        ]);

        $school->refresh();
        $this->assertSame(ToshiMode::Preview, $school->toshi_mode);

        Config::set('toshi.default_mode', 'onboarding');
        $onboarding = School::create([
            'name' => 'Onboarding Config School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);
        $this->assertSame(ToshiMode::Onboarding, $onboarding->fresh()->toshi_mode);
    }

    public function test_dashboard_shows_setup_ctas_without_auto_maximize(): void
    {
        $this->seedSchoolAdmin(ToshiMode::Onboarding);

        $response = $this->actingAs($this->admin->fresh())->get('/admin/dashboard');
        if ($response->isRedirect()) {
            $response = $this->actingAs($this->admin)->get($response->headers->get('Location'));
        }

        $response->assertOk();
        $response->assertSee('Set up manually', false);
        $response->assertSee('Set up with Toshi', false);
        // Auto-open script only — the Set up with Toshi button also dispatches maximize on click.
        $response->assertDontSee('Wave 3: open Toshi maximized', false);
    }

    public function test_session_open_toshi_flash_does_not_auto_maximize(): void
    {
        $this->seedSchoolAdmin(ToshiMode::Onboarding);

        $response = $this->actingAs($this->admin->fresh())
            ->withSession(['open_toshi_onboarding' => true])
            ->get('/admin/dashboard');
        if ($response->isRedirect()) {
            $response = $this->actingAs($this->admin)
                ->withSession(['open_toshi_onboarding' => true])
                ->get($response->headers->get('Location'));
        }

        $response->assertOk();
        $response->assertDontSee('Wave 3: open Toshi maximized', false);
        $this->assertFalse(session()->has('open_toshi_onboarding'));
    }

    public function test_unrecognised_freeform_mid_setup_keeps_step_and_flow_completable(): void
    {
        $this->seedSchoolAdmin(ToshiMode::Onboarding);

        // Leave school_name complete so complete-mode lands on student_size.
        $this->school->update(['name' => "Ada's Primary"]);
        $this->assertFalse(OnboardingStepsService::isStepComplete('student_size', $this->school->fresh()));

        $component = Livewire::actingAs($this->admin->fresh())
            ->test(AgentToshi::class)
            ->assertSet('mode', 'complete');

        // Drive to student_size action if not already there.
        if ($component->get('actionStep') !== 'onboarding_student_size') {
            $component->call('jumpToChecklistStep', 'student_size');
        }

        $component->assertSet('actionStep', 'onboarding_student_size');
        $stepBefore = $component->get('step');
        $actionBefore = $component->get('actionStep');

        $component->set('input', 'please just set everything up with AI')
            ->call('send')
            ->assertSet('mode', 'complete')
            ->assertSet('actionStep', $actionBefore)
            ->assertSet('step', $stepBefore);

        $botTexts = collect($component->get('messages'))
            ->where('role', 'bot')
            ->pluck('text')
            ->implode("\n");
        $this->assertStringContainsString(
            "Toshi's assistant is coming soon; for now, please choose one of the options above.",
            $botTexts,
        );
        $this->assertStringContainsString('Roughly how many students', $botTexts);

        // Valid chip answer still advances / completes the step.
        $component->set('input', 'Up to 500')->call('send');

        $this->school->refresh();
        $this->assertTrue(
            OnboardingStepsService::isStepComplete('student_size', $this->school),
            'Valid size answer after unrecognised free-form must still complete the step',
        );
        $this->assertSame('complete', $component->get('mode'));
        $this->assertNotSame('preview', $component->get('mode'));
    }

    public function test_setup_done_shows_coming_soon_card(): void
    {
        $this->seedSchoolAdmin(ToshiMode::Onboarding);

        Livewire::actingAs($this->admin->fresh())
            ->test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('step', 99)
            ->call('switchMode', 'assistant')
            ->assertSet('mode', 'preview')
            ->assertSee('Coming soon', false)
            ->assertSee('data-testid="toshi-preview-coming-soon"', false);
    }

    private function seedSchoolAdmin(ToshiMode $mode): void
    {
        $this->school = School::create([
            'name' => "Ada's School",
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
            'toshi_mode' => $mode,
            'curriculum' => null,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Ada Admin',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => Hash::make('secret123'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'firstname' => 'Ada',
            'lastname' => 'Admin',
        ]);
    }
}
