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
 * Soft-launch default: every school starts in Toshi preview mode.
 * Panel stays visible/collapsed; Coming soon; no scripted onboarding, AI, or MCP.
 */
class ToshiPreviewModeTest extends TestCase
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
            'name' => 'Preview School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
            // Intentionally omit toshi_mode — DB default must be preview.
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Preview Admin',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt(Str::random(16)),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    public function test_new_school_defaults_to_preview_mode(): void
    {
        $this->school->refresh();
        $this->assertSame(ToshiMode::Preview, $this->school->toshi_mode);

        $switch = app(ToshiUiSwitch::class);
        $this->assertTrue($switch->previewMode($this->admin->fresh()));
        $this->assertTrue($switch->enabled($this->admin->fresh()));
        $this->assertFalse($switch->onboardingEnabled($this->admin->fresh()));
        $this->assertFalse($switch->assistantEnabled($this->admin->fresh()));
    }

    public function test_preview_hides_set_up_with_toshi_and_keeps_manual(): void
    {
        $response = $this->actingAs($this->admin->fresh())->get('/admin/dashboard');
        if ($response->isRedirect()) {
            $response = $this->actingAs($this->admin)->get($response->headers->get('Location'));
        }

        $response->assertOk();
        $response->assertSee('Set up manually', false);
        $response->assertDontSee('Set up with Toshi', false);
        $response->assertSee('data-testid="toshi-toggle"', false);
        // Auto-open script on the dashboard must not ship in preview.
        $response->assertDontSee("dispatchEvent(new CustomEvent('toshi-maximize'))", false);
    }

    public function test_preview_panel_shows_coming_soon_and_blocks_send_sdk_mcp(): void
    {
        Config::set('toshi.sdk_v2_enabled', true);
        Config::set('ai.providers.openai-compatible.key', 'sk-test');
        Config::set('toshi.api_key', 'sk-test');

        $sdk = Mockery::mock(ToshiSdkV2Service::class);
        $sdk->shouldNotReceive('ask');
        $sdk->shouldNotReceive('askStreamed');
        $sdk->shouldNotReceive('isAvailable');
        $sdk->shouldNotReceive('consumeBudget');
        $this->app->instance(ToshiSdkV2Service::class, $sdk);

        $component = Livewire::actingAs($this->admin->fresh())
            ->test(AgentToshi::class)
            ->assertSet('mode', 'preview')
            ->assertSee('Coming soon', false)
            ->assertSee('Founding schools will be the first to try Toshi.', false)
            ->assertSee('data-testid="toshi-preview-coming-soon"', false)
            ->assertSee('data-testid="toshi-composer-preview"', false);

        $component->set('input', 'set up my school please')
            ->call('send')
            ->assertSet('mode', 'preview')
            ->assertSet('input', '');

        $this->assertSame([], $component->get('messages'));

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

    public function test_rasta_can_flip_school_to_onboarding_without_code_change(): void
    {
        $this->school->setToshiMode(ToshiMode::Onboarding);
        $this->school->refresh();

        $switch = app(ToshiUiSwitch::class);
        $this->assertFalse($switch->previewMode($this->admin->fresh()));
        $this->assertTrue($switch->onboardingEnabled($this->admin->fresh()));
        $this->assertFalse($switch->assistantEnabled($this->admin->fresh()));
        $this->assertSame(0, (int) $this->school->toshi_enabled);

        $response = $this->actingAs($this->admin->fresh())->get('/admin/dashboard');
        if ($response->isRedirect()) {
            $response = $this->actingAs($this->admin)->get($response->headers->get('Location'));
        }
        $response->assertOk();
        $response->assertSee('Set up with Toshi', false);
    }

    public function test_assistant_mode_requires_ai_key(): void
    {
        $this->school->setToshiMode(ToshiMode::Assistant);
        Config::set('ai.providers.openai-compatible.key', '');
        Config::set('toshi.api_key', '');

        $switch = app(ToshiUiSwitch::class);
        $this->assertTrue($switch->onboardingEnabled($this->admin->fresh()));
        $this->assertFalse($switch->assistantEnabled($this->admin->fresh()));

        Config::set('ai.providers.openai-compatible.key', 'sk-test');
        Config::set('toshi.api_key', 'sk-test');
        $this->assertTrue($switch->assistantEnabled($this->admin->fresh()));
        $this->assertSame(1, (int) $this->school->fresh()->toshi_enabled);
    }
}
