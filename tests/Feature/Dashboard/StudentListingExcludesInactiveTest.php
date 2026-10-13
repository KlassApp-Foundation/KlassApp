<?php

namespace Tests\Feature\Dashboard;

use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The students list All chip shows active and inactive people and
 * still excludes exit. Exit stays out through whereIn(['active','inactive']),
 * never a negative status filter. The Active chip and ?status=inactive
 * stay positive-equality filters.
 */
class StudentListingExcludesInactiveTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => 'Student Listing Exclusion School',
            'email' => 'student-listing-excl@test.sch.ug',
            'phone' => '0700000999',
            'slug' => 'student-listing-exclusion-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Listing Admin',
            'email' => 'admin@student-listing-excl.sch.ug',
            'password' => Hash::make('secret123'),
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Listing',
            'lastname' => 'Admin',
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
    }

    private function makeStudent(string $status, string $firstname): User
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => $firstname . ' Student',
            'email' => strtolower(str_replace(' ', '.', $firstname)) . '.student@student-listing-excl.sch.ug',
            'password' => Hash::make('secret123'),
        ]);
        $user->forceFill(['status' => $status])->save();

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'usergroup_id' => 6,
            'firstname' => $firstname,
            'lastname' => 'Student',
        ]);

        return $user;
    }

    public function test_default_all_chip_includes_inactive_and_excludes_exit(): void
    {
        $activeA = $this->makeStudent('active', 'ActiveAlpha');
        $activeB = $this->makeStudent('active', 'ActiveBeta');
        $inactive = $this->makeStudent('inactive', 'InactiveJunk');
        $exit = $this->makeStudent('exit', 'Exited');

        $response = $this->actingAs($this->admin)->get('/admin/students');

        $response->assertOk();
        $response->assertSee($activeA->userprofile->firstname, false);
        $response->assertSee($activeB->userprofile->firstname, false);
        $response->assertSee($inactive->userprofile->firstname, false);
        $response->assertDontSee($exit->userprofile->firstname, false);
    }

    public function test_active_chip_excludes_inactive_and_exit(): void
    {
        $active = $this->makeStudent('active', 'ActiveOnly');
        $inactive = $this->makeStudent('inactive', 'InactiveHidden');
        $exit = $this->makeStudent('exit', 'ExitedHidden');

        $response = $this->actingAs($this->admin)->get('/admin/students?chip=active');

        $response->assertOk();
        $response->assertSee($active->userprofile->firstname, false);
        $response->assertDontSee($inactive->userprofile->firstname, false);
        $response->assertDontSee($exit->userprofile->firstname, false);
    }

    public function test_status_inactive_filter_still_exposes_junk_for_audit(): void
    {
        $active = $this->makeStudent('active', 'ActiveAudit');
        $inactive = $this->makeStudent('inactive', 'InactiveJunk');

        $response = $this->actingAs($this->admin)->get('/admin/students?status=inactive');

        $response->assertOk();
        $response->assertSee($inactive->userprofile->firstname, false);
        $response->assertDontSee($active->userprofile->firstname, false);
    }
}