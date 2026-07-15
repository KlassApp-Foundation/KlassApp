<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginRegressionTest extends TestCase
{
    use RefreshDatabase;
    private const TEST_EMAIL = 'qa+local@klassapp.test';
    private const TEST_PASSWORD = 'Passw0rd!123';

    protected function setUp(): void
    {
        parent::setUp();

        // Focus these tests on auth behavior, not CSRF token plumbing.
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->ensureTestUser();
    }

    public function test_guest_is_redirected_from_admin_dashboard_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'email' => self::TEST_EMAIL,
            'password' => 'WrongPass!999',
        ]);

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertRedirect('/superadmin/dashboard');
        $this->assertAuthenticated();
    }

    private function ensureTestUser(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => self::TEST_EMAIL],
            [
                'usergroup_id' => 1,
                'school_id' => null,
                'name' => 'QA Local User',
                'password' => Hash::make(self::TEST_PASSWORD),
                'status' => 'active',
                'email_verified' => 1,
                'email_verified_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
