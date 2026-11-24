<?php

namespace Tests\Feature\Toshi;

use App\Services\ToshiActionService;
use App\Services\Toshi\WriteContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ToshiConfirmationGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset static state before each test
        ToshiActionService::$pendingConfirmPayload = null;

        // Insert essential usergroups
        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    #[Test]
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

    #[Test]
    public function confirmation_payload_returns_null_when_this_write_is_confirmed()
    {
        $args = ['student' => 'Alice', 'date' => '2026-07-15'];
        $context = WriteContext::confirmed('toolRecordAttendance', $args);

        $result = ToshiActionService::confirmationPayload(
            'toolRecordAttendance',
            $args,
            'Record attendance for Alice on 2026-07-15',
            $context,
        );

        // Confirmed for this tool: the tool proceeds to execute.
        $this->assertNull($result);
    }

    #[Test]
    public function a_context_for_another_tool_does_not_authorise_this_write()
    {
        $args = ['student' => 'Alice', 'date' => '2026-07-15'];
        $wrong = WriteContext::confirmed('toolCreateFee', ['name' => 'Tuition']);

        $result = ToshiActionService::confirmationPayload(
            'toolRecordAttendance',
            $args,
            'Record attendance for Alice on 2026-07-15',
            $wrong,
        );

        $this->assertNotNull($result);
        $decoded = json_decode($result, true);
        $this->assertTrue($decoded['__tier2_confirm']);
    }

    #[Test]
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

    #[Test]
    public function a_confirmed_write_does_not_set_the_side_chain()
    {
        $args = ['name' => 'Tuition', 'amount' => 500000];

        ToshiActionService::confirmationPayload(
            'toolCreateFee',
            $args,
            'Create fee: Tuition (UGX 500,000)',
            WriteContext::confirmed('toolCreateFee', $args),
        );

        $this->assertNull(ToshiActionService::$pendingConfirmPayload);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function a_confirmed_tool_write_leaves_a_durable_row()
    {
        $school = \App\Models\School::create([
            'name' => 'Gate School',
            'email' => 'gate@example.test',
            'phone' => '+256700555001',
            'slug' => 'gate-school',
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $user = User::factory()->create(['usergroup_id' => 3, 'school_id' => $school->id]);
        $this->actingAs($user);

        Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('schoolId', $school->id)
            ->set('mode', 'complete')
            ->set('pendingToolConfirm', [
                'tool' => 'toolCreateExam',
                'args' => ['name' => 'Midterm', 'class_id' => 1],
            ])
            ->set('awaitingConfirm', true)
            ->call('confirmYes');

        $this->assertDatabaseHas('onboarding_plan_steps', [
            'school_id' => $school->id,
            'step' => 'tool:toolCreateExam',
            'actor_id' => $user->id,
        ]);
    }
}
