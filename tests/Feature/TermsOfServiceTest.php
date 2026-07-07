<?php

namespace Tests\Feature;

use Tests\TestCase;

class TermsOfServiceTest extends TestCase
{
    public function test_terms_of_service_page_loads(): void
    {
        $response = $this->get('/terms-of-service');

        $response->assertStatus(200);
        $response->assertSee('Terms of Service');
        $response->assertSee('KlassApp');
    }

    public function test_privacy_policy_page_loads(): void
    {
        $response = $this->get('/privacy-policy');

        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
        $response->assertSee('KlassApp');
    }

    public function test_terms_page_contains_ugandan_law_references(): void
    {
        $response = $this->get('/terms-of-service');

        $response->assertStatus(200);
        $response->assertSee('Uganda Data Protection and Privacy Act');
    }

    public function test_terms_page_contains_data_protection_clauses(): void
    {
        $response = $this->get('/terms-of-service');

        $response->assertStatus(200);
        $response->assertSee('Data Protection');
        $response->assertSee('Security');
    }
}