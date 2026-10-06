<?php

namespace Tests\Feature\Demo;

use App\Models\School;
use App\Models\User;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Database\Seeders\DemoSeniorSchoolSeeder;
use Database\Seeders\UsergroupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * demo:set-password — sets the shared walkthrough password on demo schools.
 *
 * FK pragma is ON so a wrong write order fails here instead of on staging
 * (MySQL enforces FKs; sqlite does not by default).
 */
class DemoSetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PASSWORD = 'Walkthrough-2026!';

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');

        \App\Services\DemoSchoolCommsGuard::flushCache();
    }

    private function seedJunior(): School
    {
        $this->seed(UsergroupTableSeeder::class);
        $this->seed(DemoJuniorSchoolSeeder::class);

        return School::where('email', 'demo-junior@klassapp.xyz')->firstOrFail();
    }

    private function seedSenior(): School
    {
        $this->seed(UsergroupTableSeeder::class);
        $this->seed(DemoSeniorSchoolSeeder::class);

        return School::where('email', 'demo-senior@klassapp.xyz')->firstOrFail();
    }

    private function account(School $school, string $email): User
    {
        return User::where('school_id', $school->id)->where('email', $email)->firstOrFail();
    }

    /**
     * @param  array<int, int|string>  $schoolIds
     */
    private function runCommand(array $schoolIds, ?string $password = self::NEW_PASSWORD): int
    {
        $args = ['school' => $schoolIds];

        if ($password !== null) {
            $args['--password'] = $password;
        }

        return Artisan::call('demo:set-password', $args);
    }

    public function test_refuses_a_non_demo_school_and_changes_nothing(): void
    {
        $demo = $this->seedJunior();
        $admin = $this->account($demo, 'admin@junior.demo.klassapp.test');
        $before = $admin->password;

        $otherSchool = School::create([
            'name' => 'Not A Demo School',
            'slug' => 'not-a-demo-' . uniqid(),
            'email' => 'school-' . uniqid() . '@example.com',
            'phone' => '0771234567',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);

        $otherAdmin = User::create([
            'email' => 'other-admin@example.com',
            'school_id' => $otherSchool->id,
            'usergroup_id' => 3,
            'name' => 'Other Admin',
            'password' => Hash::make('other-old-password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        $otherBefore = $otherAdmin->password;

        $exit = $this->runCommand([$demo->id, $otherSchool->id]);

        $this->assertSame(1, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('not a demo school', $output);
        $this->assertSame($before, $admin->fresh()->password);
        $this->assertSame($otherBefore, $otherAdmin->fresh()->password);
    }

    public function test_refuses_a_missing_school(): void
    {
        $demo = $this->seedJunior();
        $admin = $this->account($demo, 'admin@junior.demo.klassapp.test');
        $before = $admin->password;

        $exit = $this->runCommand([$demo->id, 999999]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('not found', Artisan::output());
        $this->assertSame($before, $admin->fresh()->password);
    }

    public function test_requires_the_password_option(): void
    {
        $demo = $this->seedJunior();
        $admin = $this->account($demo, 'admin@junior.demo.klassapp.test');
        $before = $admin->password;

        $exit = $this->runCommand([$demo->id], null);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Pass --password', Artisan::output());
        $this->assertSame($before, $admin->fresh()->password);
    }

    public function test_refuses_a_weak_password(): void
    {
        $demo = $this->seedJunior();
        $admin = $this->account($demo, 'admin@junior.demo.klassapp.test');
        $before = $admin->password;

        $exit = $this->runCommand([$demo->id], 'short');
        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Minimum 8', Artisan::output());
        $this->assertSame($before, $admin->fresh()->password);

        $exit = $this->runCommand([$demo->id], '1234567');
        $this->assertSame(1, $exit);
        $this->assertSame($before, $admin->fresh()->password);
    }

    public function test_accepts_a_password_of_exactly_eight_characters(): void
    {
        $demo = $this->seedJunior();
        $admin = $this->account($demo, 'admin@junior.demo.klassapp.test');

        $exit = $this->runCommand([$demo->id], '12345678');

        $this->assertSame(0, $exit);
        $this->assertTrue(Hash::check('12345678', $admin->fresh()->password));
    }

    public function test_sets_shared_password_and_login_readiness_on_targets_only(): void
    {
        $demo = $this->seedJunior();

        // Give the admin a dirty state to prove the clean-login fixes land.
        $admin = $this->account($demo, 'admin@junior.demo.klassapp.test');
        $admin->forceFill(['is_reset' => 1, 'email_verified' => 0, 'email_verified_at' => null])->save();
        DB::table('userprofiles')->where('user_id', $admin->id)->update(['status' => 'inactive']);
        DB::table('authentications')->insert([
            'user_id' => $admin->id,
            'type' => 'register',
            'token' => 'seed-token',
            'ip_address' => '127.0.0.1',
            'expires_on' => now()->addMinutes(15),
            'status' => 1,
            'attempts' => 5,
            'locked_until' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // The inactive leftover admin (as on staging) must never be touched.
        $leftover = User::create([
            'email' => 'e2e.dashv2.demo@example.com',
            'school_id' => $demo->id,
            'usergroup_id' => 3,
            'name' => 'Leftover Admin',
            'password' => Hash::make('leftover-old-password'),
            'email_verified' => 0,
        ]);
        // `status` is not mass assignable — set it directly so the fixture
        // really is the inactive leftover (an active one WOULD be a target).
        $leftover->forceFill(['status' => 'inactive'])->save();
        $this->assertSame('inactive', $leftover->fresh()->status);
        $leftoverBefore = $leftover->fresh()->password;

        $before = User::where('school_id', $demo->id)->pluck('password', 'id');

        $exit = $this->runCommand([$demo->id]);

        $this->assertSame(0, $exit);

        // Every active allowed-role account carries the new password and is
        // reset/unlocked for a clean password login.
        $targets = User::where('school_id', $demo->id)
            ->where('status', 'active')
            ->whereIn('usergroup_id', [2, 3, 4, 5, 7, 8, 10, 11, 12])
            ->get();

        $this->assertGreaterThan(0, $targets->count());

        foreach ($targets as $user) {
            $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->password), "account {$user->id} should carry the new password");
            $this->assertSame(1, (int) $user->email_verified);
            $this->assertSame(0, (int) $user->is_reset);
        }

        // The prepared admin is fully unlocked.
        $admin->refresh();
        $this->assertSame(1, (int) $admin->email_verified);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertSame(0, (int) $admin->is_reset);
        $this->assertSame('active', DB::table('userprofiles')->where('user_id', $admin->id)->value('status'));
        $auth = DB::table('authentications')->where('user_id', $admin->id)->first();
        $this->assertSame(0, (int) $auth->attempts);
        $this->assertNull($auth->locked_until);

        // Students keep their passwords.
        $students = User::where('school_id', $demo->id)->where('usergroup_id', 6)->get();
        $this->assertGreaterThan(0, $students->count());

        foreach ($students as $student) {
            $this->assertSame($before[$student->id], $student->password);
        }

        // The inactive leftover admin keeps everything.
        $leftover->refresh();
        $this->assertSame($leftoverBefore, $leftover->password);
        $this->assertSame(0, (int) $leftover->email_verified);
        $this->assertSame('inactive', $leftover->status);

        // Counts per role only — never the password itself.
        $output = Artisan::output();
        $this->assertStringContainsString('SchoolAdmin: 1', $output);
        $this->assertStringContainsString('Teacher: 10', $output);
        $this->assertStringContainsString('Parent: 12', $output);
        $this->assertStringContainsString('Librarian: 1', $output);
        $this->assertStringContainsString('Accountant: 1', $output);
        $this->assertStringContainsString('Receptionist: 1', $output);
        $this->assertStringNotContainsString(self::NEW_PASSWORD, $output);
    }

    public function test_password_is_stored_hashed(): void
    {
        $demo = $this->seedJunior();
        $admin = $this->account($demo, 'admin@junior.demo.klassapp.test');

        $exit = $this->runCommand([$demo->id]);
        $this->assertSame(0, $exit);

        $raw = DB::table('users')->where('id', $admin->id)->value('password');
        $this->assertNotSame(self::NEW_PASSWORD, $raw);
        $this->assertStringStartsWith('$2y$', $raw);
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $raw));
    }

    public function test_no_password_leak_in_application_log_or_activity_log(): void
    {
        $demo = $this->seedJunior();

        // Make sure an application log exists during the run.
        \Log::info('demo:set-password test marker');

        $exit = $this->runCommand([$demo->id]);
        $this->assertSame(0, $exit);

        foreach ((array) glob(storage_path('logs/*.log')) as $file) {
            $this->assertStringNotContainsString(
                self::NEW_PASSWORD,
                (string) file_get_contents($file),
                "the application log {$file} must not contain the password"
            );
        }

        $activityHit = DB::table('activity_log')->where(function ($query) {
            $query->where('description', 'like', '%' . self::NEW_PASSWORD . '%')
                ->orWhere('properties', 'like', '%' . self::NEW_PASSWORD . '%');
        })->count();

        $this->assertSame(0, $activityHit, 'the activity log must not contain the password');
        $this->assertStringNotContainsString(self::NEW_PASSWORD, Artisan::output());
    }

    public function test_sends_no_mail_or_notifications(): void
    {
        $demo = $this->seedJunior();

        Mail::fake();
        Notification::fake();
        Queue::fake();
        Bus::fake();

        $exit = $this->runCommand([$demo->id]);
        $this->assertSame(0, $exit);

        Mail::assertNothingSent();
        Notification::assertNothingSent();
        Queue::assertNothingPushed();
        Bus::assertNothingDispatched();
    }

    public function test_updates_every_named_school_in_one_run(): void
    {
        $junior = $this->seedJunior();
        $senior = $this->seedSenior();

        $exit = $this->runCommand([$junior->id, $senior->id]);
        $this->assertSame(0, $exit);

        $juniorAdmin = $this->account($junior, 'admin@junior.demo.klassapp.test');
        $seniorAdmin = $this->account($senior, 'admin@senior.demo.klassapp.test');

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $juniorAdmin->fresh()->password));
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $seniorAdmin->fresh()->password));

        $output = Artisan::output();
        $this->assertStringContainsString('Demo Junior School', $output);
        $this->assertStringContainsString('Demo Senior School', $output);
    }
}
