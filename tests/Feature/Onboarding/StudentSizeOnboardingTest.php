<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\AgentToshi;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StudentSizeOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => "Size's School",
            'email' => 'size@test.sch.ug',
            'phone' => '0700000055',
            'slug' => 'size-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Size Admin',
            'email' => 'admin@size.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Size',
            'lastname' => 'Admin',
        ]);
    }

    public function test_student_size_options_match_auth_onboarding_buckets(): void
    {
        $this->assertSame([
            'Under 100 students',
            '100-300 students',
            '300-500 students',
            '500+ students',
        ], OnboardingStepsService::STUDENT_SIZE_OPTIONS);
    }

    public function test_student_size_step_is_early_in_checklist_before_structure_steps(): void
    {
        $keys = array_keys(OnboardingStepsService::ALL_STEPS);

        $this->assertSame('school_name', $keys[0]);
        $this->assertSame('student_size', $keys[1]);

        $sizeIdx = array_search('student_size', $keys, true);
        $ayIdx = array_search('academic_year', $keys, true);
        $standardsIdx = array_search('standards', $keys, true);

        $this->assertNotFalse($sizeIdx);
        $this->assertNotFalse($ayIdx);
        $this->assertNotFalse($standardsIdx);
        $this->assertLessThan($ayIdx, $sizeIdx);
        $this->assertLessThan($standardsIdx, $sizeIdx);
    }

    public function test_save_student_size_persists_valid_option(): void
    {
        app(OnboardingEngine::class)->saveStudentSize($this->school, '300-500 students');

        $this->assertSame('300-500 students', $this->school->fresh()->student_size);
        $this->assertTrue(OnboardingStepsService::isStepComplete('student_size', $this->school->fresh()));
    }

    public function test_save_student_size_rejects_unknown_value(): void
    {
        try {
            app(OnboardingEngine::class)->saveStudentSize($this->school, '100 to 500 students');
            $this->fail('Expected ValidationException for superadmin-only bucket.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('studentSize', $e->errors());
        }

        $this->assertNull($this->school->fresh()->student_size);
    }

    public function test_wizard_persists_student_size_on_next(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ManualOnboardingWizard::class)
            ->set('schoolName', 'Size Collect Academy')
            ->call('next')
            ->assertSee('Approximate school size')
            ->set('studentSize', '500+ students')
            ->call('next')
            ->assertSee('Country');

        $this->assertSame('500+ students', $this->school->fresh()->student_size);
    }

    public function test_toshi_complete_mode_student_size_action_persists(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('actionStep', 'onboarding_student_size')
            ->set('input', '100-300')
            ->call('send');

        $this->assertSame('100-300 students', $this->school->fresh()->student_size);
    }

    public function test_toshi_create_mode_student_size_step_stores_property_and_advances(): void
    {
        $this->actingAs($this->admin);

        $sizeIdx = array_search('student_size', (new AgentToshi)->steps, true);
        $this->assertNotFalse($sizeIdx);

        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'create')
            ->set('step', $sizeIdx)
            ->set('substep', 0)
            ->set('input', '500+')
            ->call('send');

        $component->assertSet('studentSize', '500+ students');
        $this->assertSame(
            'country',
            $component->instance()->steps[$component->get('step')] ?? null
        );
    }
}
