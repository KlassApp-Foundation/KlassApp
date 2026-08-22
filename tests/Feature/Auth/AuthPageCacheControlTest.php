<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class AuthPageCacheControlTest extends TestCase
{
    /**
     * @test
     */
    public function register_page_prevents_browser_caching_with_no_store(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    /**
     * @test
     */
    public function login_page_prevents_browser_caching_with_no_store(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    /**
     * @test
     */
    public function google_login_get_is_not_csrf_protected_and_redirects(): void
    {
        $response = $this->get('/auth/google');

        $response->assertStatus(302);
    }

    /**
     * @test
     */
    public function register_google_button_has_formnovalidate_to_skip_password_field_validation(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $this->assertStringContainsString('formnovalidate', $response->getContent());
        $this->assertStringContainsString('/auth/google/start', $response->getContent());
    }
}
