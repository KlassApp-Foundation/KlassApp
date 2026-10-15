<?php

namespace Tests\Feature\Demo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * The /demo page rate limit comes from klassapp.demo.page_throttle_per_minute
 * (DEMO_PAGE_THROTTLE_PER_MINUTE). Default 60 a minute per IP.
 */
class DemoPageThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function demoLimit(): int
    {
        $limit = RateLimiter::limiter('demo-page')(Request::create('/demo'));

        return (int) $limit->maxAttempts;
    }

    public function test_demo_page_throttle_default_is_60_per_minute(): void
    {
        $this->assertSame(60, (int) config('klassapp.demo.page_throttle_per_minute'));
        $this->assertSame(60, $this->demoLimit());
        $this->assertContains('throttle:demo-page', app('router')->getRoutes()->getByName('demo.follow-along')->gatherMiddleware());
    }

    public function test_demo_page_throttle_follows_the_config(): void
    {
        config(['klassapp.demo.page_throttle_per_minute' => 600]);
        $this->assertSame(600, $this->demoLimit());
    }

    public function test_demo_page_throttle_refuses_past_the_limit(): void
    {
        config(['klassapp.demo.page_throttle_per_minute' => 2]);
        // The throttle middleware keys a named limiter as md5(name . key); the file cache outlives a run.
        RateLimiter::clear(md5('demo-page'.'demo-page|127.0.0.1'));
        $this->get('/demo')->assertStatus(200);
        $this->get('/demo')->assertStatus(200);
        $this->get('/demo')->assertStatus(429);
        RateLimiter::clear(md5('demo-page'.'demo-page|127.0.0.1'));
    }
}
