<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public legal pages: Privacy Policy and Terms of Service.
 *
 * These tests cover the merged v0.2 privacy policy (16 sections) and confirm
 * that the pages load, contain the right content, carry no Gegosoft branding,
 * and show or hide the draft banner based on the config value.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------ //
    // Privacy policy
    // ------------------------------------------------------------------ //

    public function test_privacy_policy_returns_200(): void
    {
        $this->get(route('privacy'))->assertStatus(200);
    }

    public function test_privacy_policy_contains_all_16_headings(): void
    {
        $response = $this->get(route('privacy'));

        foreach (range(1, 17) as $n) {
            $response->assertSee(__("policy.s{$n}_heading"), false);
        }
    }

    public function test_privacy_policy_contains_no_gegosoft(): void
    {
        $response = $this->get(route('privacy'));

        $response->assertDontSee('Gegosoft', false);
        $response->assertDontSee('GegoK12', false);
        $response->assertDontSee('gegok12', false);
    }

    public function test_privacy_policy_contains_contact_email(): void
    {
        $this->get(route('privacy'))->assertSee('connect@klassapp.xyz', false);
    }

    public function test_privacy_policy_shows_draft_banner_by_default(): void
    {
        config(['app.legal_draft_banner' => 'Draft effective 25 November 2026. Under review.']);

        $response = $this->get(route('privacy'));

        $response->assertSee('data-testid="legal-draft-banner"', false);
        $response->assertSee('Draft effective 25 November 2026. Under review.', false);
    }

    public function test_privacy_policy_hides_draft_banner_when_config_is_empty(): void
    {
        config(['app.legal_draft_banner' => '']);

        $response = $this->get(route('privacy'));

        $response->assertDontSee('data-testid="legal-draft-banner"', false);
    }

    public function test_privacy_policy_does_not_show_reviewer_notes(): void
    {
        $response = $this->get(route('privacy'));

        $response->assertDontSee('Notes for the reviewer', false);
        $response->assertDontSee('remove before publication', false);
    }

    // ------------------------------------------------------------------ //
    // Terms of service
    // ------------------------------------------------------------------ //

    public function test_terms_returns_200(): void
    {
        $this->get(route('terms'))->assertStatus(200);
    }

    public function test_terms_contains_no_gegosoft(): void
    {
        $response = $this->get(route('terms'));

        $response->assertDontSee('Gegosoft', false);
        $response->assertDontSee('GegoK12', false);
        $response->assertDontSee('gegok12', false);
    }

    public function test_terms_contains_klassapp_branding(): void
    {
        $this->get(route('terms'))->assertSee('KlassApp', false);
    }
}
