<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPreviewV3Test extends TestCase
{
    public function test_landing_preview_returns_v4_sections_and_copy(): void
    {
        $response = $this->get('/landing-preview');

        $response->assertOk();
        $response->assertSee('id="hero"', false);
        $response->assertSee('id="trust"', false);
        $response->assertSee('id="compare"', false);
        $response->assertSee('id="community"', false);
        $response->assertSee('id="faq"', false);
        $response->assertSee('Open-source agentic school protocol', false);
        $response->assertSee('The school platform that operates in the tools educationists already use.', false);
        $response->assertSee('Protocol Cores', false);
        $response->assertSee('Built the way real infrastructure should be', false);
        $response->assertSee('Provable', false);
        $response->assertSee('Coming', false);
        $response->assertSee('Stated direction. Not a live feature yet.', false);
        $response->assertSee('What we actually address', false);
        $response->assertSee('Just tell Toshi what you need, in plain language, and it does the rest', false);
        $response->assertSee('Get notified when we open source', false);
        $response->assertSee('Do parents need to download an app?', false);
        $response->assertSee('One protocol layer. Toshi orchestrates WhatsApp, Drive, and Slack', false);
        $response->assertSee('Toshi · protocol orchestration', false);
        $response->assertSee('Protocol path', false);
        $response->assertSee('Through the WhatsApp connector', false);
        $response->assertDontSee('One intelligence layer orchestrating three perspectives on the same school.', false);
        $response->assertSee('build/assets/landing-preview-', false);
        $response->assertDontSee('Q1 2027', false);
        $response->assertDontSee('ds-kpi-card', false);
        $this->assertStringNotContainsString("\u{2014}", $response->getContent());
    }
}
