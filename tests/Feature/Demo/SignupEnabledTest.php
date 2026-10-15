<?php

namespace Tests\Feature\Demo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The single sign-up switch is config('klassapp.signup_enabled'). Closed
 * registration shows the early access page; nothing returns 404.
 */
class SignupEnabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON');
        config(['app.timezone' => 'Africa/Kampala']);
        date_default_timezone_set('Africa/Kampala');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    public function test_public_registration_is_open_by_default(): void
    {
        $this->assertTrue(config('klassapp.signup_enabled'));
        $this->get('/register')->assertOk();
    }

    public function test_only_one_signup_config_key_exists(): void
    {
        $this->assertNull(config('app.signup_enabled'));
    }

    public function test_public_registration_closed_shows_early_access_instead_of_404(): void
    {
        config(['klassapp.signup_enabled' => false]);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->get('/register')->assertOk()->assertSee('data-testid="early-access-form"', false);
        $this->post('/register', [
            'name' => 'Ada Closed',
            'email' => 'ada.closed@example.com',
            'password' => 'not-a-real-password',
            'password_confirmation' => 'not-a-real-password',
        ])->assertRedirect(route('early-access.show'));
        $this->get('/register/verify')->assertRedirect(route('early-access.show'));
        $this->post('/register/verify', ['code' => '123456'])->assertRedirect(route('early-access.show'));
        $this->post('/register/verify/resend')->assertRedirect(route('early-access.show'));
        $this->get('/login')->assertOk();
        $this->assertDatabaseMissing('users', ['email' => 'ada.closed@example.com']);
    }
}
