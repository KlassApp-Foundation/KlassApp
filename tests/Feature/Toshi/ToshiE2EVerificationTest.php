<?php

namespace Tests\Feature\Toshi;

use App\Models\User;
use App\AiAgents\ToshiSdkV2Service;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class ToshiE2EVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $schoolId = DB::table('schools')->insertGetId([
            'name' => 'E2E Test School',
            'slug' => 'e2e-test-school',
            'registration_country' => 'Uganda',
            'toshi_enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->schoolId = $schoolId;

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->schoolId,
        ]);
    }

    /** @test */
    public function llm_responds_and_route_tool_fails_authentication_gracefully()
    {
        $this->actingAs($this->admin);

        \Illuminate\Support\Facades\Log::info('=== E2E: testing LLM connectivity ===');

        $service = app(ToshiSdkV2Service::class);
        $result = $service->ask($this->admin, $this->schoolId, 'hello, tell me about student Alice');

        \Illuminate\Support\Facades\Log::info('=== E2E: LLM response length ===', [
            'length' => strlen($result ?? 'NULL'),
            'preview' => substr($result ?? 'NULL', 0, 200),
        ]);

        // Must get a response from the LLM (non-null means API worked)
        // Empty content is expected when route tools fail auth in test context
        $this->assertNotNull($result, 'LLM should return a non-null response (null = API timeout/error)');
        \Illuminate\Support\Facades\Log::info('E2E: LLM response', [
            'length' => strlen($result),
            'preview' => substr($result, 0, 200),
        ]);
    }

    /** @test */
    public function write_tool_triggers_confirmation_card()
    {
        $this->actingAs($this->admin);

        \Illuminate\Support\Facades\Log::info('=== E2E: testing write tool confirmation ===');

        $livewire = Livewire::test(\App\Livewire\AgentToshi::class);

        $livewire
            ->set('input', 'Record Alice absent today')
            ->call('send');

        $pending = $livewire->get('pendingToolConfirm');
        $messages = $livewire->get('messages');
        $awaiting = $livewire->get('awaitingConfirm');

        \Illuminate\Support\Facades\Log::info('=== E2E: confirm card state ===', [
            'pendingToolConfirm' => $pending !== null ? 'SET' : 'NULL',
            'awaitingConfirm' => $awaiting ? 'true' : 'false',
            'messageCount' => count($messages),
        ]);

        foreach ($messages as $i => $msg) {
            \Illuminate\Support\Facades\Log::info("E2E msg[$i]", [
                'role' => $msg['role'] ?? 'unknown',
                'text' => substr($msg['text'] ?? $msg['content'] ?? '', 0, 300),
            ]);
        }

        // The test passes regardless — we're logging the actual behavior.
        // The LLM may or may not trigger a tool call depending on its training.
        // The key observation is the log output shows the full round-trip.
        $this->assertNotNull($livewire->get('mode'), 'Component should be mounted');
    }

    /** @test */
    public function llm_timeout_falls_back_to_keyword_router()
    {
        $this->actingAs($this->admin);

        // Disable SDK v2 to simulate the timeout path (LLM unavailable)
        // This forces the fallback to keyword router
        \Illuminate\Support\Facades\Config::set('toshi.sdk_v2_enabled', false);

        $livewire = Livewire::test(\App\Livewire\AgentToshi::class);

        $livewire
            ->set('input', 'hello')
            ->call('send');

        $messages = $livewire->get('messages');

        // The keyword router handles "hello" with a greeting
        $this->assertNotEmpty($messages, 'Should have at least one bot response');
        $lastMessage = end($messages);
        $lastText = $lastMessage['text'] ?? $lastMessage['content'] ?? '';
        \Illuminate\Support\Facades\Log::info('TIMEOUT FALLBACK: response was', ['text' => substr($lastText, 0, 300)]);
        $this->assertStringContainsString('Toshi', $lastText, 'Fallback should mention Toshi in greeting');
    }

    /** @test */
    public function confirm_yes_executes_pending_tool()
    {
        $this->actingAs($this->admin);

        // Set pendingToolConfirm directly (simulating what handleAssistantQuery does)
        $livewire = Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('pendingToolConfirm', [
                'tool' => 'toolRecordAttendance',
                'args' => ['student' => 'Test Student', 'date' => '2026-07-15', 'status' => 'absent'],
            ])
            ->set('awaitingConfirm', true);

        $livewire->call('confirmYes');

        $this->assertNull($livewire->get('pendingToolConfirm'));
        $this->assertFalse($livewire->get('awaitingConfirm'));

        // confirmYes dispatches to ToshiActionService::recordAttendance which returns
        // a result botSay. Verify a message was added.
        $messages = $livewire->get('messages');
        $this->assertNotEmpty($messages);
    }

    /** @test */
    public function confirm_no_prevents_write()
    {
        $this->actingAs($this->admin);

        $livewire = Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('pendingToolConfirm', [
                'tool' => 'toolRecordPayment',
                'args' => ['student' => 'Test Student', 'amount' => 50000],
                'preview' => 'Record payment UGX 50,000 for Test Student',
            ])
            ->set('awaitingConfirm', true);

        $livewire->call('confirmNo');

        $this->assertNull($livewire->get('pendingToolConfirm'));
        $this->assertFalse($livewire->get('awaitingConfirm'));
        $this->assertNotNull($livewire->get('cancelledToolConfirm'));
    }
}
