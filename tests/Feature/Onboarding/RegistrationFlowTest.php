<?php

namespace Tests\Feature\Onboarding;

use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    /** @test */
    public function admin_name_is_set_on_manual_registration(): void
    {
        // This is the regression test for Finding 1:
        // createSchoolAdmin() was silently dropping the 'name' field
        // by writing to a non-existent 'username' column instead of 'name'.
        $response = $this->post('/register', [
            'school_name'           => 'Test School For Name',
            'name'                  => 'John Admin',
            'email'                 => 'admin@testregister.sch.ug',
            'mobile_no'             => '700123456',
            'country'               => 'Uganda',
            'curriculum'            => 'uneb',
            'termsandcondn'         => '1',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'plan'                  => 'Freemium',
        ]);

        // Registration with email_verified=0 redirects to OTP verification
        $response->assertRedirect('/verifyotp');

        // Assert the admin user was created
        $admin = User::where('email', 'admin@testregister.sch.ug')->first();
        $this->assertNotNull($admin, 'Admin user must exist after registration');

        // users.name is overwritten by UserprofileObserver to a slug (by design).
        // The display name lives in the userprofile, not users.name.
        $this->assertNotNull($admin->name, 'Admin name must NOT be null after registration');
        $profile = \App\Models\Userprofile::where('user_id', $admin->id)->first();
        $this->assertNotNull($profile, 'Admin profile must exist');
        // firstname accessor returns strtoupper; raw DB value is mixed case
        $this->assertEquals('JOHN ADMIN', $profile->firstname, 'Display name in profile must match (uppercased)');
    }

    /** @test */
    public function admin_name_is_not_null_even_without_explicit_plan(): void
    {
        // Registration without specifying a plan should also set the name correctly
        $response = $this->post('/register', [
            'school_name'           => 'No Plan School',
            'name'                  => 'Sarah Admin',
            'email'                 => 'sarah@noplan.sch.ug',
            'mobile_no'             => '701234567',
            'country'               => 'Uganda',
            'curriculum'            => 'uneb',
            'termsandcondn'         => '1',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertSessionHasNoErrors();
        $admin = User::where('email', 'sarah@noplan.sch.ug')->first();
        $this->assertNotNull($admin);
        // users.name is overwritten by UserprofileObserver; display name is in profile
        $this->assertNotNull($admin->name, 'Admin name must not be null');
        $profile = \App\Models\Userprofile::where('user_id', $admin->id)->first();
        $this->assertNotNull($profile, 'Admin profile must exist');
        // firstname accessor returns strtoupper; raw DB value is mixed case
        $this->assertEquals('SARAH ADMIN', $profile->firstname, 'Display name in profile must match (uppercased)');
    }
}
