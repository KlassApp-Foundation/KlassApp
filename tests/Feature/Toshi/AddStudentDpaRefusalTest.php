<?php

namespace Tests\Feature\Toshi;

use App\AiAgents\Tools\AddStudentTool;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Onboarding\OnboardingDpaGate;
use App\Services\Toshi\WriteContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

/**
 * A DPA refusal must reach the person as a message, not as an exception that
 * the panel turns into a 500. AddStudentTool is one of the two tools that write
 * through OnboardingEngine, so it is the one that meets the gate.
 */
class AddStudentDpaRefusalTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['onboarding.dpa_gate.enabled' => true]);

        $this->school = School::create([
            'name' => 'Refusal School',
            'email' => 'refusal@example.test',
            'phone' => '+256700999001',
            'slug' => 'refusal-school',
            'status' => 1,
            'registration_country' => 'Uganda',
            'curriculum' => 'uneb',
        ]);

        // Mid-onboarding and unconfirmed: the gate applies.
        $this->school->forceFill(['onboarding_dpa_required_at' => now()])->save();
        $this->school->refresh();

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Refusal Admin',
            'email' => 'refusal.admin@example.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Refusal',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2027',
            'type' => 'Current Academic Year',
            'description' => 'Current Academic Year',
            'start_date' => '2027-01-05',
            'end_date' => '2027-12-05',
            'status' => 1,
        ]);
    }

    public function test_a_dpa_refusal_comes_back_as_a_message_not_an_exception(): void
    {
        $this->actingAs($this->admin);

        $args = [
            'name' => 'Blocked Learner',
            'class' => 'Primary One',
            'email' => 'blocked.learner@example.test',
        ];

        // Confirmed at the tier-2 layer: the only refusal left is the DPA gate.
        $context = WriteContext::confirmed('toolAddStudent', $args, $this->admin);

        $result = (new AddStudentTool)->handle(new Request($args), $context);

        $this->assertIsString($result);
        $this->assertStringStartsWith('❌', $result);
        $this->assertStringContainsString('data processing agreement', $result);
        $this->assertDatabaseMissing('users', ['email' => 'blocked.learner@example.test']);
    }

    public function test_the_same_call_is_not_refused_for_dpa_once_it_is_recorded(): void
    {
        $this->actingAs($this->admin);

        app(OnboardingDpaGate::class)->record($this->school);

        $args = [
            'name' => 'Allowed Learner',
            'class' => 'Primary One',
            'email' => 'allowed.learner@example.test',
        ];

        $context = WriteContext::confirmed('toolAddStudent', $args, $this->admin);

        $result = (new AddStudentTool)->handle(new Request($args), $context);

        $this->assertStringNotContainsString('data processing agreement', (string) $result);
    }
}
