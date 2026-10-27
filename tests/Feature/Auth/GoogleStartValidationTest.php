<?php

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesSignupCountriesTable;
use Tests\TestCase;

class GoogleStartValidationTest extends TestCase
{
    use CreatesSignupCountriesTable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSignupCountriesTable();
    }

    #[Test]
    public function google_start_accepts_empty_registration_fields_and_redirects_to_google(): void
    {
        $register = $this->get('/register');
        $register->assertStatus(200);

        $html = $register->getContent();
        $this->assertMatchesRegularExpression('/name="_token" value="([^"]+)"/', $html);
        preg_match('/name="_token" value="([^"]+)"/', $html, $matches);
        $token = $matches[1];

        $response = $this->post('/auth/google/start', [
            '_token' => $token,
            'name' => '',
            'email' => '',
            'phone' => '',
            'termsandcondn' => '',
        ]);

        $response->assertStatus(302);
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

}
