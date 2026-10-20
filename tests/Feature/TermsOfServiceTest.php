<?php

namespace Tests\Feature;

use Illuminate\Contracts\Foundation\MaintenanceMode;
use Tests\TestCase;

class TermsOfServiceTest extends TestCase
{
    public function test_canonical_privacy_page_returns_200_for_a_guest(): void
    {
        $this->assertGuest();

        $response = $this->get('/privacy');

        $response->assertOk();
        $response->assertSee('Privacy Policy');
        $response->assertSee('KlassApp');
    }

    public function test_canonical_terms_page_returns_200_for_a_guest(): void
    {
        $this->assertGuest();

        $response = $this->get('/terms');

        $response->assertOk();
        $response->assertSee('Terms of Service');
        $response->assertSee('KlassApp');
    }

    public function test_privacy_aliases_redirect_permanently_to_privacy(): void
    {
        foreach (['/ploicy', '/policy', '/privacy-policy'] as $alias) {
            $this->get($alias)
                ->assertStatus(301)
                ->assertRedirect('/privacy');
        }
    }

    public function test_terms_of_service_redirects_permanently_to_terms(): void
    {
        $this->get('/terms-of-service')
            ->assertStatus(301)
            ->assertRedirect('/terms');
    }

    public function test_legal_pages_stay_up_for_guests_during_maintenance(): void
    {
        $this->app->instance(MaintenanceMode::class, new class implements MaintenanceMode
        {
            public function activate(array $payload): void {}

            public function deactivate(): void {}

            public function active(): bool
            {
                return true;
            }

            public function data(): array
            {
                return [];
            }
        });

        $this->assertGuest();
        $this->get('/privacy')->assertOk();
        $this->get('/terms')->assertOk();
        $this->get('/ploicy')->assertStatus(301)->assertRedirect('/privacy');
        $this->get('/this-route-is-not-a-legal-page')->assertStatus(503);
    }

    public function test_legal_aliases_keep_the_query_string(): void
    {
        $this->get('/privacy-policy?hl=en')
            ->assertStatus(301)
            ->assertRedirect('/privacy?hl=en');

        $this->get('/terms-of-service?source=consent')
            ->assertStatus(301)
            ->assertRedirect('/terms?source=consent');
    }

    public function test_terms_page_contains_ugandan_law_references(): void
    {
        $response = $this->get('/terms');

        $response->assertStatus(200);
        $response->assertSee('Uganda Data Protection and Privacy Act');
    }

    public function test_terms_page_contains_data_protection_clauses(): void
    {
        $response = $this->get('/terms');

        $response->assertStatus(200);
        $response->assertSee('Data Protection');
        $response->assertSee('Security');
    }
}
