<?php

namespace Tests\Feature\Dashboard;

use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Traits\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression: studentCount (and gender breakdowns) used `status != 'exit'`,
 * which Eloquent's enum ['active','inactive','exit'] means INCLUDES students
 * flagged `status='inactive'`. The Aug 2026 junk-record cleanup
 * (flag_junk_student_records migration) flags 2,165 duplicate/garbage
 * students as inactive in school 104 — with `!= 'exit'` those leaked into the
 * dashboard total (933 active + 317 inactive = 1,250 shown vs 933 expected).
 *
 * All student count queries must use the ByActive() scope (status='active'),
 * which excludes both 'inactive' and 'exit'.
 */
class DashboardStudentCountExcludesInactiveTest extends TestCase
{
    use RefreshDatabase;

    use Dashboard;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => 'Inactive Exclusion School',
            'email' => 'inactive.excl@test.sch.ug',
            'phone' => '0700000777',
            'slug' => 'inactive-exclusion-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Inactive Admin',
            'email' => 'admin@inactive-excl.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Inactive',
            'lastname' => 'Admin',
        ]);

        // Main path needs a current academic year (status=1).
        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
    }

    private function createStudent(
        string $status,
        ?string $gender = null,
        bool $softDeleted = false,
    ): User {
        $user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => fake()->unique()->userName,
            'email' => fake()->unique()->safeEmail,
            'password' => bcrypt('password'),
        ]);
        $user->forceFill(['status' => $status])->save();

        if ($gender !== null) {
            Userprofile::create([
                'school_id' => $this->school->id,
                'user_id' => $user->id,
                'usergroup_id' => 6,
                'firstname' => fake()->firstName,
                'lastname' => fake()->lastName,
                'gender' => $gender,
            ]);
        }

        if ($softDeleted) {
            $user->delete();
        }

        return $user;
    }

    public function test_student_count_excludes_inactive_exit_and_soft_deleted(): void
    {
        // Counted: 3 students (2 with gender profiles, 1 without).
        $this->createStudent('active', 'male');
        $this->createStudent('active', 'female');
        $this->createStudent('active', null);

        // Excluded: junk-flagged inactive, exit, soft-deleted active.
        $this->createStudent('inactive', 'male');
        $this->createStudent('exit', 'female');
        $this->createStudent('active', 'male', softDeleted: true);

        $dashboard = $this->adminDashboard($this->school->id, $this->admin->id);

        $this->assertFalse($dashboard['setupIncomplete']);
        $this->assertSame(3, (int) $dashboard['studentCount']);
        $this->assertSame(1, (int) $dashboard['maleCount']);
        $this->assertSame(1, (int) $dashboard['femaleCount']);
        $this->assertSame(1, (int) $dashboard['unknownCount']);

        // Donut must agree with the headline total.
        $this->assertSame(
            (int) $dashboard['studentCount'],
            (int) $dashboard['maleCount'] + (int) $dashboard['femaleCount'] + (int) $dashboard['unknownCount'],
        );
    }

    public function test_setup_incomplete_path_also_excludes_inactive_students(): void
    {
        // No academic year for this school → the fresh-signup branch runs;
        // it still computes studentCount with the same ByActive filter.
        $fresh = School::create([
            'name' => 'Fresh School No AY',
            'email' => 'fresh.noay@test.sch.ug',
            'phone' => '0700000888',
            'slug' => 'fresh-school-no-ay',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->createStudentInSchool('active', $fresh->id);
        $this->createStudentInSchool('inactive', $fresh->id);
        $this->createStudentInSchool('exit', $fresh->id);

        $dashboard = $this->adminDashboard($fresh->id, $this->admin->id);

        $this->assertTrue($dashboard['setupIncomplete']);
        $this->assertSame(1, (int) $dashboard['studentCount']);
    }

    private function createStudentInSchool(string $status, int $schoolId): void
    {
        $user = User::create([
            'school_id' => $schoolId,
            'usergroup_id' => 6,
            'name' => fake()->unique()->userName,
            'email' => fake()->unique()->safeEmail,
            'password' => bcrypt('password'),
        ]);
        $user->forceFill(['status' => $status])->save();
    }
}