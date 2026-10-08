<?php

namespace Tests\Feature\Demo;

use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\DemoSchoolCommsGuard;
use App\Services\OnboardingStepsService;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Database\Seeders\DemoSeniorSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A1: the demo schools must be complete enough that nothing on the dashboard
 * says "Finish school setup", every student has a guardian, and the profile
 * basics (date of birth, gender, phone numbers) are present.
 */
class DemoDataCompletenessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        DemoSchoolCommsGuard::flushCache();
    }

    public static function schoolProvider(): array
    {
        return [
            'junior' => ['demo-junior@klassapp.xyz', DemoJuniorSchoolSeeder::class],
            'senior' => ['demo-senior@klassapp.xyz', DemoSeniorSchoolSeeder::class],
        ];
    }

    private function seedSchool(string $seeder): School
    {
        $this->seed($seeder);

        return School::where('email', 'demo-' . (str_contains($seeder, 'Junior') ? 'junior' : 'senior') . '@klassapp.xyz')->firstOrFail();
    }

    /** @dataProvider schoolProvider */
    public function test_every_student_has_a_date_of_birth_and_a_gender(string $email, string $seeder): void
    {
        $school = $this->seedSchool($seeder);

        $studentIds = User::where('school_id', $school->id)->where('usergroup_id', 6)->ByActive()->pluck('id');
        $this->assertGreaterThan(0, $studentIds->count());

        $profiles = Userprofile::whereIn('user_id', $studentIds)->get(['user_id', 'date_of_birth', 'gender']);
        $this->assertSame($studentIds->count(), $profiles->count(), 'every active student has a profile');

        foreach ($profiles as $profile) {
            $this->assertNotNull($profile->date_of_birth, "student {$profile->user_id} must have a date of birth");
            $this->assertContains($profile->gender, ['female', 'male'], "student {$profile->user_id} must have a gender");
        }
    }

    /** @dataProvider schoolProvider */
    public function test_every_student_is_linked_to_a_parent_and_parents_have_one_to_three_children(string $email, string $seeder): void
    {
        $school = $this->seedSchool($seeder);

        $studentIds = User::where('school_id', $school->id)->where('usergroup_id', 6)->ByActive()->pluck('id');
        $links = StudentParentLink::where('school_id', $school->id)->get();

        $linkedStudents = $links->pluck('student_id')->unique();
        foreach ($studentIds as $studentId) {
            $this->assertTrue($linkedStudents->contains($studentId), "student {$studentId} must have a parent link");
        }

        $childCounts = $links->groupBy('parent_id')->map->count();
        foreach ($childCounts as $parentId => $count) {
            $this->assertGreaterThanOrEqual(1, $count, "parent {$parentId} must have at least one child");
            $this->assertLessThanOrEqual(3, $count, "parent {$parentId} must not have more than three children");
        }

        // School-scoping guard: no demo parent link may point at a student of
        // the other demo school.
        $otherSchool = School::where('id', '!=', $school->id)
            ->whereIn('email', ['demo-junior@klassapp.xyz', 'demo-senior@klassapp.xyz'])
            ->first();
        if ($otherSchool) {
            $otherStudentIds = User::where('school_id', $otherSchool->id)->where('usergroup_id', 6)->pluck('id');
            $this->assertCount(0, $links->whereIn('student_id', $otherStudentIds), 'links must never cross schools');
        }

        $parents = User::where('school_id', $school->id)->where('usergroup_id', 7)->ByActive()->get(['id', 'mobile_no']);
        $this->assertGreaterThanOrEqual(35, $parents->count(), 'about 40 parents expected');
        foreach ($parents as $parent) {
            $this->assertTrue(filled($parent->mobile_no), "parent {$parent->id} must have a fictional phone number");
        }

        $teachers = User::where('school_id', $school->id)->where('usergroup_id', 5)->ByActive()->get(['id', 'mobile_no']);
        $this->assertGreaterThan(0, $teachers->count());
        foreach ($teachers as $teacher) {
            $this->assertTrue(filled($teacher->mobile_no), "teacher {$teacher->id} must have a fictional phone number");
        }
    }

    /** @dataProvider schoolProvider */
    public function test_no_incomplete_setup_steps_and_no_setup_banner_on_the_dashboard(string $email, string $seeder): void
    {
        $school = $this->seedSchool($seeder);
        $admin = User::where('school_id', $school->id)->where('usergroup_id', 3)->firstOrFail();

        $missing = array_map(fn ($step) => $step['key'], OnboardingStepsService::incompleteSteps($school, $admin->id));
        $this->assertSame([], $missing, 'a school with data must not show incomplete setup steps');

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();
        $response->assertDontSee('Finish school setup');
    }

    /** @dataProvider schoolProvider */
    public function test_no_profile_points_at_a_missing_avatar_file(string $email, string $seeder): void
    {
        $school = $this->seedSchool($seeder);

        $profiles = Userprofile::where('school_id', $school->id)->whereNotNull('avatar')->get(['user_id', 'avatar']);

        $broken = $profiles->filter(function ($profile) {
            $candidates = [
                public_path($profile->avatar),
                public_path('storage/' . $profile->avatar),
                storage_path('app/public/' . $profile->avatar),
            ];

            return ! collect($candidates)->contains(fn ($path) => file_exists($path));
        });

        $this->assertCount(0, $broken, 'no profile may point at a missing avatar file: ' . $broken->pluck('avatar')->implode(', '));
    }
}
