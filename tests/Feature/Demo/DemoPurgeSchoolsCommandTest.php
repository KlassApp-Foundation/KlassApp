<?php

namespace Tests\Feature\Demo;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * demo:purge-schools removes demo schools and their dependents; dry run by
 * default, --force to execute, refuses non-demo schools, idempotent.
 */
class DemoPurgeSchoolsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchool(string $name, bool $demo): School
    {
        $school = School::create([
            'name' => $name,
            'slug' => str()->slug($name),
            'email' => str()->slug($name) . '@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);

        if ($demo) {
            $school->forceFill(['is_demo' => 1])->save();
        }

        return $school->refresh();
    }

    private function makeUser(School $school): User
    {
        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => 'Purge Tester',
            'email' => 'purge-' . $school->id . '@schools.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $profile = new Userprofile;
        $profile->forceFill([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'firstname' => 'Purge',
            'lastname' => 'Tester',
            'status' => 'active',
        ])->save();

        return $user;
    }

    private function makeSchoolScopedRows(School $school): void
    {
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'purge test',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'Purge Class', 'status' => 1]);
        StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
    }

    public function test_dry_run_prints_the_inventory_and_deletes_nothing(): void
    {
        $demo = $this->makeSchool('Purge Demo Dry', true);
        $user = $this->makeUser($demo);
        $this->makeSchoolScopedRows($demo);

        $this->artisan('demo:purge-schools', ['--school' => [$demo->id]])
            ->expectsOutputToContain("school {$demo->id}")
            ->expectsOutputToContain("user id={$user->id}")
            ->expectsOutputToContain('sections: 1 row(s)')
            ->expectsOutputToContain('Dry run — nothing was deleted.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('schools', ['id' => $demo->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('sections', ['school_id' => $demo->id]);
    }

    public function test_force_deletes_only_the_demo_school_and_its_dependents(): void
    {
        $demo = $this->makeSchool('Purge Demo Real', true);
        $demoUser = $this->makeUser($demo);
        $this->makeSchoolScopedRows($demo);

        $normal = $this->makeSchool('Purge Normal', false);
        $normalUser = $this->makeUser($normal);

        $this->artisan('demo:purge-schools', ['--school' => [$demo->id], '--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('schools', ['id' => $demo->id]);
        $this->assertDatabaseMissing('users', ['id' => $demoUser->id]);
        $this->assertDatabaseMissing('userprofiles', ['user_id' => $demoUser->id]);
        $this->assertDatabaseMissing('sections', ['school_id' => $demo->id]);
        $this->assertDatabaseMissing('standards_link', ['school_id' => $demo->id]);

        $this->assertDatabaseHas('schools', ['id' => $normal->id]);
        $this->assertDatabaseHas('users', ['id' => $normalUser->id]);
    }

    public function test_refuses_to_purge_a_school_that_is_not_demo(): void
    {
        $normal = $this->makeSchool('Purge Refuse', false);
        $this->makeUser($normal);

        $this->artisan('demo:purge-schools', ['--school' => [$normal->id], '--force' => true])
            ->expectsOutputToContain('is not flagged is_demo')
            ->assertExitCode(1);

        $this->assertDatabaseHas('schools', ['id' => $normal->id]);
    }

    public function test_is_idempotent_when_the_school_is_already_gone(): void
    {
        $demo = $this->makeSchool('Purge Demo Twice', true);

        $this->artisan('demo:purge-schools', ['--school' => [$demo->id], '--force' => true])->assertExitCode(0);
        $this->artisan('demo:purge-schools', ['--school' => [$demo->id], '--force' => true])
            ->expectsOutputToContain('already removed')
            ->assertExitCode(0);
    }
}
