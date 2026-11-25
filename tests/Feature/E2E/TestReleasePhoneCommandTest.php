<?php

namespace Tests\Feature\E2E;

use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * test:release-phone releases a phone number from its test-school ties
 * without deleting the school or its users.
 *
 * The three ties that TestSchoolCommsGuard checks:
 *   (A) whatsapp_users.school_id → is_test school
 *   (B) whatsapp_users.user_id → users.school_id → is_test school
 *   (C) users.mobile_no → users.school_id → is_test school
 *
 * On --force: mobile_no is nulled (not the user), whatsapp_users rows whose
 * tie is to a test school are deleted, and orphan PHONE_SCOPED rows are
 * removed.  Real-school ties are never touched.
 */
class TestReleasePhoneCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeTestSchool(string $name): School
    {
        $school = School::create([
            'name'                 => $name,
            'slug'                 => str()->slug($name),
            'email'                => str()->slug($name) . '@schools.test',
            'registration_country' => 'Uganda',
            'curriculum'           => 'UNEB',
            'status'               => 1,
        ]);
        $school->forceFill(['is_test' => 1])->save();

        return $school->refresh();
    }

    private function makeRealSchool(string $name): School
    {
        return School::create([
            'name'                 => $name,
            'slug'                 => str()->slug($name),
            'email'                => str()->slug($name) . '@schools.test',
            'registration_country' => 'Uganda',
            'curriculum'           => 'UNEB',
            'status'               => 1,
        ]);
    }

    private function makeUser(School $school, string $phone = ''): User
    {
        $user = User::create([
            'school_id'    => $school->id,
            'usergroup_id' => 5,
            'name'         => 'Release Tester',
            'email'        => 'release-' . $school->id . '-' . rand() . '@schools.test',
            'password'     => bcrypt('password'),
            'status'       => 'active',
        ]);

        // Use a raw update to bypass the User::saving hook (PhoneCountry::normalise)
        // so the phone is stored exactly as given, matching TestPurgeSchoolsCommandTest.
        if ($phone !== '') {
            \DB::table('users')->where('id', $user->id)->update(['mobile_no' => $phone]);
        }

        $profile = new Userprofile;
        $profile->forceFill([
            'user_id'      => $user->id,
            'school_id'    => $school->id,
            'usergroup_id' => 5,
            'firstname'    => 'Release',
            'lastname'     => 'Tester',
            'status'       => 'active',
        ])->save();

        return $user->fresh();
    }

    private function insertWhatsAppUser(School $school, User $user, string $phone): int
    {
        return \DB::table('whatsapp_users')->insertGetId([
            'phone'       => $phone,
            'user_id'     => $user->id,
            'school_id'   => $school->id,
            'opted_in'    => 1,
            'verified_at' => now(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    // ------------------------------------------------------------------ //
    // Test 1: dry run prints tie analysis and touches nothing
    // ------------------------------------------------------------------ //

    public function test_dry_run_prints_all_three_ties_and_deletes_nothing(): void
    {
        $lakeview   = $this->makeTestSchool('Lakeview Primary School');
        $hillcrest  = $this->makeTestSchool('Hillcrest Primary Academy');
        $phone      = '+256770100001';

        // Tie C: users.mobile_no (Lakeview admin).
        $admin = $this->makeUser($lakeview, $phone);

        // Tie A + B: whatsapp_users.school_id = Hillcrest, user_id = admin (Lakeview).
        $wauId = $this->insertWhatsAppUser($hillcrest, $admin, $phone);

        // Note: expectsOutputToContain uses Mockery and only one registered expectation
        // can fire per doWrite call.  Pick ONE unique substring per output line.
        //   phone line     → "phone {$phone}"
        //   count line     → "3 tie(s)"
        //   tie-A line     → "Hillcrest Primary Academy" (unique to that line)
        //   tie-B line     → "whatsapp_users.user_id→school_id" (unique tie type)
        //   tie-C line     → "users.mobile_no" (unique tie type)
        //   dry-run footer → "Dry run — nothing was modified."
        $this->artisan('test:release-phone', ['--phone' => [$phone]])
            ->expectsOutputToContain("phone {$phone}")
            ->expectsOutputToContain('3 tie(s)')
            ->expectsOutputToContain('Hillcrest Primary Academy')
            ->expectsOutputToContain('whatsapp_users.user_id→school_id')
            ->expectsOutputToContain('users.mobile_no')
            ->expectsOutputToContain('Dry run — nothing was modified.')
            ->assertExitCode(0);

        // Nothing was changed.
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'mobile_no' => $phone]);
        $this->assertDatabaseHas('whatsapp_users', ['id' => $wauId, 'phone' => $phone]);
        $this->assertDatabaseHas('schools', ['id' => $lakeview->id]);
        $this->assertDatabaseHas('schools', ['id' => $hillcrest->id]);
    }

    // ------------------------------------------------------------------ //
    // Test 2: --force nulls mobile_no and deletes whatsapp_users rows
    // ------------------------------------------------------------------ //

    public function test_force_nulls_mobile_no_and_deletes_whatsapp_users_rows(): void
    {
        $school = $this->makeTestSchool('E2E Release Test');
        $phone  = '+256770100002';
        $user   = $this->makeUser($school, $phone);
        $wauId  = $this->insertWhatsAppUser($school, $user, $phone);

        // Orphan delivery-log row.
        \DB::table('message_delivery_log')->insert([
            'whatsapp_message_id' => 'wamid.release-test',
            'phone'               => $phone,
            'direction'           => 'outbound',
            'status'              => 'sent',
            'content_preview'     => 'release fixture',
        ]);

        $this->artisan('test:release-phone', ['--phone' => [$phone], '--force' => true])
            ->assertExitCode(0);

        // mobile_no is nulled; user still exists; school still exists.
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id, 'mobile_no' => $phone]);
        $this->assertDatabaseHas('schools', ['id' => $school->id]);

        // whatsapp_users row (which had both school_id and user_id ties) is gone.
        $this->assertDatabaseMissing('whatsapp_users', ['id' => $wauId]);

        // Orphan delivery-log row is gone.
        $this->assertDatabaseMissing('message_delivery_log', ['phone' => $phone]);
    }

    // ------------------------------------------------------------------ //
    // Test 3: real-school user with this number → refuses
    // ------------------------------------------------------------------ //

    public function test_refuses_number_owned_by_non_test_school_user(): void
    {
        $real  = $this->makeRealSchool('Mwanjari P S');
        $phone = '+256770100003';
        $user  = $this->makeUser($real, $phone);

        $this->artisan('test:release-phone', ['--phone' => [$phone], '--force' => true])
            ->expectsOutputToContain('non-test school')
            ->assertExitCode(1);

        // Nothing modified.
        $this->assertDatabaseHas('users', ['id' => $user->id, 'mobile_no' => $phone]);
    }

    // ------------------------------------------------------------------ //
    // Test 4: platform admin number → refuses
    // ------------------------------------------------------------------ //

    public function test_refuses_platform_admin_number(): void
    {
        foreach ([1, 2] as $group) {
            $school = $this->makeTestSchool("E2E Platform Admin Group {$group}");
            $phone  = '+25677010000' . (3 + $group);
            $user   = $this->makeUser($school, $phone);
            $user->forceFill(['usergroup_id' => $group])->save();

            $this->artisan('test:release-phone', ['--phone' => [$phone], '--force' => true])
                ->expectsOutputToContain('platform admin')
                ->assertExitCode(1);

            $this->assertDatabaseHas('users', ['id' => $user->id, 'mobile_no' => $phone]);
        }
    }

    // ------------------------------------------------------------------ //
    // Test 5: two separate test-school ties both released by one run
    // ------------------------------------------------------------------ //

    public function test_force_clears_ties_across_two_test_schools(): void
    {
        $schoolA = $this->makeTestSchool('E2E Release School A');
        $schoolB = $this->makeTestSchool('E2E Release School B');
        $phone   = '+256770100006';

        // Tie C: mobile_no on a user at schoolA.
        $userA = $this->makeUser($schoolA, $phone);

        // Tie A: whatsapp_users row whose school_id = schoolB (different user).
        $userB  = $this->makeUser($schoolB);
        $wauId1 = \DB::table('whatsapp_users')->insertGetId([
            'phone'      => $phone,
            'user_id'    => $userB->id,
            'school_id'  => $schoolB->id,
            'opted_in'   => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('test:release-phone', ['--phone' => [$phone], '--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['id' => $userA->id, 'mobile_no' => $phone]);
        $this->assertDatabaseHas('users', ['id' => $userA->id]); // user still exists
        $this->assertDatabaseMissing('whatsapp_users', ['id' => $wauId1]);
    }

    // ------------------------------------------------------------------ //
    // Test 6: number not present anywhere → prints "no ties" and exits 0
    // ------------------------------------------------------------------ //

    public function test_prints_no_ties_and_exits_successfully_when_number_absent(): void
    {
        $this->artisan('test:release-phone', ['--phone' => ['+256770199999']])
            ->expectsOutputToContain('no test-school ties')
            ->assertExitCode(0);
    }

    // ------------------------------------------------------------------ //
    // Test 7: trunk form (0...) and E.164 (+256...) resolve to same ties
    // ------------------------------------------------------------------ //

    public function test_trunk_form_matches_e164_stored_number(): void
    {
        $school   = $this->makeTestSchool('E2E Trunk Form Match');
        $e164     = '+256770100007';
        $trunk    = '0770100007';     // same number, trunk form
        $user     = $this->makeUser($school, $e164); // stored as E.164
        $wauId    = $this->insertWhatsAppUser($school, $user, $e164);

        // Pass the trunk form; it should still find all ties (A, B, C for the
        // same school: whatsapp_users.school_id, user_id→school_id, users.mobile_no).
        $this->artisan('test:release-phone', ['--phone' => [$trunk]])
            ->expectsOutputToContain('3 tie(s)')
            ->expectsOutputToContain('E2E Trunk Form Match')
            ->expectsOutputToContain('Dry run — nothing was modified.')
            ->assertExitCode(0);

        // --force with trunk form should clear everything.
        $this->artisan('test:release-phone', ['--phone' => [$trunk], '--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['id' => $user->id, 'mobile_no' => $e164]);
        $this->assertDatabaseMissing('whatsapp_users', ['id' => $wauId]);
    }
}
