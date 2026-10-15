<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Standing rule 42: run under a non-UTC application timezone.
        config(['app.timezone' => 'Africa/Kampala']);
        date_default_timezone_set('Africa/Kampala');
        config(['security_headers.enabled' => true]);
    }

    public function test_security_headers_referrer_permissions_and_report_only_csp_are_sent(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $csp = $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString('report-uri /csp-report', $csp);
        $this->assertFalse($response->headers->has('Content-Security-Policy'), 'CSP must be report-only');
    }

    public function test_security_headers_also_on_not_found_pages(): void
    {
        $response = $this->get('/no-such-page-'.uniqid());

        $response->assertNotFound();
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_security_headers_hsts_only_on_secure_production_requests(): void
    {
        $this->app['env'] = 'production';
        $secure = $this->get('https://localhost/');
        $secure->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $this->assertStringNotContainsString('preload', $secure->headers->get('Strict-Transport-Security'));

        $plain = $this->get('http://localhost/');
        $this->assertFalse($plain->headers->has('Strict-Transport-Security'));

        $this->app['env'] = 'testing';
        $notProd = $this->get('https://localhost/');
        $this->assertFalse($notProd->headers->has('Strict-Transport-Security'));
    }

    public function test_security_headers_switch_off_with_config(): void
    {
        config(['security_headers.enabled' => false]);

        $response = $this->get('/');

        $this->assertFalse($response->headers->has('Referrer-Policy'));
        $this->assertFalse($response->headers->has('Permissions-Policy'));
        $this->assertFalse($response->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function test_security_headers_keep_a_header_that_is_already_set(): void
    {
        Route::get('/__sec-header-preset', fn () => response('ok')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Content-Security-Policy', "default-src 'none'"));

        $response = $this->get('/__sec-header-preset');

        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('Content-Security-Policy', "default-src 'none'");
        $this->assertFalse($response->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function test_security_headers_csp_report_is_logged_without_personal_data(): void
    {
        config(['cache.limiter' => 'array']);
        $this->app->forgetInstance(\Illuminate\Cache\RateLimiter::class);
        Log::spy();

        $response = $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode([
            'csp-report' => [
                'document-uri' => 'https://example.com/admin/students/123/edit?email=someone@example.com',
                'effective-directive' => 'script-src-elem',
                'blocked-uri' => 'https://cdn.example.net/lib.js?token=abc',
            ],
        ]));

        $response->assertNoContent();
        Log::shouldHaveReceived('warning')->once()->withArgs(function ($message, $context) {
            return $message === 'csp.violation'
                && $context === [
                    'directive' => 'script-src-elem',
                    'blocked' => 'https://cdn.example.net',
                    'page' => '/admin/students/{id}/edit',
                ];
        });
    }

    public function test_security_headers_csp_report_endpoint_is_rate_limited(): void
    {
        // A fresh in-memory limiter so earlier runs cannot leave hits behind.
        config(['cache.limiter' => 'array']);
        $this->app->forgetInstance(\Illuminate\Cache\RateLimiter::class);

        for ($i = 0; $i < 30; $i++) {
            $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], '{}')->assertNoContent();
        }

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], '{}')->assertStatus(429);
    }
}
