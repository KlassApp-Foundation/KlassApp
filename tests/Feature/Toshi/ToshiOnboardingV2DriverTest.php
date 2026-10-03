<?php

namespace Tests\Feature\Toshi;

use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\AgentToshi;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Onboarding\Steps\ToshiOnboardingV2Driver;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ToshiOnboardingV2DriverTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        config(['toshi.onboarding_v2' => true]);

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

        $this->school = School::create([
            'name' => "V2 Admin's School",
            'email' => 'v2-onboard@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'v2-onboard',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 0,
            'toshi_mode' => 'onboarding',
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'V2 Admin',
            'email' => 'admin@v2-onboard.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    public function test_driver_advances_early_steps_and_rejects_bad_curriculum(): void
    {
        $driver = app(ToshiOnboardingV2Driver::class);

        $this->assertSame('school_name', $driver->promptNext($this->school, $this->admin->id)['key']);

        $advanced = $driver->handleReply($this->school, 'V2 Registry Primary', $this->admin->id);
        $this->assertSame('advanced', $advanced['status']);
        $this->assertSame('student_size', $advanced['key']);
        $this->assertSame('V2 Registry Primary', $this->school->fresh()->name);

        $driver->handleReply($this->school->fresh(), 'Up to 500', $this->admin->id);
        $driver->handleReply($this->school->fresh(), 'Uganda', $this->admin->id);

        $rejected = $driver->handleReply($this->school->fresh(), 'not-a-board', $this->admin->id);
        $this->assertSame('rejected', $rejected['status']);
        $this->assertSame(ToshiOnboardingV2Driver::COMING_SOON_HINT, $rejected['hint']);
        $this->assertSame('curriculum', $rejected['key']);

        $ok = $driver->handleReply($this->school->fresh(), 'UNEB', $this->admin->id);
        $this->assertSame('advanced', $ok['status']);
        $this->assertSame('uneb', $this->school->fresh()->curriculum);
        $this->assertTrue(OnboardingStepsService::isStepComplete('curriculum', $this->school->fresh()));
    }

    public function test_livewire_uses_v2_action_step_when_flag_on(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);

        $this->assertSame('complete', $component->get('mode'));
        $this->assertSame('onboarding_v2', $component->get('actionStep'));

        $component->set('input', 'V2 Livewire School')->call('send');
        $this->assertSame('V2 Livewire School', $this->school->fresh()->name);
        $this->assertSame('onboarding_v2', $component->get('actionStep'));

        $component->set('input', 'garbage-size')->call('send');
        $messages = collect($component->get('messages'))->pluck('text')->implode("\n");
        $this->assertStringContainsString(ToshiOnboardingV2Driver::COMING_SOON_HINT, $messages);
    }

    public function test_flag_off_keeps_legacy_action_steps(): void
    {
        config(['toshi.onboarding_v2' => false]);
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $this->assertNotSame('onboarding_v2', $component->get('actionStep'));
    }
}
