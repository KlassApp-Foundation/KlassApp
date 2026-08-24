<?php

namespace Tests\Feature\Onboarding;

use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ToshiSchoolNameRoutingTest extends TestCase
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
        ]);

        $this->school = School::create([
            'name' => "Grace's School",
            'email' => 'grace-onboard@example.com',
            'phone' => '+256701111111',
            'slug' => 'graces-school-onboard',
            'status' => 1,
            'toshi_enabled' => 1,
            'curriculum' => null,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Grace Admin',
            'email' => 'grace-onboard@example.com',
            'password' => Hash::make('secret123'),
            'email_verified' => 1,
            'mobile_no' => '+256701111111',
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'firstname' => 'Grace Admin',
        ]);
    }

    /** @test */
    public function stale_action_step_country_does_not_hijack_school_name_answer(): void
    {
        $this->actingAs($this->admin);

        // Simulate a session restore where the previous turn left actionStep = onboarding_country,
        // but the next incomplete step is now school_name.
        session(['toshi_state' => [
            'messages' => [],
            'step' => 0,
            'substep' => 0,
            'mode' => 'complete',
            'scope' => 'school',
            'schoolId' => $this->school->id,
            'schoolName' => '',
            'schoolCountry' => '',
            'curriculum' => '',
            'actionStep' => 'onboarding_country',
            'actionSubstep' => 0,
        ]]);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);

        // After mount/reconcile, Toshi should be on the school name step with no stale action.
        $this->assertSame('school_info', $component->get('steps')[$component->get('step')]);
        $this->assertNull($component->get('actionStep'));
        $this->assertSame(0, (int) $component->get('substep'));

        $component->set('input', 'Toshi Test School')->call('send');

        $this->school->refresh();

        $this->assertNull($this->school->registration_country, 'The school name answer should not be persisted as the country.');
        $this->assertSame('Toshi Test School', $component->get('schoolName'));
        $this->assertTrue((bool) $component->get('awaitingConfirm'));

        $botTexts = collect($component->get('messages'))
            ->where('role', 'bot')
            ->pluck('text')
            ->implode("\n");

        $this->assertStringNotContainsString('Country set', $botTexts);
    }

    /** @test */
    public function stale_action_step_plan_selection_does_not_hijack_school_name_answer(): void
    {
        $this->actingAs($this->admin);

        // A different stale actionStep — plan selection — must also be discarded
        // when the next incomplete step is school_name.
        session(['toshi_state' => [
            'messages' => [],
            'step' => 0,
            'substep' => 0,
            'mode' => 'complete',
            'scope' => 'school',
            'schoolId' => $this->school->id,
            'schoolName' => '',
            'schoolCountry' => '',
            'curriculum' => '',
            'actionStep' => 'onboarding_plan_selection',
            'actionSubstep' => 0,
        ]]);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);

        $this->assertSame('school_info', $component->get('steps')[$component->get('step')]);
        $this->assertNull($component->get('actionStep'));

        $component->set('input', 'Bright Future Academy')->call('send');

        $this->assertSame('Bright Future Academy', $component->get('schoolName'));
        $this->assertTrue((bool) $component->get('awaitingConfirm'));

        $botTexts = collect($component->get('messages'))
            ->where('role', 'bot')
            ->pluck('text')
            ->implode("\n");

        $this->assertStringNotContainsStringIgnoringCase('plan', $botTexts);
    }
}
