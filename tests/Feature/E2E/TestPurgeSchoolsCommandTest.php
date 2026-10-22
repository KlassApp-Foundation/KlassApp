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

        // MySQL enforces foreign keys; sqlite does not by default, and the
        // FK pragma can only be flipped OUTSIDE a transaction and does not
        // survive reconnection. The bootstrap transaction RefreshDatabase
        // opens is therefore ended inside each FK-sensitive test before
        // turning the pragma on (see the FK ordering test below).
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

    // FK-enforcing regression (2026-10-05): staging purge died on
    // "authentications" — user_id FK with NO ACTION, 32 live rows — because
    // the command deleted users without cleaning user-scoped dependent rows
    // first. sqlite only enforces FKs when the pragma is on AND no
    // transaction is open, so this test ends RefreshDatabase's boot
    // transaction, turns the pragma on, and keeps it on across the command's
    // deletes — the same guarantee staging's MySQL gives.
    public function test_force_purge_deletes_user_authentication_and_grading_rows_in_fk_safe_order(): void
    {
        while (\DB::transactionLevel() > 0) {
            \DB::commit();
        }
        \DB::statement('PRAGMA foreign_keys = ON');
        $this->assertSame(
            1,
            (int) (\DB::select('PRAGMA foreign_keys')[0]->foreign_keys ?? 0),
            'sqlite foreign-key enforcement must be active for this test to mean anything.'
        );

        $e2e = $this->makeSchool('E2E Purge FK Ordering 2026-10-05', true);

        // FK enforcement is live here, so every seeded row must satisfy its
        // own foreign keys: users.usergroup_id -> usergroups first.
        \DB::table('usergroups')->insertOrIgnore([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $user = $this->makeUser($e2e);

        $standard = Standard::create(['school_id' => $e2e->id, 'name' => 'fk-order', 'order' => 1, 'status' => 1]);
        \DB::table('authentications')->insert([
            'user_id' => $user->id,
            'type' => 'login',
            'token' => 'purge-fk-ordering-token',
            'ip_address' => '127.0.0.1',
            'expires_on' => now()->addDay(),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \DB::table('school_grading_systems')->insert([
            'school_id' => $e2e->id,
            'standard_id' => $standard->id,
            'points' => 6,
            'grade' => 'D1',
            'min_score' => 80,
            'max_score' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('test:purge-schools', ['--school' => [$e2e->id], '--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('schools', ['id' => $e2e->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('authentications', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('userprofiles', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('school_grading_systems', ['school_id' => $e2e->id]);
    }

    private function makeEarlyAccessAndInvite(string $email): void
    {
        \DB::table('early_access_requests')->insert([
            'school_name' => 'Purge School',
            'contact_name' => 'Purge Contact',
            'contact_role' => 'Head teacher',
            'email' => $email,
            'phone' => '+256700000000',
            'country' => 'Uganda',
            'approx_students' => 100,
            'consented_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \DB::table('school_invites')->insert([
            'email' => $email,
            'school_name' => 'Purge School',
            'token_hash' => hash('sha256', 'invite-' . $email),
            'expires_at' => now()->addDays(7),
            'claimed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeTeacherAndCoAdminInvite(int $schoolId, string $email): void
    {
        \DB::table('teacher_invites')->insert([
            'school_id' => $schoolId,
            'email' => $email,
            'token_hash' => hash('sha256', 'teacher-' . $email),
            'role' => 'class_teacher',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \DB::table('co_admin_invites')->insert([
            'school_id' => $schoolId,
            'email' => $email,
            'token_hash' => hash('sha256', 'coadmin-' . $email),
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_confirm_name_allows_a_manually_named_is_test_school(): void
    {
        $school = $this->makeSchool('Kabale S S', true);
        $user = $this->makeUser($school);
        $this->makeSchoolScopedRows($school);
        \DB::table('student_id_sequences')->insert([
            'school_id' => $school->id,
            'next_seq' => 4,
        ]);

        $this->artisan('test:purge-schools', ['--school' => [$school->id], '--confirm-name' => ['Kabale S S']])
            ->expectsOutputToContain("user id={$user->id}")
            ->expectsOutputToContain('student_id_sequences: 1 row(s)')
            ->expectsOutputToContain('Dry run — nothing was deleted.')
            ->assertExitCode(0);
        $this->assertDatabaseHas('schools', ['id' => $school->id]);
        $this->assertDatabaseHas('student_id_sequences', ['school_id' => $school->id]);

        $this->artisan('test:purge-schools', ['--school' => [$school->id], '--confirm-name' => ['Kabale S S'], '--force' => true])
            ->assertExitCode(0);
        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('subjects', ['school_id' => $school->id]);
        $this->assertDatabaseMissing('student_id_sequences', ['school_id' => $school->id]);
    }

    public function test_confirm_name_does_not_bypass_the_demo_school_refusal(): void
    {
        $demo = $this->makeSchool('Named Demo School', true, true);
        $user = $this->makeUser($demo);

        $this->artisan('test:purge-schools', [
            '--school' => [$demo->id],
            '--confirm-name' => ['Named Demo School'],
            '--force' => true,
        ])
            ->expectsOutputToContain('use demo:purge-schools')
            ->assertExitCode(1);

        $this->assertDatabaseHas('schools', ['id' => $demo->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_confirm_name_must_match_exactly_and_does_not_bypass_is_test(): void
    {
        $test = $this->makeSchool('Kabale S S', true);
        $this->artisan('test:purge-schools', ['--school' => [$test->id], '--confirm-name' => ['Kabale SS'], '--force' => true])
            ->expectsOutputToContain('not an E2E test school')
            ->assertExitCode(1);
        $this->assertDatabaseHas('schools', ['id' => $test->id]);

        $real = $this->makeSchool('Mazi P S', false);
        $this->artisan('test:purge-schools', ['--school' => [$real->id], '--confirm-name' => ['Mazi P S'], '--force' => true])
            ->expectsOutputToContain('is not flagged is_test')
            ->assertExitCode(1);
        $this->assertDatabaseHas('schools', ['id' => $real->id]);
    }

    public function test_refuses_a_school_with_a_platform_admin_user(): void
    {
        foreach ([1, 2] as $group) {
            $school = $this->makeSchool('E2E Has Platform ' . $group, true);
            $admin = $this->makeUser($school);
            $admin->forceFill(['usergroup_id' => $group])->save();

            $this->artisan('test:purge-schools', ['--school' => [$school->id], '--force' => true])
                ->expectsOutputToContain('platform admin')
                ->assertExitCode(1);
            $this->assertDatabaseHas('schools', ['id' => $school->id]);
            $this->assertDatabaseHas('users', ['id' => $admin->id]);
        }

        $school = $this->makeSchool('E2E Soft Deleted Platform', true);
        $admin = $this->makeUser($school);
        $admin->forceFill(['usergroup_id' => 1])->save();
        $admin->delete();

        $this->artisan('test:purge-schools', ['--school' => [$school->id], '--force' => true])
            ->expectsOutputToContain('platform admin')
            ->assertExitCode(1);
        $this->assertDatabaseHas('schools', ['id' => $school->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_email_option_purges_early_access_requests_and_invites_for_those_emails_only(): void
    {
        $school = $this->makeSchool('E2E Email Purge', true);
        $host = $this->makeSchool('Invite Rows Host', true);
        $user = $this->makeUser($school);
        $this->makeEarlyAccessAndInvite($user->email);
        $this->makeEarlyAccessAndInvite('keep-me@schools.test');
        $this->makeTeacherAndCoAdminInvite($host->id, $user->email);
        $this->makeTeacherAndCoAdminInvite($host->id, 'keep-me@schools.test');

        $this->artisan('test:purge-schools', ['--school' => [$school->id], '--email' => [strtoupper($user->email)]])
            ->expectsOutputToContain('early_access_requests: 1 row(s)')
            ->expectsOutputToContain('school_invites: 1 row(s)')
            ->expectsOutputToContain('teacher_invites: 1 row(s)')
            ->expectsOutputToContain('co_admin_invites: 1 row(s)')
            ->expectsOutputToContain('Dry run — nothing was deleted.')
            ->assertExitCode(0);
        $this->assertDatabaseHas('early_access_requests', ['email' => $user->email]);
        $this->assertDatabaseHas('teacher_invites', ['email' => $user->email]);

        $this->artisan('test:purge-schools', ['--school' => [$school->id], '--email' => [$user->email], '--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('early_access_requests', ['email' => $user->email]);
        $this->assertDatabaseMissing('school_invites', ['email' => $user->email]);
        $this->assertDatabaseMissing('teacher_invites', ['email' => $user->email]);
        $this->assertDatabaseMissing('co_admin_invites', ['email' => $user->email]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('schools', ['id' => $host->id]);
        $this->assertDatabaseHas('early_access_requests', ['email' => 'keep-me@schools.test']);
        $this->assertDatabaseHas('school_invites', ['email' => 'keep-me@schools.test']);
        $this->assertDatabaseHas('teacher_invites', ['email' => 'keep-me@schools.test']);
        $this->assertDatabaseHas('co_admin_invites', ['email' => 'keep-me@schools.test']);
    }

    public function test_email_option_refuses_an_email_owned_by_a_platform_admin(): void
    {
        $target = $this->makeSchool('E2E Email Admin Target', true);
        $home = $this->makeSchool('Platform Admin Home', false);
        $admin = $this->makeUser($home);
        $admin->forceFill(['usergroup_id' => 2])->save();
        $this->makeEarlyAccessAndInvite($admin->email);

        $this->artisan('test:purge-schools', [
            '--school' => [$target->id],
            '--email' => [strtoupper($admin->email)],
            '--force' => true,
        ])
            ->expectsOutputToContain('platform admin')
            ->assertExitCode(1);

        $this->assertDatabaseHas('schools', ['id' => $target->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('early_access_requests', ['email' => $admin->email]);
        $this->assertDatabaseHas('school_invites', ['email' => $admin->email]);
    }

    public function test_email_option_refuses_an_email_owned_by_a_user_outside_the_targeted_schools(): void
    {
        $target = $this->makeSchool('E2E Email Target', true);
        $other = $this->makeSchool('Real Other School', false);
        $otherUser = $this->makeUser($other);
        $this->makeEarlyAccessAndInvite($otherUser->email);

        $this->artisan('test:purge-schools', ['--school' => [$target->id], '--email' => [$otherUser->email], '--force' => true])
            ->expectsOutputToContain('outside the targeted schools')
            ->assertExitCode(1);

        $this->assertDatabaseHas('schools', ['id' => $target->id]);
        $this->assertDatabaseHas('early_access_requests', ['email' => $otherUser->email]);
    }

    private function makeContactRows(School $school, User $user, string $phone, string $email): void
    {
        \DB::table('users')->where('id', $user->id)->update(['mobile_no' => $phone]);

        \DB::table('whatsapp_users')->insert([
            'phone' => $phone,
            'user_id' => $user->id,
            'school_id' => $school->id,
            'opted_in' => 1,
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('early_access_requests')->insert([
            'school_name' => 'Purge School',
            'contact_name' => 'Purge Contact',
            'contact_role' => 'Head teacher',
            'email' => $email,
            'phone' => $phone,
            'country' => 'Uganda',
            'approx_students' => 100,
            'consented_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('message_delivery_log')->insert([
            'whatsapp_message_id' => 'wamid.purge',
            'phone' => $phone,
            'direction' => 'outbound',
            'status' => 'sent',
            'content_preview' => 'purge fixture',
        ]);

        if (\Schema::hasTable('whatsapp_verification_challenges')) {
            \DB::table('whatsapp_verification_challenges')->insert([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'phone' => $phone,
                'phone_digits' => ltrim($phone, '+'),
                'code_hash' => hash('sha256', 'purge-code'),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function test_purging_a_school_frees_its_admin_email_and_phone(): void
    {
        $school = $this->makeSchool('E2E Contact Freedom', true);
        $user = $this->makeUser($school);
        $this->makeContactRows($school, $user, '+256770123456', $user->email);

        $this->artisan('test:purge-schools', ['--school' => [$school->id]])
            ->expectsOutputToContain('early_access_requests: 1 row(s)')
            ->expectsOutputToContain('whatsapp_users: 1 row(s)')
            ->expectsOutputToContain('message_delivery_log: 1 row(s)')
            ->expectsOutputToContain('phones +256770123456 — would purge')
            ->expectsOutputToContain('Dry run — nothing was deleted.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('whatsapp_users', ['phone' => '+256770123456']);

        $this->artisan('test:purge-schools', ['--school' => [$school->id], '--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('early_access_requests', ['email' => $user->email]);
        $this->assertDatabaseMissing('early_access_requests', ['phone' => '+256770123456']);
        $this->assertDatabaseMissing('whatsapp_users', ['phone' => '+256770123456']);
        $this->assertDatabaseMissing('message_delivery_log', ['phone' => '+256770123456']);
        if (\Schema::hasTable('whatsapp_verification_challenges')) {
            $this->assertDatabaseMissing('whatsapp_verification_challenges', ['phone' => '+256770123456']);
        }

        // The same person can sign up again with that address and number.
        $again = School::create([
            'name' => 'Recreated After Purge',
            'slug' => 'recreated-after-purge',
            'email' => 'recreated@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);
        $fresh = User::create([
            'school_id' => $again->id,
            'usergroup_id' => 3,
            'name' => 'Purge Tester',
            'email' => $user->email,
            'mobile_no' => '+256770123456',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        \DB::table('whatsapp_users')->insert([
            'phone' => '+256770123456',
            'user_id' => $fresh->id,
            'school_id' => $again->id,
            'opted_in' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \DB::table('early_access_requests')->insert([
            'school_name' => 'Recreated',
            'contact_name' => 'Purge Tester',
            'contact_role' => 'Head teacher',
            'email' => $user->email,
            'phone' => '+256770123456',
            'country' => 'Uganda',
            'approx_students' => 100,
            'consented_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('users', ['id' => $fresh->id]);
        $this->assertDatabaseHas('whatsapp_users', ['phone' => '+256770123456']);
    }

    public function test_phone_option_refuses_a_number_owned_by_a_user_outside_the_targeted_schools(): void
    {
        $target = $this->makeSchool('E2E Phone Target', true);
        $other = $this->makeSchool('Real Phone Owner', false);
        $otherUser = $this->makeUser($other);
        $otherUser->forceFill(['mobile_no' => '+256771111222'])->save();

        $this->artisan('test:purge-schools', [
            '--school' => [$target->id],
            '--phone' => ['+256771111222'],
            '--force' => true,
        ])
            ->expectsOutputToContain('outside the targeted schools')
            ->assertExitCode(1);

        $this->assertDatabaseHas('schools', ['id' => $target->id]);
        $this->assertDatabaseHas('users', ['id' => $otherUser->id]);
    }

    public function test_phone_option_frees_a_number_left_by_an_already_removed_school(): void
    {
        $host = $this->makeSchool('E2E Leftover Host', true);
        \DB::table('early_access_requests')->insert([
            'school_name' => 'Kabale S S',
            'contact_name' => 'Leftover',
            'contact_role' => 'Head teacher',
            'email' => 'leftover@schools.test',
            'phone' => '+256772222333',
            'country' => 'Uganda',
            'approx_students' => 100,
            'consented_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('test:purge-schools', ['--school' => [$host->id], '--phone' => ['0772222333']])
            ->expectsOutputToContain('early_access_requests: 1 row(s)')
            ->expectsOutputToContain('phones 0772222333 — would purge')
            ->assertExitCode(0);
        $this->assertDatabaseHas('early_access_requests', ['phone' => '+256772222333']);

        $this->artisan('test:purge-schools', ['--school' => [$host->id], '--phone' => ['0772222333'], '--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('early_access_requests', ['phone' => '+256772222333']);
    }

    public function test_soft_deleted_users_are_inventoried_and_removed(): void
    {
        $school = $this->makeSchool('E2E Soft Deleted User', true);
        $user = $this->makeUser($school);
        $user->delete(); // soft delete

        $this->artisan('test:purge-schools', ['--school' => [$school->id]])
            ->expectsOutputToContain("user id={$user->id}")
            ->assertExitCode(0);

        $this->artisan('test:purge-schools', ['--school' => [$school->id], '--force' => true])
            ->assertExitCode(0);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('userprofiles', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
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
