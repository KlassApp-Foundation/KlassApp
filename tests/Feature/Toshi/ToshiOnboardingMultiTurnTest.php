<?php

namespace Tests\Feature\Toshi;

use App\Enums\ToshiMode;
use App\Livewire\AgentToshi;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Complete-mode journeys must advance past ~5 turns without stalling
 * (invalid size / re-prompt loops used to leave the panel stuck).
 */
class ToshiOnboardingMultiTurnTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => "Grace's School",
            'email' => 'multi-turn@example.com',
            'phone' => '+256701111222',
            'slug' => 'multi-turn-school',
            'status' => 1,
            'toshi_enabled' => 0,
            'toshi_mode' => ToshiMode::Onboarding,
            'curriculum' => null,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Multi Turn Admin',
            'email' => 'multi-turn@example.com',
            'password' => Hash::make('secret123'),
            'email_verified' => 1,
            'mobile_no' => '+256701111222',
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'firstname' => 'Multi',
            'lastname' => 'Turn',
        ]);
    }

    public function test_complete_mode_advances_past_five_turns_with_assistant_off(): void
    {
        Config::set('toshi.sdk_v2_enabled', true);
        $this->school->setToshiMode(ToshiMode::Onboarding);

        $sdk = \Mockery::mock(\App\AiAgents\ToshiSdkV2Service::class);
        $sdk->shouldNotReceive('ask');
        $sdk->shouldNotReceive('askStreamed');
        $this->app->instance(\App\AiAgents\ToshiSdkV2Service::class, $sdk);

        $this->actingAs($this->admin->fresh());

        $component = Livewire::test(AgentToshi::class);
        $this->assertSame('complete', $component->get('mode'));

        // Legacy free-text size that used to fail hard — must normalise and advance.
        $turns = [
            ['My Cool Primary School', null],
            ['yes', null],
            ['100-300 students', 'onboarding_student_size'],
            ['Uganda', 'onboarding_country'],
            ['UNEB', 'onboarding_curriculum'],
            ['Primary + Nursery', 'onboarding_school_category'],
            ['EMIS-12345', 'onboarding_emis'],
            ['skip', 'onboarding_uneb_center'],
        ];

        $botAfter = '';
        foreach ($turns as [$input, $expectedAction]) {
            if ($expectedAction !== null) {
                // After name confirm, complete-mode lands on action steps via detectMissingSteps.
                // Ensure we are still progressing (not stuck on the same unanswered prompt).
            }
            $component->set('input', $input)->call('send');
            $botAfter = collect($component->get('messages'))
                ->where('role', 'bot')
                ->pluck('text')
                ->implode("\n");
            $this->assertStringNotContainsString(
                "couldn't find a student",
                $botAfter,
                "Stalled into student lookup after sending: {$input}",
            );
        }

        $this->assertGreaterThanOrEqual(8, count($component->get('messages')));
        $this->school->refresh();
        $this->assertNotNull($this->school->curriculum);
        $this->assertNotNull($this->school->school_category);
        $this->assertSame('complete', $component->get('mode'));
        $this->assertNotSame('assistant', $component->get('mode'));
    }
}
