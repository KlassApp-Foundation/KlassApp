<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicYear;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Drives the live AgentToshi Livewire component through the new onboarding
 * steps (country / EMIS / UNEB centre) and the end-of-flow plan selection,
 * plus verifies plan-based truncation of students was removed.
 */
class ToshiCountryEmisPlanFlowTest extends TestCase
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
            'name' => 'Flow Test School',
            'email' => 'flow@test.sch.ug',
            'phone' => '0700000000',
            'slug' => 'flow-test-school',
            'status' => 1,
            'curriculum' => 'uneb',
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Flow Admin',
            'email' => 'admin@flow.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Flow Admin',
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
    public function complete_mode_country_action_persists_registration_country_and_country_id(): void
    {
        $country = \App\Models\Country::create([
            'name' => 'Uganda', 'short_name' => 'UG', 'status' => 1, 'order' => 1,
        ]);

        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('actionStep', 'onboarding_country');
        $component->set('input', 'Uganda')->call('send');

        $fresh = $this->school->fresh();
        $this->assertSame('Uganda', $fresh->registration_country);
        $this->assertSame($country->id, (int) $fresh->country_id);
    }

    /** @test */
    public function complete_mode_emis_action_persists_ministry_code_for_uganda(): void
    {
        $this->school->update(['registration_country' => 'Uganda']);

        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('schoolCountry', 'Uganda');
        $component->set('actionStep', 'onboarding_emis');
        $component->set('input', 'EMIS-77123')->call('send');

        $this->assertSame('EMIS-77123', $this->school->fresh()->ministry_code);
    }

    /** @test */
    public function complete_mode_uneb_center_skip_persists_empty_string_and_never_blocks(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('actionStep', 'onboarding_uneb_center');
        $component->set('input', 'skip')->call('send');

        // Skipped -> stored as '' (not null), which counts as complete.
        $this->assertSame('', $this->school->fresh()->uneb_center_number);
        $this->assertTrue(
            \App\Services\OnboardingStepsService::isUnebCenterComplete($this->school->fresh())
        );
    }

    /** @test */
    public function complete_mode_uneb_center_stores_provided_value(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('actionStep', 'onboarding_uneb_center');
        $component->set('input', 'U0123')->call('send');

        $this->assertSame('U0123', $this->school->fresh()->uneb_center_number);
    }

    /** @test */
    public function complete_mode_plan_selection_creates_current_plan_at_end_with_override(): void
    {
        // Freemium fits 50 students; the school already has 120 active students.
        $freemium = Plan::create([
            'name' => 'freemium', 'display_name' => 'Freemium', 'amount' => 0,
            'no_of_students' => 50, 'no_of_users' => 5, 'cycle' => 365, 'is_active' => 1, 'order' => 1,
        ]);
        Plan::create([
            'name' => 'premium', 'display_name' => 'Premium', 'amount' => 150000,
            'no_of_students' => 0, 'no_of_users' => 0, 'cycle' => 365, 'is_active' => 1, 'order' => 2,
        ]);

        foreach (range(1, 120) as $i) {
            User::create([
                'school_id' => $this->school->id, 'usergroup_id' => 6,
                'name' => "Student {$i}", 'email' => "s{$i}@flow.sch.ug",
                'password' => bcrypt('password'), 'status' => 'active', 'email_verified' => 1,
            ]);
        }

        $this->assertFalse(
            \App\Services\OnboardingStepsService::isStepComplete('plan_selection', $this->school),
            'plan_selection must be incomplete until a plan is chosen at the end'
        );

        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);

        // Admin overrides the suggestion and picks the smaller Freemium tier.
        $component->call('selectPlan', $freemium->id);

        $cp = CurrentPlan::where('school_id', $this->school->id)->first();
        $this->assertNotNull($cp, 'CurrentPlan must be created at plan-selection time');
        $this->assertSame($freemium->id, (int) $cp->plan_id, 'Any tier override must be honored');

        $this->assertTrue(
            \App\Services\OnboardingStepsService::isStepComplete('plan_selection', $this->school->fresh())
        );

        $this->assertDatabaseHas('subscriptions', [
            'school_id' => $this->school->id,
            'plan_id' => $freemium->id,
        ]);
    }

    /** @test */
    public function create_mode_commit_does_not_truncate_students_below_plan_limit(): void
    {
        // A tiny plan that historically would have truncated the student list.
        $plan = Plan::create([
            'name' => 'freemium', 'display_name' => 'Freemium', 'amount' => 0,
            'no_of_students' => 1, 'no_of_users' => 1, 'cycle' => 365, 'is_active' => 1, 'order' => 1,
        ]);

        $superadmin = User::create([
            'school_id' => null, 'usergroup_id' => 1, 'name' => 'Super Admin',
            'email' => 'super@flow.sch.ug', 'password' => bcrypt('password'),
            'status' => 'active', 'email_verified' => 1,
        ]);
        $this->actingAs($superadmin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('mode', 'create');
        $component->set('schoolName', 'No Truncate School');
        $component->set('schoolEmail', 'notruncate@test.sch.ug');
        $component->set('schoolPhone', '0700111333');
        $component->set('schoolCountry', 'Uganda');
        $component->set('schoolType', 'primary');
        $component->set('curriculum', 'uneb');
        $component->set('academicYearLabel', date('Y'));
        $component->set('adminName', 'NT Admin');
        $component->set('adminEmail', 'nt-admin@notruncate.sch.ug');
        $component->set('adminPassword', 'password123');
        $component->set('selectedPlanId', $plan->id);
        $component->set('standards', [['name' => 'P1']]);
        $component->set('subjects', []);
        $component->set('teacherList', ['Teacher One', 'Teacher Two', 'Teacher Three']);
        $component->set('teacherLinks', []);
        $component->set('terms', []);
        $component->set('fees', []);
        $component->set('studentList', ['Alice NT', 'Bob NT', 'Carol NT']);
        $component->set('actionData', [
            'students' => [
                ['name' => 'Alice NT', 'class' => 'P1'],
                ['name' => 'Bob NT', 'class' => 'P1'],
                ['name' => 'Carol NT', 'class' => 'P1'],
            ],
        ]);

        $component->call('commit');

        $schoolId = $component->get('schoolId');
        $this->assertNotNull($schoolId, 'Create-mode commit must create a school');

        // All 3 students created despite plan.no_of_students = 1 (no truncation).
        $this->assertSame(3, User::where('school_id', $schoolId)->where('usergroup_id', 6)->count());
        // All 3 teachers created despite plan.no_of_users = 1 (no truncation).
        $this->assertSame(3, User::where('school_id', $schoolId)->where('usergroup_id', 5)->count());
    }
}
