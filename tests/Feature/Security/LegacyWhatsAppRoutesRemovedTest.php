<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * The legacy WhatsApp REST data endpoints were unauthenticated and had no in-repo caller.
 * They are removed; the live Meta WABA webhook stays. These tests fail if a legacy route
 * comes back, and confirm the webhook route is still routed.
 */
class LegacyWhatsAppRoutesRemovedTest extends TestCase
{
    /** @return array<int, array{0:string,1:string}> */
    private function legacyRoutes(): array
    {
        return [
            ['POST', '/api/whatsapp/identify-user'],
            ['GET', '/api/whatsapp/student/1/grades'],
            ['GET', '/api/whatsapp/student/1/report'],
            ['GET', '/api/whatsapp/student/1/attendance'],
            ['GET', '/api/whatsapp/fees/1/balance'],
            ['GET', '/api/whatsapp/school/1/events'],
        ];
    }

    public function test_every_legacy_whatsapp_route_returns_404(): void
    {
        foreach ($this->legacyRoutes() as [$method, $uri]) {
            $status = $method === 'POST'
                ? $this->postJson($uri)->getStatusCode()
                : $this->getJson($uri)->getStatusCode();

            $this->assertSame(404, $status, "{$method} {$uri} should no longer be routed");
        }
    }

    public function test_the_live_waba_webhook_is_still_routed(): void
    {
        $uris = collect(app('router')->getRoutes())->map(fn ($route) => $route->uri());

        $this->assertTrue($uris->contains('api/whatsapp/inbound'), 'the live inbound webhook must stay routed');
        $this->assertTrue($uris->contains('api/whatsapp/delivery'), 'the delivery callback is deliberately kept');

        foreach ($this->legacyRoutes() as [$method, $uri]) {
            $this->assertFalse($uris->contains(ltrim($uri, '/')), "{$uri} must not be routed");
        }
    }

    public function test_the_unused_hmac_middleware_is_gone(): void
    {
        // It was never applied to any route, so it protected nothing while implying it did.
        $this->assertFileDoesNotExist(app_path('Http/Middleware/WhatsAppHmac.php'));
    }
}
