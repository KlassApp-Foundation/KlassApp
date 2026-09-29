<?php

namespace Tests\Feature\Security;

use App\Models\MessageDeliveryLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Meta signs every webhook POST with X-Hub-Signature-256. These tests cover the
 * verification middleware on both WhatsApp webhook routes, the unchanged GET
 * verify-token handshake, the instant switch-off flag, and the fail-closed
 * behaviour when no app secret is configured.
 */
class WhatsAppWebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-app-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.app_secret' => self::SECRET,
            'services.whatsapp.verify_signature' => true,
        ]);
    }

    private function signatureFor(string $body): string
    {
        return 'sha256='.hash_hmac('sha256', $body, self::SECRET);
    }

    /** @return array<string, string> */
    private function signedHeaders(string $body): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Hub-Signature-256' => $this->signatureFor($body),
        ];
    }

    public function test_inbound_post_without_signature_is_refused(): void
    {
        $this->postJson('/api/whatsapp/inbound', ['entry' => []])
            ->assertStatus(403);
    }

    public function test_inbound_post_with_wrong_signature_is_refused(): void
    {
        $body = json_encode(['entry' => []]);

        $this->call('POST', '/api/whatsapp/inbound', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Hub-Signature-256' => 'sha256='.str_repeat('0', 64),
        ], $body)->assertStatus(403);
    }

    public function test_inbound_post_with_correct_signature_is_accepted(): void
    {
        $body = json_encode(['entry' => []]);

        $this->call('POST', '/api/whatsapp/inbound', [], [], [], $this->signedHeaders($body), $body)
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_get_handshake_is_unchanged(): void
    {
        $token = config('services.whatsapp.business_verify_token');

        $this->get('/api/whatsapp/inbound?hub.mode=subscribe&hub.verify_token='.$token.'&hub.challenge=challenge-123')
            ->assertStatus(200)
            ->assertSee('challenge-123');

        $this->get('/api/whatsapp/inbound?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=challenge-123')
            ->assertStatus(403);
    }

    public function test_delivery_post_without_signature_is_refused(): void
    {
        $this->postJson('/api/whatsapp/delivery', [
            'phone' => '+256700000000',
            'status' => 'received',
            'direction' => 'inbound',
        ])->assertStatus(403);
    }

    public function test_delivery_with_correct_signature_behaves_the_same(): void
    {
        $log = MessageDeliveryLog::create([
            'whatsapp_message_id' => 'wamid.test.delivery.1',
            'phone' => '+256700000000',
            'direction' => 'outbound',
            'status' => 'sent',
        ]);

        $body = json_encode([
            'whatsapp_message_id' => 'wamid.test.delivery.1',
            'phone' => '+256700000000',
            'status' => 'delivered',
        ]);

        $this->call('POST', '/api/whatsapp/delivery', [], [], [], $this->signedHeaders($body), $body)
            ->assertStatus(200)
            ->assertJson(['status' => 'updated']);

        $this->assertSame('delivered', $log->fresh()->status);
    }

    public function test_verification_can_be_switched_off(): void
    {
        config(['services.whatsapp.verify_signature' => false]);

        $this->postJson('/api/whatsapp/inbound', ['entry' => []])
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_missing_secret_fails_closed(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        $this->postJson('/api/whatsapp/inbound', ['entry' => []])
            ->assertStatus(403);
    }
}
