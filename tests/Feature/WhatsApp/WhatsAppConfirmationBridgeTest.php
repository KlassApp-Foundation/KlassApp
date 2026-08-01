<?php

namespace Tests\Feature\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppPendingConfirmation;
use App\Models\WhatsAppUser;
use App\Services\WhatsApp\WhatsAppConfirmationBridge;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class WhatsAppConfirmationBridgeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private WhatsAppUser $waUser;

    private string $phone = '+256701234567';

    /** @var \Mockery\MockInterface&WhatsAppBusinessService */
    private WhatsAppBusinessService $whatsApp;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->user = User::factory()->create([
            'usergroup_id' => 5,
            'email' => 'teacher-confirm@test.sch.ug',
        ]);

        $this->waUser = WhatsAppUser::query()->create([
            'phone' => $this->phone,
            'user_id' => $this->user->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        $this->whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $this->app->instance(WhatsAppBusinessService::class, $this->whatsApp);
    }

    public function test_parse_reply_button_and_text_patterns(): void
    {
        $bridge = $this->bridge();

        $this->assertSame(
            ['token' => 'a7f3k2xy', 'approved' => true],
            $bridge->parseReply('ty_a7f3k2xy')
        );
        $this->assertSame(
            ['token' => 'a7f3k2xy', 'approved' => false],
            $bridge->parseReply('tn_a7f3k2xy')
        );
        $this->assertSame(
            ['token' => 'a7f3k2xy', 'approved' => true],
            $bridge->parseReply('YES a7f3k2xy')
        );
        $this->assertSame(
            ['token' => 'a7f3k2xy', 'approved' => false],
            $bridge->parseReply('NO a7f3k2xy')
        );
        $this->assertSame(
            ['token' => 'a7f3k2xy', 'approved' => true],
            $bridge->parseReply('approve a7f3k2xy')
        );
        $this->assertSame(
            ['token' => 'a7f3k2xy', 'approved' => false],
            $bridge->parseReply('reject a7f3k2xy')
        );

        $this->assertNull($bridge->parseReply('yes'));
        $this->assertNull($bridge->parseReply('no'));
        $this->assertNull($bridge->parseReply('fees'));
        $this->assertNull($bridge->parseReply('YES'));
    }

    public function test_send_confirmation_includes_buttons_and_yes_no_body(): void
    {
        $this->whatsApp->shouldReceive('sendInteractiveButtons')
            ->once()
            ->withArgs(function (string $phone, string $body, array $buttons) {
                $this->assertSame($this->phone, $phone);
                $this->assertStringContainsString('Reply YES ', $body);
                $this->assertStringContainsString(' or NO ', $body);
                $this->assertStringContainsString('Create task "Homework"', $body);
                $this->assertCount(2, $buttons);
                $this->assertSame('Approve', $buttons[0]['title']);
                $this->assertSame('Reject', $buttons[1]['title']);
                $this->assertTrue(str_starts_with($buttons[0]['id'], 'ty_'));
                $this->assertTrue(str_starts_with($buttons[1]['id'], 'tn_'));
                $token = substr($buttons[0]['id'], 3);
                $this->assertSame("tn_{$token}", $buttons[1]['id']);
                $this->assertStringContainsString("YES {$token}", $body);

                return true;
            })
            ->andReturn(['success' => true, 'message_id' => 'wamid.test.1']);

        $pending = $this->bridge()->sendConfirmation(
            $this->phone,
            $this->user,
            WhatsAppPendingConfirmation::MECHANISM_TIER2,
            [
                'tool' => 'toolTeacherCreateTask',
                'args' => ['title' => 'Homework'],
                'preview' => 'Create task "Homework"',
            ],
        );

        $this->assertSame(WhatsAppPendingConfirmation::STATUS_PENDING, $pending->status);
        $this->assertSame('wamid.test.1', $pending->outbound_wamid);
        $this->assertSame($this->phone, $pending->phone);
        $this->assertSame($this->user->id, $pending->user_id);
        $this->assertDatabaseHas('whatsapp_pending_confirmations', [
            'token' => $pending->token,
            'status' => 'pending',
        ]);
    }

    public function test_approve_via_button_resumes_and_invalidates(): void
    {
        $pending = $this->seedPending('btnok12a');

        $bridge = $this->partialBridgeExpectingResume(true, '✅ Task created.');

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn ($phone, $msg) => $phone === $this->phone && $msg === '✅ Task created.')
            ->andReturn(['success' => true, 'message_id' => 'out-1']);

        $handled = $bridge->handleInbound($this->waUser, $this->phone, 'ty_btnok12a');

        $this->assertTrue($handled);
        $this->assertSame(
            WhatsAppPendingConfirmation::STATUS_APPROVED,
            $pending->fresh()->status
        );
    }

    public function test_reject_via_button(): void
    {
        $pending = $this->seedPending('btnno12b');

        $bridge = $this->partialBridgeExpectingResume(false, '❌ Cancelled.');

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn ($phone, $msg) => $phone === $this->phone && $msg === '❌ Cancelled.')
            ->andReturn(['success' => true, 'message_id' => 'out-2']);

        $this->assertTrue($bridge->handleInbound($this->waUser, $this->phone, 'tn_btnno12b'));
        $this->assertSame(
            WhatsAppPendingConfirmation::STATUS_REJECTED,
            $pending->fresh()->status
        );
    }

    public function test_approve_via_text(): void
    {
        $pending = $this->seedPending('txtyes1c');

        $bridge = $this->partialBridgeExpectingResume(true, '✅ Approved.');

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->andReturn(['success' => true, 'message_id' => 'out-3']);

        $this->assertTrue($bridge->handleInbound($this->waUser, $this->phone, 'YES txtyes1c'));
        $this->assertSame(
            WhatsAppPendingConfirmation::STATUS_APPROVED,
            $pending->fresh()->status
        );
    }

    public function test_reject_via_text(): void
    {
        $pending = $this->seedPending('txtno12d');

        $bridge = $this->partialBridgeExpectingResume(false, '❌ Rejected.');

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->andReturn(['success' => true, 'message_id' => 'out-4']);

        $this->assertTrue($bridge->handleInbound($this->waUser, $this->phone, 'NO txtno12d'));
        $this->assertSame(
            WhatsAppPendingConfirmation::STATUS_REJECTED,
            $pending->fresh()->status
        );
    }

    public function test_expired_token_sends_expiry_message(): void
    {
        $pending = $this->seedPending('expired1', [
            'expires_at' => now()->subMinute(),
        ]);

        $bridge = $this->bridge();

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn ($phone, $msg) => $msg === WhatsAppConfirmationBridge::EXPIRED_MESSAGE)
            ->andReturn(['success' => true, 'message_id' => 'out-exp']);

        $this->assertTrue($bridge->handleInbound($this->waUser, $this->phone, 'YES expired1'));
        $this->assertSame(
            WhatsAppPendingConfirmation::STATUS_EXPIRED,
            $pending->fresh()->status
        );
    }

    public function test_missing_token_sends_expiry_message(): void
    {
        $bridge = $this->bridge();

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn ($phone, $msg) => $msg === WhatsAppConfirmationBridge::EXPIRED_MESSAGE)
            ->andReturn(['success' => true, 'message_id' => 'out-miss']);

        $this->assertTrue($bridge->handleInbound($this->waUser, $this->phone, 'YES missingx'));
    }

    public function test_wrong_phone_fails_closed_without_resolving(): void
    {
        $pending = $this->seedPending('wrongph1');

        $otherUser = User::factory()->create([
            'usergroup_id' => 5,
            'email' => 'other-confirm@test.sch.ug',
        ]);
        $otherWa = WhatsAppUser::query()->create([
            'phone' => '+256781111111',
            'user_id' => $otherUser->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        $bridge = $this->bridge();

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn ($phone, $msg) => $phone === '+256781111111'
                && $msg === WhatsAppConfirmationBridge::EXPIRED_MESSAGE)
            ->andReturn(['success' => true, 'message_id' => 'out-wrong']);

        $this->assertTrue($bridge->handleInbound($otherWa, '+256781111111', 'ty_wrongph1'));
        $this->assertSame(
            WhatsAppPendingConfirmation::STATUS_PENDING,
            $pending->fresh()->status,
            'Wrong phone must not invalidate the pending row'
        );
    }

    public function test_multi_pending_disambiguates_by_token(): void
    {
        $first = $this->seedPending('multi001');
        $second = $this->seedPending('multi002', [
            'preview' => 'Second confirm',
        ]);

        $bridge = $this->partialBridgeExpectingResume(true, '✅ Second done.');

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->andReturn(['success' => true, 'message_id' => 'out-multi']);

        $this->assertTrue($bridge->handleInbound($this->waUser, $this->phone, 'YES multi002'));

        $this->assertSame(WhatsAppPendingConfirmation::STATUS_PENDING, $first->fresh()->status);
        $this->assertSame(WhatsAppPendingConfirmation::STATUS_APPROVED, $second->fresh()->status);
    }

    public function test_first_wins_invalidates_second_response(): void
    {
        $pending = $this->seedPending('firstwin');

        $bridge = $this->partialBridgeExpectingResume(true, '✅ First wins.');

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn ($phone, $msg) => $msg === '✅ First wins.')
            ->andReturn(['success' => true, 'message_id' => 'out-first']);

        $this->assertTrue($bridge->handleInbound($this->waUser, $this->phone, 'ty_firstwin'));
        $this->assertSame(WhatsAppPendingConfirmation::STATUS_APPROVED, $pending->fresh()->status);

        // Second response (text reject) must get expiry copy — token already invalidated.
        $bridge2 = $this->bridge();
        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn ($phone, $msg) => $msg === WhatsAppConfirmationBridge::EXPIRED_MESSAGE)
            ->andReturn(['success' => true, 'message_id' => 'out-second']);

        $this->assertTrue($bridge2->handleInbound($this->waUser, $this->phone, 'NO firstwin'));
        $this->assertSame(WhatsAppPendingConfirmation::STATUS_APPROVED, $pending->fresh()->status);
    }

    public function test_refuses_payroll_tool_over_whatsapp(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not available over WhatsApp');

        $this->bridge()->sendConfirmation(
            $this->phone,
            $this->user,
            WhatsAppPendingConfirmation::MECHANISM_TIER2,
            [
                'tool' => 'toolAccountantManagePayroll',
                'args' => ['action' => 'run'],
                'preview' => 'Run payroll',
            ],
        );
    }

    public function test_refuses_impersonation_tool_over_whatsapp(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->bridge()->sendConfirmation(
            $this->phone,
            $this->user,
            WhatsAppPendingConfirmation::MECHANISM_APPROVABLE,
            [
                'conversation_id' => (string) Str::uuid(),
                'approval_id' => 'call_impersonate',
                'agent_class' => \App\Ai\Agents\PlatformOperationsAgent::class,
                'tool' => 'ImpersonateSchoolAdminTool',
                'preview' => 'Impersonate admin',
            ],
        );
    }

    public function test_bare_yes_is_not_consumed(): void
    {
        $pending = $this->seedPending('bareyes1');

        $handled = $this->bridge()->handleInbound($this->waUser, $this->phone, 'yes');

        $this->assertFalse($handled);
        $this->assertSame(
            WhatsAppPendingConfirmation::STATUS_PENDING,
            $pending->fresh()->status,
            'Bare yes must leave the pending confirmation open'
        );
    }

    /**
     * Precedence: pending check looks for button OR coded YES/NO only.
     * Free-form that does not match must fall through (handleInbound false)
     * so keywords / menu / Track-1 Toshi can continue — without resolving.
     */
    public function test_free_form_while_pending_falls_through_without_resolving(): void
    {
        $pending = $this->seedPending('freefrm1');

        $handled = $this->bridge()->handleInbound(
            $this->waUser,
            $this->phone,
            'what are my fees?'
        );

        $this->assertFalse(
            $handled,
            'Non-coded free-form must not be consumed by the confirmation bridge'
        );
        $this->assertSame(
            WhatsAppPendingConfirmation::STATUS_PENDING,
            $pending->fresh()->status,
            'Pending must stay open until coded YES/NO or ty_/tn_ button'
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedPending(string $token, array $overrides = []): WhatsAppPendingConfirmation
    {
        return WhatsAppPendingConfirmation::query()->create(array_merge([
            'token' => $token,
            'phone' => $this->phone,
            'user_id' => $this->user->id,
            'mechanism' => WhatsAppPendingConfirmation::MECHANISM_TIER2,
            'payload' => [
                'tool' => 'toolTeacherCreateTask',
                'args' => ['title' => 'Homework'],
                'preview' => 'Create task',
            ],
            'preview' => 'Create task',
            'status' => WhatsAppPendingConfirmation::STATUS_PENDING,
            'expires_at' => now()->addMinutes(15),
        ], $overrides));
    }

    private function bridge(): WhatsAppConfirmationBridge
    {
        return new WhatsAppConfirmationBridge($this->whatsApp);
    }

    /**
     * @return \Mockery\MockInterface&WhatsAppConfirmationBridge
     */
    private function partialBridgeExpectingResume(bool $approved, string $result)
    {
        $bridge = Mockery::mock(WhatsAppConfirmationBridge::class, [$this->whatsApp])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $bridge->shouldReceive('resume')
            ->once()
            ->withArgs(function (WhatsAppPendingConfirmation $pending, User $user, bool $isApproved) use ($approved) {
                return $isApproved === $approved && $user->id === $this->user->id;
            })
            ->andReturn($result);

        return $bridge;
    }
}
