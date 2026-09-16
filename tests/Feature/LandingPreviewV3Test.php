<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPreviewV3Test extends TestCase
{
    public function test_landing_preview_returns_v4_sections_and_copy(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="hero"', false);
        $response->assertSee('id="trust"', false);
        $response->assertSee('id="compare"', false);
        $response->assertSee('id="faq"', false);
        $response->assertSee('id="protocol"', false);
        $response->assertDontSee('id="community"', false);
        $response->assertDontSee('id="open-source"', false);
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
        $response->assertSee('klassapp-logo-primary.svg', false);
        $response->assertSee('navbar-logo-img', false);
        $response->assertSee('hero-bg-vintage', false);
        $response->assertSee('id="toshiTower"', false);
        $response->assertSee('data-toshi-tower="1"', false);
        $response->assertSee('images/brand/models/anthropic-mark.svg', false);
        $response->assertSee('images/brand/models/openai-mark.svg', false);
        $response->assertSee('images/brand/models/xai-grok-mark.svg', false);
        $response->assertSee('images/brand/models/google-gemini-mark.svg', false);
        $response->assertSee('images/brand/models/moonshot-kimi-mark.svg', false);
        $response->assertSee('images/brand/models/zhipu-zai-mark.svg', false);
        $this->assertStringNotContainsString('deepseek', strtolower($response->getContent()));
        $response->assertDontSee('toshi-visual-hub', false);
        $response->assertDontSee('toshi-visual-core', false);
        $response->assertSee('images/klassapp-logo.svg', false);
        $response->assertSee('Human in the loop', false);
        $response->assertSee('Before consequential writes, Toshi asks for confirmation', false);
        $response->assertDontSee('protocol-visual', false);
        $response->assertDontSee('class="mesh"', false);
        $response->assertDontSee('mesh-hub-mark', false);
        $response->assertSee('Not just software. A protocol.', false);
        $response->assertSee('Open Source', false);
        $response->assertSee('MIT licensed. Source and self-hosting will open publicly after an independent security review', false);
        $response->assertSee('Smarter schools start here.', false);
        $response->assertSee('site-footer', false);
        $response->assertSee('site-footer-wordmark', false);
        $response->assertDontSee('Stay in the loop', false);
        $response->assertDontSee('footer-columns', false);
        $response->assertSee('build/assets/landing-preview-', false);
        $response->assertDontSee('Q1 2027', false);
        $response->assertDontSee('ds-kpi-card', false);
        $this->assertStringNotContainsString("\u{2014}", $response->getContent());

        // Mobile nav + compare cards + real legal footer links (Sep 2026 mobile bugfix)
        $response->assertSee('id="navbarMobileToggle"', false);
        $response->assertSee('id="navbarMobilePanel"', false);
        $response->assertSee('compare-list', false);
        $response->assertSee('compare-card', false);
        $response->assertDontSee('compare-table-wrap', false);
        $response->assertSee('/terms-of-service', false);
        $response->assertSee('/privacy-policy', false);
        $content = $response->getContent();
        $this->assertStringNotContainsString('href="#">Terms</a>', $content);
        $this->assertStringNotContainsString('href="#">Privacy</a>', $content);

        // Hero role rotate + Toshi cloud-quality + real socials (Sep 2026 polish)
        // Hero X-flip + K-mark avatars (tower/flip integration)
        $response->assertSee('id="heroRoleDeck"', false);
        $response->assertSee('id="heroRoleDots"', false);
        $response->assertSee('Parent · WhatsApp', false);
        $response->assertSee('Teacher · Drive', false);
        $response->assertSee('Admin · Slack', false);
        $response->assertSee('hero-role-avatar', false);
        $response->assertSee('images/klassapp-icon.svg', false);
        $response->assertSee('brand-mark--whatsapp', false);
        $response->assertSee('brand-mark--slack', false);
        $response->assertSee('brand-mark--drive', false);
        $response->assertSee('fill="#25D366"', false);
        $response->assertSee('fill="#E01E5A"', false);
        $response->assertSee('fill="#0066da"', false);
        // Generic Lucide-style approximations must not remain for WA/Drive/Slack connectors
        $this->assertStringNotContainsString('stroke="#16A34A" stroke-width="2"><path d="M21 11.5a8.38', $content);
        $response->assertSee('https://x.com/klassapp', false);
        $response->assertSee('https://github.com/KlassApp-Foundation', false);
        $this->assertStringNotContainsString('href="#" class="site-footer-social"', $content);

        // Canonical DESIGN_SYSTEM type + parchment (Piece 1 marketing alignment)
        $response->assertSee('family=Sora', false);
        $response->assertSee('family=DM+Sans', false);
        $this->assertStringNotContainsString('Bricolage', $content);
        $this->assertStringNotContainsString('family=Inter', $content);

        $css = file_get_contents(resource_path('css/landing-preview.css'));
        $this->assertMatchesRegularExpression('/--text-primary:\s*#1E293B/i', $css);
        $this->assertMatchesRegularExpression('/--paper-base:\s*#FAFAF5/i', $css);
        $this->assertMatchesRegularExpression("/--font-display:\\s*'Sora'/i", $css);
        $this->assertMatchesRegularExpression("/--font-body:\\s*'DM Sans'/i", $css);
        $this->assertDoesNotMatchRegularExpression('/Bricolage/i', $css);
        $this->assertDoesNotMatchRegularExpression("/--font-body:\\s*'Inter'/i", $css);
        $this->assertDoesNotMatchRegularExpression('/--paper-base:\\s*#F5F0E6/i', $css);

        // Hero X-flip + tower emergence (no unresolved --d-* in live rules)
        $this->assertMatchesRegularExpression('/transform:\s*rotateX\(90deg\)/', $css);
        $this->assertMatchesRegularExpression('/\.hero-role-avatar\s*\{[^}]*border:\s*1px solid var\(--brand-green\)/s', $css);
        $this->assertMatchesRegularExpression('/@keyframes toshi-model-l/', $css);
        $this->assertMatchesRegularExpression('/\.toshi-tower\s*\{/', $css);
        $this->assertStringNotContainsString('var(--d-', $css);
    }
}
