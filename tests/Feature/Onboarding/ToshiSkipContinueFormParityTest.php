<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\AgentToshi;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Continue-form free-text must match Continue / Skip this step buttons.
 * Regression: teachers/students/fees/exams set substep=6 for the inline form;
 * handlers only handled 0/1, so typed "skip" was a silent no-op.
 *
 * Plan selection must land on the create-flow step (cards visible) whether
 * resumed via checklist jump or the progress sidebar — not a text-only actionStep.
 */
class ToshiSkipContinueFormParityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Skip Continue School',
            'email' => 'skip-continue@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'skip-continue-school',
            'status' => 1,
            'curriculum' => 'uneb',
            'school_category' => 'primary',
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Skip Admin',
            'email' => 'admin@skip-continue.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Skip',
            'lastname' => 'Admin',
        ]);

        Plan::create([
            'name' => 'freemium',
            'display_name' => 'Freemium',
            'amount' => 0,
            'no_of_students' => 100,
            'no_of_users' => 5,
            'cycle' => 365,
            'is_active' => 1,
            'order' => 1,
        ]);
    }

    public function test_typed_skip_on_teacher_continue_form_advances(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('scope', 'school');

        $steps = $component->get('steps');
        $teachersIdx = array_search('teachers', $steps, true);
        $this->assertNotFalse($teachersIdx);

        $component
            ->set('step', $teachersIdx)
            ->call('showTeacherFormFn')
            ->assertSet('showTeacherForm', true)
            ->assertSet('substep', 6);

        $beforeStep = $component->get('step');
        $component->set('input', 'skip')->call('send');

        $this->assertFalse((bool) $component->get('showTeacherForm'));
        $this->assertNotSame($beforeStep, $component->get('step'), 'typed skip must advance past teachers');
    }

    public function test_skip_step_button_on_teacher_continue_form_advances(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('scope', 'school');

        $steps = $component->get('steps');
        $teachersIdx = array_search('teachers', $steps, true);
        $this->assertNotFalse($teachersIdx);

        $component
            ->set('step', $teachersIdx)
            ->call('showTeacherFormFn');

        $beforeStep = $component->get('step');
        $component->call('skipStep');

        $this->assertFalse((bool) $component->get('showTeacherForm'));
        $this->assertNotSame($beforeStep, $component->get('step'));
    }

    public function test_typed_continue_on_empty_teacher_form_matches_continue_button(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('scope', 'school');

        $steps = $component->get('steps');
        $teachersIdx = array_search('teachers', $steps, true);
        $component
            ->set('step', $teachersIdx)
            ->call('showTeacherFormFn');

        $beforeStep = $component->get('step');
        $component->set('input', 'continue')->call('send');

        $this->assertFalse((bool) $component->get('showTeacherForm'));
        $this->assertNotSame($beforeStep, $component->get('step'));
    }

    public function test_plan_selection_jump_and_sidebar_share_step_index_for_cards(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('scope', 'school');

        $steps = $component->get('steps');
        $planIdx = array_search('plan_selection', $steps, true);
        $this->assertNotFalse($planIdx);

        // Checklist resume path
        $method = new ReflectionMethod(AgentToshi::class, 'jumpToIncompleteOnboardingStep');
        $method->setAccessible(true);
        $method->invoke($component->instance(), 'plan_selection');

        $this->assertNull($component->get('actionStep'));
        $this->assertSame($planIdx, $component->get('step'));

        // Sidebar path: clear leftover actionStep and keep step/cards
        $component
            ->set('actionStep', 'onboarding_plan_selection')
            ->set('step', $planIdx)
            ->call('jumpToStep', $planIdx);

        $this->assertNull($component->get('actionStep'));
        $this->assertSame($planIdx, $component->get('step'));

        $freemium = Plan::where('name', 'freemium')->firstOrFail();
        $component->call('selectPlan', $freemium->id);
        $this->assertSame($freemium->id, (int) $component->get('selectedPlanId'));
    }
}
