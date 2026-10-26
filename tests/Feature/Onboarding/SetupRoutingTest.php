<?php

namespace Tests\Feature\Onboarding;

use App\Enums\ToshiMode;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetupRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_a_new_school_resolves_to_the_first_setup_step(): void
    {
        [$school, $admin] = $this->school("Ada's School", 'ada@setup-route.test');

        $step = OnboardingEngine::earliestUnfinishedStep($school, $admin->id);
        $destination = OnboardingEngine::setupDestination($school, $admin->id);

        $this->assertSame('school_name', $step['key'] ?? null);
        $this->assertNotNull($destination);
        $this->assertStringContainsString('step=school_name', $destination);
        $this->assertStringNotContainsString('schooldetails', $destination);

        $school->forceFill([
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
        ])->save();

        foreach (['school_name', 'student_size', 'country', 'curriculum', 'school_category', 'emis', 'uneb_center'] as $key) {
            $row = collect(OnboardingStepsService::steps($school->fresh(), $admin->id))->firstWhere('key', $key);
            $this->assertNotNull($row, $key);
            $this->assertNull($row['route'], $key.' must not point at Edit School Details');
        }
    }

    public function test_continue_setup_uses_the_same_step_as_the_resolver(): void
    {
        [$school, $admin] = $this->school('Greenfield Primary', 'greenfield@setup-route.test');

        $destination = OnboardingEngine::setupDestination($school, $admin->id);
        $this->assertNotNull($destination);
        $this->assertStringContainsString('step=student_size', $destination);

        $html = $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->getContent();

        $this->assertIsString($html);
        $this->assertMatchesRegularExpression(
            '#href="'.preg_quote($destination, '#').'"[^>]*data-testid="dashboard-v2-setup-continue"#',
            $html,
        );
        $this->assertDoesNotMatchRegularExpression(
            '#data-testid="dashboard-v2-setup-continue"[^>]*schooldetails|schooldetails[^>]*data-testid="dashboard-v2-setup-continue"#',
            $html,
        );
        $this->assertStringContainsString('School name: Greenfield Primary', $html);
        $this->assertStringContainsString("What's set up", $html);
        $this->assertStringContainsString('Approximate school size', $html);
    }

    public function test_a_finished_school_is_not_sent_back_into_setup(): void
    {
        [$school, $admin] = $this->school('Finished Primary', 'finished@setup-route.test');
        $school->forceFill([
            'toshi_mode' => ToshiMode::Onboarding,
            'toshi_enabled' => 0,
            'is_test' => 0,
            'onboarding_finished_at' => now(),
        ])->save();

        $this->assertNull(OnboardingEngine::setupDestination($school->fresh(), $admin->id));

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($admin)->get('/admin/dashboard?toshi_onboarding=1')->assertOk();
        $this->actingAs($admin)
            ->get('/welcome')
            ->assertRedirect('/admin/dashboard');
    }

    /**
     * @return array{0: School, 1: User}
     */
    private function school(string $name, string $email): array
    {
        $school = School::create([
            'name' => $name,
            'email' => $email,
            'phone' => '0700'.random_int(100000, 999999),
            'slug' => 'setup-'.substr(md5($email), 0, 12),
            'status' => 1,
            'toshi_enabled' => 0,
            'toshi_mode' => config('toshi.default_mode'),
            'curriculum' => null,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Setup Admin',
            'email' => 'admin-'.$email,
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Setup',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        return [$school->fresh(), $admin];
    }
}
