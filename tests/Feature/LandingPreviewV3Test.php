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
        $response->assertSee('id="protocol"', false);
        $response->assertSee('id="open-source"', false);
        $response->assertSee('toshi-visual', false);
        $response->assertSee('toshi-visual-channel', false);
        $response->assertSee('ka-node', false);
        $response->assertSee('Tools connected by', false);
        $response->assertSee('Coming Q1 2027', false);
        $response->assertSee('build/assets/landing-preview-', false);
    }
}
