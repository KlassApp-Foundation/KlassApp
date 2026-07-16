<?php

namespace Tests\Feature\Toshi;

use App\Services\ToshiActionService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToshiConfirmationGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset static state before each test
        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        // Insert essential usergroups
        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    /** @test */
    public function confirmation_payload_returns_json_when_not_bypassing()
    {
        $result = ToshiActionService::confirmationPayload(
            'toolRecordAttendance',
            ['student' => 'Alice', 'date' => '2026-07-15'],
            'Record attendance for Alice on 2026-07-15'
        );

        // Must return a JSON string with __tier2_confirm
        $this->assertNotNull($result);

        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
        $this->assertTrue($decoded['__tier2_confirm']);
        $this->assertEquals('toolRecordAttendance', $decoded['tool']);
        $this->assertEquals('Record attendance for Alice on 2026-07-15', $decoded['preview']);
        $this->assertEquals(['student' => 'Alice', 'date' => '2026-07-15'], $decoded['args']);
    }

    /** @test */
    public function confirmation_payload_returns_null_when_bypassing()
    {
        ToshiActionService::$bypassConfirm = true;

        $result = ToshiActionService::confirmationPayload(
            'toolRecordAttendance',
            ['student' => 'Alice', 'date' => '2026-07-15'],
            'Record attendance for Alice on 2026-07-15'
        );

        // Must return null — tool should proceed to execute
        $this->assertNull($result);
    }

    /** @test */
    public function side_chain_stores_pending_payload()
    {
        ToshiActionService::confirmationPayload(
            'toolCreateFee',
            ['name' => 'Tuition', 'amount' => 500000],
            'Create fee: Tuition (UGX 500,000)'
        );

        // Side-channel must contain the payload
        $this->assertNotNull(ToshiActionService::$pendingConfirmPayload);
        $this->assertEquals('toolCreateFee', ToshiActionService::$pendingConfirmPayload['tool']);
        $this->assertEquals(['name' => 'Tuition', 'amount' => 500000], ToshiActionService::$pendingConfirmPayload['args']);
    }

    /** @test */
    public function side_chain_is_not_set_when_bypassing()
    {
        ToshiActionService::$bypassConfirm = true;

        ToshiActionService::confirmationPayload(
            'toolCreateFee',
            ['name' => 'Tuition', 'amount' => 500000],
            'Create fee: Tuition (UGX 500,000)'
        );

        // Side-channel must remain null when bypassed
        $this->assertNull(ToshiActionService::$pendingConfirmPayload);
    }

    /** @test */
    public function confirm_yes_clears_pending_state()
    {
        $user = User::factory()->create(['usergroup_id' => 3]);

        $this->actingAs($user);

        $livewire = Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('pendingToolConfirm', [
                'tool' => 'toolCreateExam',
                'args' => ['name' => 'Midterm', 'class_id' => 1],
            ])
            ->set('awaitingConfirm', true);

        $livewire->call('confirmYes');

        // State must be cleared after confirm
        $this->assertNull($livewire->get('pendingToolConfirm'));
        $this->assertFalse($livewire->get('awaitingConfirm'));
    }

    /** @test */
    public function confirm_no_clears_pending_and_sets_cancelled()
    {
        $user = User::factory()->create(['usergroup_id' => 3]);

        $this->actingAs($user);

        $livewire = Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('pendingToolConfirm', [
                'tool' => 'toolCreateExam',
                'args' => ['name' => 'Midterm', 'class_id' => 1],
                'preview' => 'Create exam: Midterm',
            ])
            ->set('awaitingConfirm', true);

        $livewire->call('confirmNo');

        // pending must be cleared, cancelled must be set
        $this->assertNull($livewire->get('pendingToolConfirm'));
        $this->assertFalse($livewire->get('awaitingConfirm'));
        $this->assertNotNull($livewire->get('cancelledToolConfirm'));
        $this->assertEquals('toolCreateExam', $livewire->get('cancelledToolConfirm')['tool']);
    }

    /** @test */
    public function confirm_yes_executes_action_and_bot_says_result()
    {
        $user = User::factory()->create(['usergroup_id' => 3]);

        $this->actingAs($user);

        $livewire = Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('pendingToolConfirm', [
                'tool' => 'toolSeedDefaultGrading',
                'args' => [],
            ])
            ->set('awaitingConfirm', true);

        $livewire->call('confirmYes');

        // confirmYes dispatches to the match handler and botSay fires
        $messages = $livewire->get('messages');
        $this->assertNotEmpty($messages, 'Expected at least one message after confirmYes');
        $lastMessage = end($messages);
        $this->assertNotNull($lastMessage, 'Expected a bot response message');
    }
}
