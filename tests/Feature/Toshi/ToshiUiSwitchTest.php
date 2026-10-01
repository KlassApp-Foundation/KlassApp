<?php

namespace Tests\Feature\Toshi;

use App\AiAgents\ToshiSdkV2Service;
use App\Enums\ToshiMode;
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
 * - Onboarding (signup default): scripted setup, no AI key required
 * - Preview (per-school fallback): panel only, Coming soon
 * - Assistant: AI key AND toshi_mode=assistant
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
            'toshi_mode' => ToshiMode::Onboarding,
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
        $this->school->setToshiMode(ToshiMode::Onboarding);

        $switch = app(ToshiUiSwitch::class);

        $this->assertTrue($switch->onboardingEnabled($this->admin->fresh()));
        $this->assertTrue($switch->enabled($this->admin->fresh()));
        $this->assertFalse($switch->assistantEnabled($this->admin->fresh()));
        $this->assertFalse($switch->previewMode($this->admin->fresh()));
    }

    public function test_assistant_requires_ai_key_and_school_mode(): void
    {
        $this->school->setToshiMode(ToshiMode::Onboarding);
        Config::set('ai.providers.openai-compatible.key', 'sk-test');
        Config::set('toshi.api_key', 'sk-test');

        $switch = app(ToshiUiSwitch::class);
        $this->assertFalse(
            $switch->assistantEnabled($this->admin->fresh()),
            'Key alone is not enough when mode is not assistant',
        );

        $this->school->setToshiMode(ToshiMode::Assistant);
        $this->assertTrue($switch->assistantEnabled($this->admin->fresh()));

        Config::set('ai.providers.openai-compatible.key', '');
        Config::set('toshi.api_key', '');
        $this->assertFalse($switch->assistantEnabled($this->admin->fresh()));
    }

    public function test_dashboard_shows_toshi_when_onboarding_on_assistant_off(): void
    {
        Config::set('ai.providers.openai-compatible.key', '');
        Config::set('toshi.api_key', '');
        $this->school->setToshiMode(ToshiMode::Onboarding);

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
        $this->school->setToshiMode(ToshiMode::Onboarding);

        $response = $this->actingAs($this->admin->fresh())->get('/admin/dashboard');
        if ($response->isRedirect()) {
            $response = $this->actingAs($this->admin)->get($response->headers->get('Location'));
        }

        $response->assertOk();
        $response->assertSee('Set up with Toshi', false);
    }

    public function test_exit_completing_setup_lands_in_coming_soon_when_assistant_off(): void
    {
        Config::set('toshi.sdk_v2_enabled', true);
        $this->school->setToshiMode(ToshiMode::Onboarding);

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
            ->assertSet('mode', 'preview')
            ->assertSee('Coming soon', false)
            ->assertSee('data-testid="toshi-preview-coming-soon"', false);

        $component->set('input', 'what can you do about fees?')
            ->call('send')
            ->assertSet('mode', 'preview');

        $this->assertSame([], $component->get('messages'));
    }

    public function test_post_setup_freeform_shows_coming_soon_and_blocks_mcp(): void
    {
        Config::set('toshi.sdk_v2_enabled', true);
        $this->school->setToshiMode(ToshiMode::Onboarding);

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

        // Legacy done → Coming soon card; free-form must not hit the model.
        $component->set('input', 'add three students please')
            ->call('send')
            ->assertSet('mode', 'preview')
            ->assertSee('Coming soon', false);

        $this->assertSame([], $component->get('messages'));

        // Stale MCP resume payload must not run when Coming soon is showing.
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

        $this->assertNotNull($component->get('pendingToolConfirm'), 'preview must not consume MCP confirm');
    }

    public function test_toshi_activity_404_when_assistant_off(): void
    {
        Config::set('ai.providers.openai-compatible.key', '');
        Config::set('toshi.api_key', '');
        $this->school->setToshiMode(ToshiMode::Onboarding);

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
