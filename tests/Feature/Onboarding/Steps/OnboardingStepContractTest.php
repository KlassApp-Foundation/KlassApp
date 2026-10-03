<?php

namespace Tests\Feature\Onboarding\Steps;

use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Onboarding\Steps\StepRegistry;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class OnboardingStepContractTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private StepRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => "Contract's School",
            'email' => 'contract-steps@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'contract-steps',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 0,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Contract Admin',
            'email' => 'admin@contract-steps.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);

        $this->registry = app(StepRegistry::class);
    }

    public function test_registry_keys_match_all_steps_service(): void
    {
        $keys = array_map(fn ($s) => $s->key(), $this->registry->all());
        $this->assertSame(array_keys(OnboardingStepsService::ALL_STEPS), $keys);
    }

    public function test_uganda_uneb_registry_includes_emis_category_and_centre(): void
    {
        $this->school->forceFill([
            'registration_country' => 'Uganda',
            'curriculum' => 'uneb',
        ])->save();

        $keys = array_map(fn ($s) => $s->key(), $this->registry->forSchool($this->school->fresh()));
        $this->assertContains('emis', $keys);
        $this->assertContains('school_category', $keys);
        $this->assertContains('uneb_center', $keys);
    }

    public function test_non_uganda_registry_drops_emis(): void
    {
        $this->school->forceFill([
            'registration_country' => 'Kenya',
            'curriculum' => 'other',
        ])->save();

        $keys = array_map(fn ($s) => $s->key(), $this->registry->forSchool($this->school->fresh()));
        $this->assertNotContains('emis', $keys);
        $this->assertNotContains('school_category', $keys);
        $this->assertNotContains('uneb_center', $keys);
    }

    public function test_each_applicable_step_exposes_contract_surface(): void
    {
        $this->school->forceFill([
            'registration_country' => 'Uganda',
            'curriculum' => 'uneb',
        ])->save();

        foreach ($this->registry->forSchool($this->school->fresh()) as $step) {
            $this->assertNotSame('', $step->question());
            $this->assertNotSame('', $step->inputType());
            $this->assertIsBool($step->required());
            $this->assertIsBool($step->applies($this->school));
            $this->assertIsBool($step->isComplete($this->school, $this->admin->id));
            $this->assertIsArray($step->options($this->school));
        }
    }

    public function test_school_name_normalize_validate_save_complete(): void
    {
        $step = $this->registry->byKey('school_name');
        $this->assertNotNull($step);

        $normalized = $step->normalize('  Registry Academy  ');
        $this->assertSame('Registry Academy', $normalized);

        try {
            $step->validate($this->school, '');
            $this->fail('Expected InvalidArgumentException for empty school name');
        } catch (InvalidArgumentException) {
            // expected
        }

        $step->save($this->school, $normalized);
        $this->school->refresh();
        $this->assertSame('Registry Academy', $this->school->name);
        $this->assertTrue($step->isComplete($this->school->fresh()));
    }

    public function test_student_size_and_country_and_curriculum_save(): void
    {
        $this->registry->byKey('school_name')->save($this->school, 'Registry Academy');
        $this->registry->byKey('student_size')->save($this->school->fresh(), 'Up to 500');
        $this->registry->byKey('country')->save($this->school->fresh(), 'Uganda');
        $this->registry->byKey('curriculum')->save($this->school->fresh(), 'UNEB');

        $school = $this->school->fresh();
        $this->assertSame('Up to 500', $school->student_size);
        $this->assertTrue(OnboardingStepsService::isUganda($school->registration_country));
        $this->assertSame('uneb', $school->curriculum);
        $this->assertTrue($this->registry->byKey('student_size')->isComplete($school));
        $this->assertTrue($this->registry->byKey('country')->isComplete($school));
        $this->assertTrue($this->registry->byKey('curriculum')->isComplete($school));
    }

    public function test_optional_teachers_skip_marks_complete(): void
    {
        $step = $this->registry->byKey('teachers');
        $this->assertFalse($step->required());
        $step->save($this->school->fresh(), 'skip');
        $this->assertTrue($step->isComplete($this->school->fresh()));
        $this->assertTrue(OnboardingStepsService::wasStepSkipped($this->school->fresh(), 'teachers'));
    }

    public function test_next_unfinished_advances_after_saves(): void
    {
        $this->assertSame('school_name', $this->registry->nextUnfinished($this->school)->key());
        $this->registry->byKey('school_name')->save($this->school, 'Registry Academy');
        $this->assertSame('student_size', $this->registry->nextUnfinished($this->school->fresh())->key());
    }
}
