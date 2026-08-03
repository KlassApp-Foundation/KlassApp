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
        $response = $this->post('/register', [
            'name'                  => 'John Admin',
            'email'                 => 'admin@testregister.sch.ug',
            'phone'                 => '700123456',
            'termsandcondn'         => '1',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/admin/dashboard?toshi_onboarding=1');

        $admin = User::where('email', 'admin@testregister.sch.ug')->first();
        $this->assertNotNull($admin, 'Admin user must exist after registration');

        $this->assertNotNull($admin->name, 'Admin name must NOT be null after registration');
        $profile = \App\Models\Userprofile::where('user_id', $admin->id)->first();
        $this->assertNotNull($profile, 'Admin profile must exist');
        $this->assertEquals('JOHN ADMIN', $profile->firstname, 'Display name in profile must match (uppercased)');

        $school = School::find($admin->school_id);
        $this->assertNotNull($school);
        $this->assertSame("John's School", $school->name);
        $this->assertNull($school->curriculum);
        $this->assertSame(1, (int) $school->toshi_enabled);
    }

    /** @test */
    public function admin_name_is_not_null_even_without_explicit_plan(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Sarah Admin',
            'email'                 => 'sarah@noplan.sch.ug',
            'phone'                 => '701234567',
            'termsandcondn'         => '1',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/admin/dashboard?toshi_onboarding=1');
        $admin = User::where('email', 'sarah@noplan.sch.ug')->first();
        $this->assertNotNull($admin);
        $this->assertNotNull($admin->name, 'Admin name must not be null');
        $profile = \App\Models\Userprofile::where('user_id', $admin->id)->first();
        $this->assertNotNull($profile, 'Admin profile must exist');
        $this->assertEquals('SARAH ADMIN', $profile->firstname, 'Display name in profile must match (uppercased)');
    }
}
