<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\AgentToshi;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regression coverage for Toshi resume/jump when school_category is incomplete.
 * Any OnboardingStepsService key routed through actionMap must stay registered in
 * jumpToIncompleteOnboardingStep() — missing entries fail silently with the
 * generic "Let's continue setting up." prompt.
 */
class ToshiSchoolCategoryJumpResumeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Jump Resume Test School',
            'email' => 'jump-resume@test.sch.ug',
            'phone' => '0700000001',
            'slug' => 'jump-resume-test-school',
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'school_category' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Jump Resume Admin',
            'email' => 'admin@jump-resume.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Jump Resume Admin',
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'type' => 'Current Academic Year',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
        ]);
    }

    /** @test */
    public function mount_jumps_to_school_category_action_with_actionable_prompt(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);

        $component->assertSet('mode', 'complete');
        $component->assertSet('actionStep', 'onboarding_school_category');

        $botText = collect($component->get('messages'))
            ->where('role', 'bot')
            ->pluck('text')
            ->implode("\n");

        $this->assertStringContainsString('school category', strtolower($botText));
        $this->assertStringNotContainsString("Let's continue setting up.", $botText);
    }

    /** @test */
    public function complete_mode_select_school_category_persists_and_clears_action_step(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $component->call('selectSchoolCategory', 'primary');

        $this->assertSame('primary', $this->school->fresh()->school_category);
        $this->assertSame('onboarding_emis', $component->get('actionStep'));
    }

    /** @test */
    public function complete_mode_text_input_on_school_category_action_persists_category(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $component
            ->set('actionStep', 'onboarding_school_category')
            ->set('input', 'Primary + Nursery')
            ->call('send');

        $this->assertSame('primary_nursery', $this->school->fresh()->school_category);
    }

    /**
     * Template guard: every key that uses the action-step resume path must stay
     * registered in jumpToIncompleteOnboardingStep()'s actionMap.
     *
     * @test
     * @dataProvider actionMappedOnboardingStepProvider
     */
    public function jump_to_incomplete_onboarding_step_maps_action_steps(string $key, string $expectedActionStep): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $method = new ReflectionMethod(AgentToshi::class, 'jumpToIncompleteOnboardingStep');
        $method->setAccessible(true);
        $method->invoke($component->instance(), $key);

        $this->assertSame($expectedActionStep, $component->get('actionStep'));
    }

    public static function actionMappedOnboardingStepProvider(): array
    {
        return [
            'curriculum' => ['curriculum', 'onboarding_curriculum'],
            'country' => ['country', 'onboarding_country'],
            'school_category' => ['school_category', 'onboarding_school_category'],
            'emis' => ['emis', 'onboarding_emis'],
            'uneb_center' => ['uneb_center', 'onboarding_uneb_center'],
            'plan_selection' => ['plan_selection', 'onboarding_plan_selection'],
        ];
    }
}
