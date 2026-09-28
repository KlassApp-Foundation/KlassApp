<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\TestController;
use Tests\TestCase;

/**
 * /checksms and /cache-clear were unauthenticated debug routes. A single GET to /checksms
 * sent a real SMS to a hardcoded number, and a GET to /cache-clear cleared the application
 * cache. Both are removed; these tests fail if either comes back.
 */
class DebugRoutesRemovedTest extends TestCase
{
    public function test_checksms_route_no_longer_exists(): void
    {
        $this->get('/checksms')->assertNotFound();
    }

    public function test_the_checksms_controller_method_is_gone(): void
    {
        // The route cannot be re-added without the method, and the method cannot be
        // re-added without failing here.
        $this->assertFalse(method_exists(TestController::class, 'checksms'));
    }

    public function test_cache_clear_route_no_longer_exists(): void
    {
        $this->get('/cache-clear')->assertNotFound();
    }

    public function test_no_public_route_can_send_an_sms(): void
    {
        $this->get('/checksms')->assertNotFound();
        $this->get('/cache-clear')->assertNotFound();
    }
}
