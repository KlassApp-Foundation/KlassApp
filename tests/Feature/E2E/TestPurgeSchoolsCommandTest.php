<?php

namespace Tests\Feature\E2E;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * test:purge-schools removes E2E test schools ("E2E ...", is_test) and their
 * dependents; dry run by default, --force to execute, refuses anything that is
 * not an E2E test school, idempotent.
 */
class TestPurgeSchoolsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // MySQL enforces foreign keys; sqlite does not by default. Turn them
        // on so a wrong delete order fails here instead of on staging.
        \DB::statement('PRAGMA foreign_keys = ON');
    }

    private function makeSchool(string $name, bool $isTest, bool $isDemo = false): School
    {
        $school = School::create([
            'name' => $name,
            'slug' => str()->slug($name),
            'email' => str()->slug($name) . '@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);

        $flags = [];
        if ($isTest) {
            $flags['is_test'] = 1;
        }
        if ($isDemo) {
            $flags['is_demo'] = 1;
        }
        if ($flags) {
            $school->forceFill($flags)->save();
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
        // Subjects reference sections; the purge must delete subjects first.
        Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Purge Subject',
            'status' => 1,
        ]);
    }

    public function test_dry_run_prints_the_inventory_and_deletes_nothing(): void
    {
        $school = $this->makeSchool('E2E Primary Manual 2026-09-30', true);
        $user = $this->makeUser($school);
        $this->makeSchoolScopedRows($school);

        $this->artisan('test:purge-schools', ['--school' => [$school->id]])
            ->expectsOutputToContain("school {$school->id}")
            ->expectsOutputToContain("user id={$user->id}")
            ->expectsOutputToContain('subjects: 1 row(s)')
            ->expectsOutputToContain('sections: 1 row(s)')
            ->expectsOutputToContain('Dry run — nothing was deleted.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('schools', ['id' => $school->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('sections', ['school_id' => $school->id]);
    }

    public function test_force_deletes_only_the_e2e_school_and_its_dependents(): void
    {
        $e2e = $this->makeSchool('E2E O-Level Toshi 2026-09-30', true);
        $e2eUser = $this->makeUser($e2e);
        $this->makeSchoolScopedRows($e2e);

        $other = $this->makeSchool('Mucu SS', true); // is_test, not E2E-named
        $otherUser = $this->makeUser($other);

        $this->artisan('test:purge-schools', ['--school' => [$e2e->id], '--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('schools', ['id' => $e2e->id]);
        $this->assertDatabaseMissing('users', ['id' => $e2eUser->id]);
        $this->assertDatabaseMissing('userprofiles', ['user_id' => $e2eUser->id]);
        $this->assertDatabaseMissing('sections', ['school_id' => $e2e->id]);
        $this->assertDatabaseMissing('subjects', ['school_id' => $e2e->id]);
        $this->assertDatabaseMissing('standards_link', ['school_id' => $e2e->id]);

        $this->assertDatabaseHas('schools', ['id' => $other->id]);
        $this->assertDatabaseHas('users', ['id' => $otherUser->id]);
    }

    public function test_refuses_a_school_that_is_not_flagged_is_test(): void
    {
        $normal = $this->makeSchool('E2E Wannabe 2026-09-30', false);
        $this->makeUser($normal);

        $this->artisan('test:purge-schools', ['--school' => [$normal->id], '--force' => true])
            ->expectsOutputToContain('is not flagged is_test')
            ->assertExitCode(1);

        $this->assertDatabaseHas('schools', ['id' => $normal->id]);
    }

    public function test_refuses_an_is_test_school_that_is_not_e2e_named(): void
    {
        // The phase-4 roster fixture shape: is_test = 1, but not an E2E school.
        $fixture = $this->makeSchool('Phase 4 Roster Test Fixture', true);
        $this->makeUser($fixture);

        $this->artisan('test:purge-schools', ['--school' => [$fixture->id], '--force' => true])
            ->expectsOutputToContain('not an E2E test school')
            ->assertExitCode(1);

        $this->assertDatabaseHas('schools', ['id' => $fixture->id]);
    }

    public function test_refuses_a_demo_school_and_points_to_the_demo_command(): void
    {
        $demo = $this->makeSchool('E2E Demo Impersonator', true, true);
        $this->makeUser($demo);

        $this->artisan('test:purge-schools', ['--school' => [$demo->id], '--force' => true])
            ->expectsOutputToContain('use demo:purge-schools')
            ->assertExitCode(1);

        $this->assertDatabaseHas('schools', ['id' => $demo->id]);
    }

    public function test_is_idempotent_when_the_school_is_already_gone(): void
    {
        $school = $this->makeSchool('E2E Primary Manual 2026-10-01', true);

        $this->artisan('test:purge-schools', ['--school' => [$school->id], '--force' => true])->assertExitCode(0);
        $this->artisan('test:purge-schools', ['--school' => [$school->id], '--force' => true])
            ->expectsOutputToContain('already removed')
            ->assertExitCode(0);
    }
}
