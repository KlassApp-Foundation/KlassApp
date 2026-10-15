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

    /**
     * SIGNUP_ENABLED is text in the environment. Each value is parsed by the
     * real config file, not set as a boolean, so "false" must close sign-up.
     *
     * @return array<string, array{0: ?string, 1: bool}>
     */
    public static function signupEnvValues(): array
    {
        return [
            'text false' => ['false', false],
            'text FALSE uppercase' => ['FALSE', false],
            'text zero' => ['0', false],
            'text off' => ['off', false],
            'empty value' => ['', false],
            'unset' => [null, true],
            'text true' => ['true', true],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('signupEnvValues')]
    public function test_T2_signup_enabled_env_text_is_parsed_as_boolean(?string $value, bool $expected): void
    {
        $saved = [getenv('SIGNUP_ENABLED'), $_ENV['SIGNUP_ENABLED'] ?? null, $_SERVER['SIGNUP_ENABLED'] ?? null];

        try {
            if ($value === null) {
                putenv('SIGNUP_ENABLED');
                unset($_ENV['SIGNUP_ENABLED'], $_SERVER['SIGNUP_ENABLED']);
            } else {
                putenv('SIGNUP_ENABLED=' . $value);
                $_ENV['SIGNUP_ENABLED'] = $value;
                $_SERVER['SIGNUP_ENABLED'] = $value;
            }

            $config = require base_path('config/klassapp.php');

            $this->assertSame($expected, $config['signup_enabled']);
        } finally {
            $saved[0] === false ? putenv('SIGNUP_ENABLED') : putenv('SIGNUP_ENABLED=' . $saved[0]);
            if ($saved[1] === null) { unset($_ENV['SIGNUP_ENABLED']); } else { $_ENV['SIGNUP_ENABLED'] = $saved[1]; }
            if ($saved[2] === null) { unset($_SERVER['SIGNUP_ENABLED']); } else { $_SERVER['SIGNUP_ENABLED'] = $saved[2]; }
        }
    }

    public function test_T2_signup_closed_register_verify_routes_go_to_early_access_not_404(): void
    {
        $this->get('/register/verify')->assertRedirect(route('early-access.show'));
        $this->post('/register/verify', ['code' => '123456'])->assertRedirect(route('early-access.show'));
        $this->post('/register/verify/resend')->assertRedirect(route('early-access.show'));
    }

    public function test_T2_signup_closed_toshi_create_mode_creates_no_school_for_a_school_admin(): void
    {
        $admin = app(SchoolSignupBootstrapService::class)->bootstrapAsOperator([
            'name' => 'Existing Admin',
            'email' => 'existing@example.com',
            'phone' => null,
            'password' => 'secret123',
        ]);
        $this->assertSame(1, School::count());

        $this->actingAs($admin->fresh());

        $this->fillToshiCreateMode(\Livewire\Livewire::test(\App\Livewire\AgentToshi::class), 'Second Closed School', 'second-admin@example.com')
            ->call('confirmOnboarding');

        $this->assertSame(1, School::count());
        $this->assertNull(User::where('email', 'second-admin@example.com')->first());
    }

    public function test_T2_signup_closed_toshi_create_mode_still_works_for_platform_admin(): void
    {
        $platform = User::create([
            'school_id' => null,
            'usergroup_id' => 1,
            'name' => 'Platform Admin',
            'email' => 'platform@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $this->actingAs($platform);

        $this->fillToshiCreateMode(\Livewire\Livewire::test(\App\Livewire\AgentToshi::class), 'Operator Created School', 'operator-admin@example.com')
            ->call('confirmOnboarding');

        $this->assertSame(1, School::where('name', 'Operator Created School')->count());
    }

    private function fillToshiCreateMode($component, string $schoolName, string $adminEmail)
    {
        return $component
            ->set('mode', 'create')
            ->set('schoolName', $schoolName)
            ->set('schoolEmail', 'office@' . \Illuminate\Support\Str::slug($schoolName) . '.example.com')
            ->set('schoolPhone', '0700999' . random_int(100, 999))
            ->set('adminName', 'New Admin')
            ->set('adminEmail', $adminEmail)
            ->set('adminPassword', 'not-a-real-password')
            ->set('schoolType', 'primary')
            ->set('curriculum', 'uneb')
            ->set('selectedPlanId', 1)
            ->set('standards', [['name' => 'P1']]);
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
