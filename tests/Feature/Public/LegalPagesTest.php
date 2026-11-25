<?php

namespace Tests\Feature\Public;

use Tests\TestCase;

/**
 * Legal pages (/privacy and /terms): KlassApp draft content, no Gegosoft text,
 * draft banner present, correct structure.
 */
class LegalPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // ── Privacy Policy ──────────────────────────────────────────────────────

    public function test_privacy_page_returns_200(): void
    {
        $this->get('/privacy')->assertOk();
    }

    public function test_privacy_page_contains_klassapp_title(): void
    {
        $this->get('/privacy')->assertSee('Privacy Policy');
    }

    public function test_privacy_page_contains_all_twelve_sections(): void
    {
        $response = $this->get('/privacy');
        $response->assertSee('1. Who we are');
        $response->assertSee('2. Our role: schools decide, we process');
        $response->assertSee('12. Changes and contact');
    }

    public function test_privacy_page_does_not_contain_gegosoft(): void
    {
        $response = $this->get('/privacy');
        $response->assertDontSee('Gegosoft', false);
        $response->assertDontSee('GegoSoft', false);
        $response->assertDontSee('GegoK12', false);
        $response->assertDontSee('gegok12', false);
    }

    public function test_privacy_page_shows_draft_banner(): void
    {
        config(['app.legal_draft_banner' => 'Draft effective 25 November 2026. Under review.']);

        $this->get('/privacy')
            ->assertSee('Draft effective 25 November 2026. Under review.')
            ->assertSee('data-testid="legal-draft-banner"', false);
    }

    public function test_privacy_page_hides_banner_when_config_is_empty(): void
    {
        config(['app.legal_draft_banner' => '']);

        $this->get('/privacy')
            ->assertDontSee('data-testid="legal-draft-banner"', false);
    }

    public function test_privacy_page_contains_contact_email(): void
    {
        $this->get('/privacy')->assertSee('connect@klassapp.xyz');
    }

    // ── Terms of Service ────────────────────────────────────────────────────

    public function test_terms_page_returns_200(): void
    {
        $this->get('/terms')->assertOk();
    }

    public function test_terms_page_contains_klassapp_title(): void
    {
        $this->get('/terms')->assertSee('Terms of Service');
    }

    public function test_terms_page_contains_all_fourteen_sections(): void
    {
        $response = $this->get('/terms');
        $response->assertSee('1. Agreement');
        $response->assertSee('7. AI features');
        $response->assertSee('14. Changes and contact');
    }

    public function test_terms_page_does_not_contain_gegosoft(): void
    {
        $response = $this->get('/terms');
        $response->assertDontSee('Gegosoft', false);
        $response->assertDontSee('GegoSoft', false);
        $response->assertDontSee('GegoK12', false);
        $response->assertDontSee('gegok12', false);
    }

    public function test_terms_page_shows_draft_banner(): void
    {
        config(['app.legal_draft_banner' => 'Draft effective 25 November 2026. Under review.']);

        $this->get('/terms')
            ->assertSee('Draft effective 25 November 2026. Under review.')
            ->assertSee('data-testid="legal-draft-banner"', false);
    }

    public function test_terms_page_hides_banner_when_config_is_empty(): void
    {
        config(['app.legal_draft_banner' => '']);

        $this->get('/terms')
            ->assertDontSee('data-testid="legal-draft-banner"', false);
    }

    public function test_terms_page_contains_contact_email(): void
    {
        $this->get('/terms')->assertSee('connect@klassapp.xyz');
    }
}
