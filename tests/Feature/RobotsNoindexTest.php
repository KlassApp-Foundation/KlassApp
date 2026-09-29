<?php

namespace Tests\Feature;

use Tests\TestCase;

class RobotsNoindexTest extends TestCase
{
    public function test_header_applies_to_page_redirect_and_404_when_enabled(): void
    {
        config(['app.robots_noindex' => true]);

        $page = $this->get('/');
        $page->assertOk();
        $page->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $redirect = $this->get('/landing-preview');
        $redirect->assertRedirect('/');
        $redirect->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $notFound = $this->get('/no-such-page-'.uniqid());
        $notFound->assertNotFound();
        $notFound->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_robots_txt_disallows_everything_when_enabled(): void
    {
        config(['app.robots_noindex' => true]);

        $response = $this->get('/robots.txt');
        $response->assertOk();
        $this->assertSame("User-agent: *\nDisallow: /\n", $response->getContent());
    }

    public function test_no_header_and_robots_txt_allows_when_disabled(): void
    {
        config(['app.robots_noindex' => false]);

        $page = $this->get('/');
        $page->assertOk();
        $page->assertHeaderMissing('X-Robots-Tag');

        $robots = $this->get('/robots.txt');
        $robots->assertOk();
        $robots->assertHeaderMissing('X-Robots-Tag');
        $this->assertSame("User-agent: *\nDisallow:\n", $robots->getContent());
    }
}
