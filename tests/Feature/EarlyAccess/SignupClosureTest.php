<?php

namespace Tests\Feature\EarlyAccess;

use App\Exceptions\SignupClosedException;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolSignupBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Task 2: with klassapp.signup_enabled=false no self-serve path creates a
 * school or a school admin. One test per path. Foreign keys on, non-UTC
 * app timezone (AGENTS.md rules 35 and 42).
 */
class SignupClosureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        // The test cache store is a file store; clear rate limiter hits.
        \Illuminate\Support\Facades\Cache::flush();
        config(['app.timezone' => 'Africa/Kampala']);
        date_default_timezone_set('Africa/Kampala');

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('plans')->insert([
            ['id' => 1, 'cycle' => 30, 'name' => 'Freemium', 'display_name' => 'Freemium', 'order' => 1, 'is_active' => 1, 'amount' => 0, 'no_of_students' => 0, 'no_of_users' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        config(['klassapp.signup_enabled' => false]);
    }

    protected function tearDown(): void
    {
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    public function test_T2_signup_closed_register_page_shows_early_access_form(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertSee('data-testid="early-access-form"', false);
        $response->assertDontSee('Create account with password');
        $response->assertDontSee('register-google', false);
    }

    public function test_T2_signup_closed_register_post_creates_no_school_or_admin(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Grace Example',
            'email' => 'grace@example.com',
            'phone' => '0701234567',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'termsandcondn' => '1',
        ])->assertRedirect(route('early-access.show'));

        $this->assertSame(0, School::count());
        $this->assertSame(0, User::count());
    }

    public function test_T2_signup_closed_google_start_does_not_begin_signup(): void
    {
        Socialite::shouldReceive('driver')->never();

        $this->post('/auth/google/start', ['name' => 'Grace', 'email' => 'grace@example.com'])
            ->assertRedirect(route('early-access.show'));

        $this->assertNull(session('saas_signup'));
    }

    public function test_T2_signup_closed_google_callback_unknown_email_creates_nothing(): void
    {
        $this->mockGoogle('stranger@example.com', 'google-stranger-1');

        $this->get('/auth/google/callback')->assertRedirect(route('early-access.show'));

        $this->assertGuest();
        $this->assertSame(0, School::count());
        $this->assertSame(0, User::count());
    }

    public function test_T2_signup_closed_google_callback_existing_user_still_signs_in(): void
    {
        $user = app(SchoolSignupBootstrapService::class)->bootstrapAsOperator([
            'name' => 'Existing Admin',
            'email' => 'existing@example.com',
            'phone' => null,
            'password' => 'secret123',
        ]);

        $this->mockGoogle('existing@example.com', 'google-existing-1');

        $this->get('/auth/google/callback')->assertRedirect();

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame(1, School::count());
    }

    public function test_T2_signup_closed_bootstrap_service_refuses_for_any_caller(): void
    {
        $this->expectException(SignupClosedException::class);

        try {
            app(SchoolSignupBootstrapService::class)->bootstrap([
                'name' => 'Grace Example',
                'email' => 'grace@example.com',
                'phone' => null,
                'password' => 'secret123',
            ]);
        } finally {
            $this->assertSame(0, School::count());
            $this->assertSame(0, User::count());
        }
    }

    public function test_T2_signup_closed_login_link_says_request_early_access(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Request early access');
        $response->assertDontSee('>Sign up</a>', false);
    }

    public function test_T2_signup_open_register_page_still_shows_signup_form(): void
    {
        config(['klassapp.signup_enabled' => true, 'settings.register_status' => '1']);

        $this->get('/register')->assertOk()->assertSee('Create account with password');
    }

    private function mockGoogle(string $email, string $id): void
    {
        $google = Mockery::mock(SocialiteUser::class);
        $google->shouldReceive('getId')->andReturn($id);
        $google->shouldReceive('getEmail')->andReturn($email);
        $google->shouldReceive('getName')->andReturn('Google Person');
        $google->shouldReceive('getAvatar')->andReturn('https://example.test/a.png');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($google);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }
}
