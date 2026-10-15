<?php

namespace Tests\Feature\Demo;

use App\Models\WhatsAppUser;
use App\Services\DemoOnlyRecipientGuard;
use App\Services\DemoSchoolCommsGuard;
use App\Services\WhatsAppBusinessService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\Feature\Demo\Concerns\BuildsFollowAlongSchools;
use Tests\TestCase;

/**
 * Local café demo, send guard: with WHATSAPP_DEMO_ONLY_RECIPIENTS on, the
 * one outbound choke point (WhatsAppBusinessService) refuses every number
 * that is not bound to, or pending for, a demo school's student, and every
 * number linked to a school that is not a demo school, and logs it. The
 * sender of the message being answered still gets the reply.
 */
class DemoOnlyRecipientGuardTest extends TestCase
{
    use BuildsFollowAlongSchools;
    use RefreshDatabase;

    private const BOUND = '256700000951';

    private const RANDOM = '+256700000999';

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config(['app.timezone' => 'Pacific/Auckland']);
        date_default_timezone_set('Pacific/Auckland');
        Carbon::setTestNow(Carbon::parse('2026-10-17 10:00:00', 'Pacific/Auckland'));
        config([
            'services.whatsapp.app_secret' => self::APP_SECRET,
            'services.whatsapp.verify_signature' => true,
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_parent_user_id' => 0,
            'services.whatsapp.demo_only_recipients' => true,
            'klassapp.demo.whatsapp_number' => '+1 555 010 0000',
            'klassapp.demo.bind_ttl_minutes' => 45,
            'klassapp.demo.bind_requires_approval' => false,
            'app.url' => 'https://demo.example.com',
        ]);
        DemoSchoolCommsGuard::flushCache();
        DemoOnlyRecipientGuard::flush();
        $this->relaxDeliveryLog();
        $this->buildSchools();
        DemoSchoolCommsGuard::flushCache();
        Http::preventStrayRequests();
        $this->fakeGraph();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    private function resetHttp(): void
    {
        Http::fake();
        Http::preventStrayRequests();
        $this->fakeGraph();
    }

    private function realParentPhone(): string
    {
        return (string) WhatsAppUser::where('school_id', $this->fx['real']['school']->id)->value('phone');
    }

    /** A business send to $phone the way a marks release makes it. */
    private function send(string $phone): array
    {
        return DemoSchoolCommsGuard::duringBoundDemoRelease(
            $phone,
            fn () => app(WhatsAppBusinessService::class)->sendText($phone, 'test', 'marks_release'),
        );
    }

    public function test_demo_local_guard_refuses_a_real_school_recipient(): void
    {
        Log::spy();

        $result = $this->send($this->realParentPhone());

        $this->assertTrue($result['blocked']);
        $this->assertSame('demo_only_recipients', $result['reason']);
        $this->assertStringContainsString('linked_to_a_school_that_is_not_a_demo_school', $result['message']);
        $this->assertSame([], $this->outbound());
        Log::shouldHaveReceived('warning')->withArgs(fn ($msg, $ctx = []) => str_contains($msg, '[demo-only-guard]')
            && $ctx['reason'] === 'linked_to_a_school_that_is_not_a_demo_school'
            && ! str_contains(json_encode($ctx), $this->realParentPhone()));
    }

    public function test_demo_local_guard_refuses_a_random_number(): void
    {
        Log::spy();

        $result = $this->send(self::RANDOM);

        $this->assertTrue($result['blocked']);
        $this->assertStringContainsString('not_a_demo_recipient', $result['message']);
        $this->assertSame([], $this->outbound());
        Log::shouldHaveReceived('warning')->withArgs(fn ($msg, $ctx = []) => str_contains($msg, '[demo-only-guard]') && $ctx['to'] === '•••999');
    }

    public function test_demo_local_guard_allows_a_bound_demo_number(): void
    {
        $this->inbound(self::BOUND, 'BIND ' . $this->fx['demo']['students'][0]['code'])->assertOk();
        $this->resetHttp();

        $result = $this->send('+' . self::BOUND);

        $this->assertTrue($result['success']);
        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertSame(self::BOUND, $sent[0]['to']);
    }

    public function test_demo_local_guard_allows_a_pending_demo_number(): void
    {
        config(['klassapp.demo.bind_requires_approval' => true]);
        $this->inbound(self::BOUND, 'BIND ' . $this->fx['demo']['students'][0]['code'])->assertOk();
        $this->assertStringContainsString('Request sent', $this->outbound()[0]['body']);
        $this->resetHttp();

        $result = $this->send('+' . self::BOUND);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $this->outbound());
    }

    public function test_demo_local_guard_lets_the_sender_of_the_current_message_get_the_reply(): void
    {
        $this->inbound('256700000952', 'MARKS')->assertOk();

        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertSame('256700000952', $sent[0]['to']);
        $this->assertStringContainsString('not following a demo student', $sent[0]['body']);

        // Outside that conversation the same number is refused again.
        $this->resetHttp();
        $this->assertTrue($this->send('+256700000952')['blocked']);
    }

    public function test_demo_local_guard_gives_a_real_school_number_no_reply_even_when_it_writes(): void
    {
        $digits = ltrim($this->realParentPhone(), '+');

        $this->inbound($digits, 'MARKS')->assertOk();

        $this->assertSame([], $this->outbound());
    }

    public function test_demo_local_guard_is_off_by_default(): void
    {
        config(['services.whatsapp.demo_only_recipients' => false]);

        $this->assertNull(DemoOnlyRecipientGuard::refusal(self::RANDOM));
        $this->assertTrue(app(WhatsAppBusinessService::class)->sendText(self::RANDOM, 'x')['success']);
    }

    /** The exact envelope Meta posts (object, entry id, field, metadata, timestamp), signed. */
    private function metaWebhook(array $value): \Illuminate\Testing\TestResponse
    {
        $body = json_encode(['object' => 'whatsapp_business_account', 'entry' => [[
            'id' => '102290129340398',
            'changes' => [['field' => 'messages', 'value' => ['messaging_product' => 'whatsapp',
                'metadata' => ['display_phone_number' => '256793844906', 'phone_number_id' => '106540352242922']] + $value]],
        ]]]);

        return $this->call('POST', '/api/whatsapp/inbound', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Hub-Signature-256' => 'sha256=' . hash_hmac('sha256', $body, self::APP_SECRET),
        ], $body);
    }

    public function test_demo_guard_answers_a_real_meta_payload_and_ignores_status_callbacks(): void
    {
        config(['klassapp.demo.bind_requires_approval' => true]);
        $code = $this->fx['demo']['students'][0]['code'];
        $this->metaWebhook([
            'contacts' => [['profile' => ['name' => 'Attendee'], 'wa_id' => self::BOUND]],
            'messages' => [['from' => self::BOUND, 'id' => 'wamid.HBgMMjU2NzAwMDAwOTUxFQIAEhgg', 'timestamp' => (string) now()->timestamp, 'type' => 'text', 'text' => ['body' => 'BIND ' . $code]]],
        ])->assertOk();
        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertSame(self::BOUND, $sent[0]['to']);
        $this->assertStringContainsString('Request sent', $sent[0]['body']);

        $this->resetHttp();
        $this->metaWebhook(['statuses' => [['id' => 'wamid.out', 'status' => 'delivered', 'timestamp' => (string) now()->timestamp, 'recipient_id' => self::BOUND]]])->assertOk();
        $this->assertCount(0, $this->outbound());
    }
}
