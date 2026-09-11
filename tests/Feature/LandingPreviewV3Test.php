<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPreviewV3Test extends TestCase
{
    public function test_landing_preview_returns_v3_sections_and_tokens(): void
    {
        $response = $this->get('/landing-preview');

        $response->assertOk();
        $response->assertSee('id="hero"', false);
        $response->assertSee('id="connectors"', false);
        $response->assertSee('id="toshi"', false);
        $response->assertSee('id="how-it-works"', false);
        $response->assertSee('id="trust"', false);
        $response->assertSee('id="community"', false);
        $response->assertSee('id="protocol"', false);
        $response->assertSee('id="open-source"', false);
        $response->assertSee('toshi-visual', false);
        $response->assertSee('toshi-visual-channel', false);
        $response->assertSee('ka-node', false);
        $response->assertSee('Tools connected by', false);
        $response->assertSee('Coming Q1 2027', false);
        $response->assertSee('Built for trust', false);
        $response->assertSee('OWASP', false);
        $response->assertSee('ui-chrome', false);
        $response->assertSee('wa-bubble', false);
        $response->assertSee('teach-shell', false);
        $response->assertSee('admin-stack', false);
        $response->assertSee('Get notified', false);
        $response->assertSee('build/assets/landing-preview-', false);
        $response->assertDontSee('--d-', false);
        $response->assertDontSee('ds-kpi-card', false);
    }
}
