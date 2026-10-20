<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Meta delivers webhooks from a small pool of IPs, so a request carrying a valid
 * X-Hub-Signature-256 must not hit the 60/min per-IP api limit. Unsigned and
 * badly signed requests keep a per-IP limit (and are still refused with 403).
 */
class WhatsAppWebhookSignedThrottleTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-app-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.whatsapp.app_secret' => self::SECRET,
            'services.whatsapp.verify_signature' => true,
            'services.whatsapp.business_verify_token' => 'test-verify-token',
        ]);
        $this->app['cache']->store()->flush();
    }

    private function postSigned(string $uri, int $i, ?string $signature = null)
    {
        $body = json_encode(['object' => 'whatsapp_business_account', 'entry' => [], 'n' => $i]);

        return $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => '173.252.0.10',
            'HTTP_X-Hub-Signature-256' => $signature ?? 'sha256='.hash_hmac('sha256', $body, self::SECRET),
        ], $body);
    }

    public function test_61_plus_signed_inbound_posts_from_one_ip_all_succeed(): void
    {
        for ($i = 1; $i <= 75; $i++) {
            $this->postSigned('/api/whatsapp/inbound', $i)->assertStatus(200);
        }
    }

    public function test_61_plus_signed_delivery_posts_from_one_ip_all_succeed(): void
    {
        for ($i = 1; $i <= 75; $i++) {
            $this->assertNotSame(429, $this->postSigned('/api/whatsapp/delivery', $i)->status(), "request $i");
        }
    }

    public function test_unsigned_posts_get_429_after_60_per_minute(): void
    {
        for ($i = 1; $i <= 60; $i++) {
            $this->call('POST', '/api/whatsapp/inbound', [], [], [], ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '10.9.8.7'], '{"entry":[]}')
                ->assertStatus(403);
        }
        $this->call('POST', '/api/whatsapp/inbound', [], [], [], ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '10.9.8.7'], '{"entry":[]}')
            ->assertStatus(429);
    }

    public function test_invalid_signature_is_refused_and_still_limited(): void
    {
        $bad = 'sha256='.str_repeat('0', 64);
        for ($i = 1; $i <= 60; $i++) {
            $this->postSigned('/api/whatsapp/inbound', $i, $bad)->assertStatus(403);
        }
        $this->postSigned('/api/whatsapp/inbound', 61, $bad)->assertStatus(429);
    }

    public function test_signed_traffic_does_not_open_the_door_for_unsigned_from_the_same_ip(): void
    {
        for ($i = 1; $i <= 70; $i++) {
            $this->postSigned('/api/whatsapp/inbound', $i)->assertStatus(200);
        }
        // Unsigned requests from the same IP still have their full own budget, then 429.
        for ($i = 1; $i <= 60; $i++) {
            $this->call('POST', '/api/whatsapp/inbound', [], [], [], ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '173.252.0.10'], '{"entry":[]}')
                ->assertStatus(403);
        }
        $this->call('POST', '/api/whatsapp/inbound', [], [], [], ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '173.252.0.10'], '{"entry":[]}')
            ->assertStatus(429);
    }

    public function test_get_verify_handshake_still_works(): void
    {
        $token = config('services.whatsapp.business_verify_token');

        $this->get('/api/whatsapp/inbound?hub.mode=subscribe&hub.verify_token='.$token.'&hub.challenge=challenge-123')
            ->assertStatus(200)->assertSee('challenge-123');
        $this->get('/api/whatsapp/inbound?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=x')
            ->assertStatus(403);
    }

    public function test_other_api_routes_keep_the_60_per_minute_limit(): void
    {
        $limited = false;
        for ($i = 1; $i <= 61; $i++) {
            if ($this->call('POST', '/api/schoolpay/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '10.1.1.1'], '{}')->status() === 429) {
                $limited = true;
                break;
            }
        }
        $this->assertTrue($limited, 'api group throttle:60,1 must still apply to other api routes');
    }
}
