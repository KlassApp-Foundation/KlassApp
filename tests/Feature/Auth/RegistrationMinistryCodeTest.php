<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Plan;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationMinistryCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        Plan::create([
            'cycle' => 1,
            'name' => 'growth',
            'display_name' => 'Growth',
            'amount' => 0,
            'is_active' => 1,
            'order' => 1,
        ]);
    }

    public function test_registration_with_ministry_code_saves_code_on_school(): void
    {
        $unique = 'RegTest' . now()->timestamp;

        $response = $this->post('/register', [
            'school_name' => $unique . ' School',
            'name' => 'Test Admin',
            'email' => $unique . '@example.com',
            'mobile_no' => '712345678',
            'country' => 'Uganda',
            'student_size' => '100-500',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'termsandcondn' => '1',
            'ministry_code' => '876543',
        ]);

        $response->assertStatus(302);

        $school = School::where('name', $unique . ' School')->first();
        $this->assertNotNull($school, 'School was not created');
        $this->assertEquals('876543', $school->ministry_code);
    }

    public function test_registration_without_ministry_code_creates_school_with_null_code(): void
    {
        $unique = 'RegTestNoCode' . now()->timestamp;

        $response = $this->post('/register', [
            'school_name' => $unique . ' School',
            'name' => 'Test Admin',
            'email' => $unique . '@example.com',
            'mobile_no' => '712345679',
            'country' => 'Uganda',
            'student_size' => '100-500',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'termsandcondn' => '1',
        ]);

        $response->assertStatus(302);

        $school = School::where('name', $unique . ' School')->first();
        $this->assertNotNull($school, 'School was not created');
        $this->assertNull($school->ministry_code);
    }
}
