<?php

namespace Tests\Feature\Security;

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

    public function test_the_test_controller_itself_is_gone(): void
    {
        // The whole controller was removed: it held an unrouted webhook that created users
        // with a password taken from request data, plus the SMS-sending checksms() method.
        $this->assertFileDoesNotExist(app_path('Http/Controllers/TestController.php'));
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
