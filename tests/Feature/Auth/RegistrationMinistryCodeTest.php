<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Plan;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationMinistryCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Plan::create([
            'cycle' => 1,
            'name' => 'growth',
            'display_name' => 'Growth',
            'amount' => 0,
            'is_active' => 1,
            'order' => 1,
        ]);
    }

    public function test_registration_creates_placeholder_school_without_ministry_code(): void
    {
        // Ministry code is deferred to Toshi onboarding under the minimal SaaS signup.
        $response = $this->post('/register', [
            'name' => 'Test Admin',
            'email' => 'reg-ministry@example.com',
            'phone' => '0701234567',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'termsandcondn' => '1',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/admin/dashboard');

        $school = School::where('email', 'reg-ministry@example.com')->first();
        $this->assertNotNull($school, 'School was not created');
        $this->assertNull($school->ministry_code);
        $this->assertSame("Test's School", $school->name);
    }
}
