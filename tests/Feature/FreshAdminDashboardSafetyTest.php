<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FreshAdminDashboardSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_fresh_admin_dashboard_renders_continue_setup_without_500(): void
    {
        $school = School::create([
            'name' => "Grace's School",
            'email' => 'grace-dash@example.com',
            'phone' => '+256705555555',
            'slug' => 'graces-school-dash',
            'status' => 1,
            'toshi_enabled' => 1,
            'curriculum' => null,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Grace Admin',
            'email' => 'grace-dash@example.com',
            'password' => Hash::make('secret123'),
            'email_verified' => 1,
            'mobile_no' => '+256705555555',
        ]);

        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Grace Admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Continue school setup', false);
        // Auto-open script is driven by $setupIncomplete, not ?toshi_onboarding=
        $response->assertSee('toshi-pill', false);
        $response->assertDontSee('ErrorException', false);
    }

    public function test_fresh_admin_hitting_other_admin_route_redirects_to_dashboard_setup(): void
    {
        $school = School::create([
            'name' => "Dan's School",
            'email' => 'dan-dash@example.com',
            'phone' => '+256706666666',
            'slug' => 'dans-school-dash',
            'status' => 1,
            'toshi_enabled' => 1,
            'curriculum' => null,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Dan Admin',
            'email' => 'dan-dash@example.com',
            'password' => Hash::make('secret123'),
            'email_verified' => 1,
            'mobile_no' => '+256706666666',
        ]);

        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Dan Admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/students');

        $response->assertRedirect('/admin/dashboard');
    }
}
