<?php

namespace Tests\Feature\Demo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignupEnabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_open_by_default(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_public_registration_closes_when_signup_is_disabled(): void
    {
        config(['app.signup_enabled' => false]);

        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Ada Closed',
            'email' => 'ada.closed@example.com',
            'password' => 'not-a-real-password',
            'password_confirmation' => 'not-a-real-password',
        ])->assertNotFound();
        $this->get('/register/verify')->assertNotFound();
        $this->get('/login')->assertOk();
    }
}
