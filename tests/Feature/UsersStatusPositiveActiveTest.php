<?php

namespace Tests\Feature;

use App\Livewire\Superadmin\Academics\SchoolList;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\School;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingStepsService;
use App\Traits\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Bug pattern #6: users.status is enum(active/inactive/exit). Negative filters
 * like `!= 'exit'` silently include inactive junk. Every "currently active"
 * query must use positive equality (`= 'active'` / ByActive()).
 */
class UsersStatusPositiveActiveTest extends TestCase
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
            ['id' => 1, 'name' => 'SiteAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'librarian', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'receptionist', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => 'Positive Active School',
            'email' => 'positive.active@test.sch.ug',
            'phone' => '0700000999',
            'slug' => 'positive-active-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = $this->makeUser(3, 'active', 'admin@positive-active.sch.ug');

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Positive',
            'lastname' => 'Admin',
            'status' => 'active',
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

    private function makeUser(int $usergroupId, string $status, ?string $email = null): User
    {
        $user = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => $usergroupId,
            'email' => $email ?? fake()->unique()->safeEmail,
            'status' => 'active',
            'email_verified' => 1,
        ]);
        $user->forceFill(['status' => $status])->save();

        return $user->fresh();
    }

    public function test_onboarding_count_active_students_excludes_inactive(): void
    {
        $this->makeUser(6, 'active');
        $this->makeUser(6, 'active');
        $this->makeUser(6, 'inactive');
        $this->makeUser(6, 'exit');

        $this->assertSame(2, OnboardingStepsService::countActiveStudents($this->school->id));
    }

    public function test_onboarding_students_step_complete_ignores_inactive_only_school(): void
    {
        $this->makeUser(6, 'inactive');
        $this->makeUser(6, 'exit');

        $this->assertFalse(OnboardingStepsService::isStepComplete('students', $this->school));

        $this->makeUser(6, 'active');

        $this->assertTrue(OnboardingStepsService::isStepComplete('students', $this->school));
    }

    public function test_dashboard_teacher_and_nonteaching_counts_exclude_inactive(): void
    {
        $this->makeUser(5, 'active');
        $this->makeUser(5, 'inactive');
        $this->makeUser(5, 'exit');

        $this->makeUser(8, 'active');
        $this->makeUser(11, 'inactive');

        $dashboard = $this->adminDashboard($this->school->id, $this->admin->id);

        $this->assertSame(1, (int) $dashboard['teacherCount']);
        $this->assertSame(1, (int) $dashboard['nonteachingCount']);
    }

    public function test_dashboard_parent_count_requires_active_linked_student(): void
    {
        $activeStudent = $this->makeUser(6, 'active');
        $inactiveStudent = $this->makeUser(6, 'inactive');

        $parentOfActive = $this->makeUser(7, 'active');
        $parentOfInactiveOnly = $this->makeUser(7, 'active');

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $parentOfActive->id,
            'usergroup_id' => 7,
            'firstname' => 'Parent',
            'lastname' => 'ActiveChild',
            'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $parentOfInactiveOnly->id,
            'usergroup_id' => 7,
            'firstname' => 'Parent',
            'lastname' => 'InactiveChild',
            'status' => 'active',
        ]);

        StudentParentLink::create([
            'school_id' => $this->school->id,
            'parent_id' => $parentOfActive->id,
            'student_id' => $activeStudent->id,
            'status' => 1,
        ]);
        StudentParentLink::create([
            'school_id' => $this->school->id,
            'parent_id' => $parentOfInactiveOnly->id,
            'student_id' => $inactiveStudent->id,
            'status' => 1,
        ]);

        $dashboard = $this->adminDashboard($this->school->id, $this->admin->id);

        $this->assertSame(1, (int) $dashboard['parentCount']);
    }

    public function test_superadmin_school_list_teacher_count_excludes_inactive(): void
    {
        $this->makeUser(5, 'active');
        $this->makeUser(5, 'inactive');
        $this->makeUser(5, 'exit');
        $this->makeUser(6, 'active');
        $this->makeUser(6, 'inactive');

        $siteAdmin = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 1,
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Livewire::actingAs($siteAdmin)
            ->test(SchoolList::class)
            ->assertOk()
            ->assertViewHas('schools', function ($schools) {
                $row = $schools->firstWhere('id', $this->school->id);

                return $row
                    && (int) $row->teacher_count === 1
                    && (int) $row->student_count === 1;
            });
    }
}
