<?php

namespace Tests\Feature\Toshi;

use App\AiAgents\ToshiSdkV2Service;
use App\Livewire\AgentToshi;
use App\Models\School;
use App\Models\User;
use App\Services\Toshi\ToshiUiSwitch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * Soft-launch switches:
 * - Onboarding: every school, no AI key required
 * - Assistant: AI key AND schools.toshi_enabled
 */
class ToshiUiSwitchTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Switch School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Switch Admin',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt(Str::random(16)),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    public function test_onboarding_enabled_without_ai_key(): void
    {
        // phpunit.xml forces a placeholder OPENAI key — assistant still needs toshi_enabled.
        $this->school->update(['toshi_enabled' => 0]);

        $switch = app(ToshiUiSwitch::class);

        $this->assertTrue($switch->onboardingEnabled($this->admin->fresh()));
        $this->assertTrue($switch->enabled($this->admin->fresh()));
        $this->assertFalse($switch->assistantEnabled($this->admin->fresh()));
    }

    public function test_assistant_requires_ai_key_and_school_flag(): void
    {
        $this->school->update(['toshi_enabled' => 0]);
        Config::set('ai.providers.openai-compatible.key', 'sk-test');
        Config::set('toshi.api_key', 'sk-test');

        $switch = app(ToshiUiSwitch::class);
        $this->assertFalse(
            $switch->assistantEnabled($this->admin->fresh()),
            'Key alone is not enough when toshi_enabled is off',
        );

        $this->school->update(['toshi_enabled' => 1]);
        $this->assertTrue($switch->assistantEnabled($this->admin->fresh()));

        Config::set('ai.providers.openai-compatible.key', '');
        Config::set('toshi.api_key', '');
        $this->assertFalse($switch->assistantEnabled($this->admin->fresh()));
    }

    public function test_dashboard_shows_toshi_when_onboarding_on_assistant_off(): void
    {
        Config::set('ai.providers.openai-compatible.key', '');
        Config::set('toshi.api_key', '');
        $this->school->update(['toshi_enabled' => 0]);

        $response = $this->actingAs($this->admin->fresh())->get('/admin/dashboard');
        if ($response->isRedirect()) {
            $response = $this->actingAs($this->admin)->get($response->headers->get('Location'));
        }

        $this->assertTrue($response->isSuccessful());
        $response->assertSee('data-testid="toshi-toggle"', false);
    }

    public function test_set_up_with_toshi_returns_when_onboarding_on(): void
    {
        Config::set('ai.providers.openai-compatible.key', '');
        Config::set('toshi.api_key', '');
        $this->school->update(['toshi_enabled' => 0]);

        $response = $this->actingAs($this->admin->fresh())->get('/admin/dashboard');
        if ($response->isRedirect()) {
            $response = $this->actingAs($this->admin)->get($response->headers->get('Location'));
        }

        $response->assertOk();
        $response->assertSee('Set up with Toshi', false);
    }

    public function test_exit_completing_setup_lands_in_done_when_assistant_off(): void
    {
        Config::set('toshi.sdk_v2_enabled', true);
        $this->school->update(['toshi_enabled' => 0]);

        $sdk = Mockery::mock(ToshiSdkV2Service::class);
        $sdk->shouldNotReceive('ask');
        $sdk->shouldNotReceive('askStreamed');
        $sdk->shouldNotReceive('isAvailable');
        $sdk->shouldNotReceive('consumeBudget');
        $this->app->instance(ToshiSdkV2Service::class, $sdk);

        $component = Livewire::actingAs($this->admin->fresh())
            ->test(AgentToshi::class)
            ->set('schoolId', $this->school->id)
            ->set('scope', 'school')
            ->call('switchMode', 'assistant')
            ->assertSet('mode', 'done')
            ->assertSee('Your school is set up', false)
            ->assertSee('Here\'s what to do next', false)
            ->assertSee('Add students', false)
            ->assertSee('Enter marks', false)
            ->assertSee('Send report cards', false)
            ->assertSee('data-testid="toshi-setup-done"', false);

        $component->set('input', 'what can you do about fees?')
            ->call('send')
            ->assertSet('mode', 'done');

        $botTexts = collect($component->get('messages'))
            ->where('role', 'bot')
            ->pluck('text')
            ->implode("\n");
        $this->assertStringContainsString("I'm not sure about that yet", $botTexts);
    }

    public function test_done_mode_never_reaches_sdk_or_mcp_resume(): void
    {
        Config::set('toshi.sdk_v2_enabled', true);
        $this->school->update(['toshi_enabled' => 0]);

        $sdk = Mockery::mock(ToshiSdkV2Service::class);
        $sdk->shouldNotReceive('ask');
        $sdk->shouldNotReceive('askStreamed');
        $sdk->shouldNotReceive('isAvailable');
        $sdk->shouldNotReceive('consumeBudget');
        $this->app->instance(ToshiSdkV2Service::class, $sdk);

        $component = Livewire::actingAs($this->admin->fresh())
            ->test(AgentToshi::class)
            ->set('mode', 'done')
            ->set('schoolId', $this->school->id)
            ->set('scope', 'school')
            ->set('step', 99)
            ->set('capabilities', [
                'actions' => ['add_student', 'list_classes', 'generate_report'],
                'label' => 'school admin',
                'scope' => 'school',
            ]);

        $component->set('input', 'add three students please')
            ->call('send')
            ->assertSet('mode', 'done');

        // Stale MCP resume payload must not call the model when assistant is off.
        $component->set('pendingToolConfirm', [
            'tool' => 'toolAddStudent',
            'args' => ['name' => 'Test'],
            'mcp_resume' => [
                'agent_class' => \App\AiAgents\ToshiOrchestrator::class,
                'conversation_id' => 'conv-fake',
                'approval_id' => 'appr-fake',
            ],
        ])
            ->set('awaitingConfirm', true)
            ->call('confirmYes');

        $botTexts = collect($component->get('messages'))
            ->where('role', 'bot')
            ->pluck('text')
            ->implode("\n");
        $this->assertStringContainsString('assistant is off', $botTexts);
    }

    public function test_toshi_activity_404_when_assistant_off(): void
    {
        Config::set('ai.providers.openai-compatible.key', '');
        Config::set('toshi.api_key', '');
        $this->school->update(['toshi_enabled' => 0]);

        $activity = $this->actingAs($this->admin->fresh())->get('/admin/toshi-activity');
        if ($activity->isRedirect()) {
            $followed = $this->actingAs($this->admin)->get($activity->headers->get('Location'));
            $this->assertTrue(
                $followed->isNotFound() || ! str_contains($followed->getContent(), 'Toshi activity'),
                'Activity log must not be available when assistant is off',
            );
        } else {
            $activity->assertNotFound();
        }
    }
}
