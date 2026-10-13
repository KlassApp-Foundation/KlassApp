<?php

namespace Tests\Feature\Demo;

use App\Models\School;
use App\Models\User;
use App\Support\DemoSeedManifest;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Database\Seeders\DemoSeniorSchoolSeeder;
use Database\Seeders\UsergroupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Demo Junior has one school admin. Refresh must not recreate the legacy
 * address or change the password hash. Foreign keys are enforced.
 */
class DemoSetAdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE = 'FixtureOnly8';

    private const LEGACY = 'admin@junior.demo.klassapp.test';

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

    public function test_refresh_keeps_one_admin_on_the_new_email_and_a_stable_hash(): void
    {
        $school = $this->seedJunior();
        $legacy = self::LEGACY;

        $admins = User::where('school_id', $school->id)->where('usergroup_id', 3)->where('status', 'active')->get();
        $this->assertCount(1, $admins);
        $this->assertSame(DemoJuniorSchoolSeeder::ADMIN_EMAIL, $admins->first()->email);
        $this->assertNull(User::where('email', $legacy)->first());

        $hash = $admins->first()->password;
        $manifest = DemoSeedManifest::read($school->id);
        $this->assertSame(DemoJuniorSchoolSeeder::ADMIN_EMAIL, $manifest['admin_email'] ?? null);

        $this->assertSame(0, Artisan::call('demo:refresh', ['school' => $school->id]));
        $this->assertSame(0, Artisan::call('demo:refresh', ['school' => $school->id]));

        $after = User::where('school_id', $school->id)->where('usergroup_id', 3)->where('status', 'active')->get();
        $this->assertCount(1, $after);
        $this->assertSame(DemoJuniorSchoolSeeder::ADMIN_EMAIL, $after->first()->email);
        $this->assertSame($hash, $after->first()->password);
        $this->assertNull(User::where('email', $legacy)->first());
        $this->assertSame(0, User::where('school_id', $school->id)->where('usergroup_id', 3)->count() - 1);
    }

    public function test_a_legacy_admin_email_is_renamed_without_a_new_password_or_a_second_admin(): void
    {
        $school = $this->seedJunior();
        $admin = User::where('school_id', $school->id)->where('usergroup_id', 3)->where('status', 'active')->firstOrFail();
        $admin->forceFill(['email' => self::LEGACY])->save();
        $hash = $admin->password;

        $this->assertSame(0, Artisan::call('demo:refresh', ['school' => $school->id]));

        $admins = User::where('school_id', $school->id)->where('usergroup_id', 3)->get();
        $this->assertCount(1, $admins);
        $this->assertSame(DemoJuniorSchoolSeeder::ADMIN_EMAIL, $admins->first()->email);
        $this->assertSame($hash, $admins->first()->password);
        $this->assertNull(User::where('email', self::LEGACY)->first());
    }

    public function test_refuses_a_non_demo_school_and_any_other_school(): void
    {
        $junior = $this->seedJunior();
        $this->seed(DemoSeniorSchoolSeeder::class);
        $senior = School::where('email', 'demo-senior@klassapp.xyz')->firstOrFail();
        $juniorAdmin = User::where('school_id', $junior->id)->where('usergroup_id', 3)->firstOrFail();
        $seniorAdmin = User::where('school_id', $senior->id)->where('usergroup_id', 3)->firstOrFail();
        $juniorHash = $juniorAdmin->password;
        $seniorHash = $seniorAdmin->password;
        $seniorEmail = $seniorAdmin->email;

        $plain = School::create([
            'name' => 'Plain School ' . uniqid(),
            'slug' => 'not-demo-' . uniqid(),
            'email' => 'school-' . uniqid() . '@example.com',
            'phone' => '0771234567',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);
        $plain->forceFill(['is_demo' => 0])->save();

        $this->assertSame(1, Artisan::call('demo:set-admin-login', [
            'school' => $plain->id,
            '--password' => self::FIXTURE,
        ]));
        $this->assertStringContainsString('not a demo school', Artisan::output());

        $this->assertSame(1, Artisan::call('demo:set-admin-login', [
            'school' => $senior->id,
            '--password' => self::FIXTURE,
        ]));
        $this->assertStringContainsString('not Demo Junior School', Artisan::output());

        $this->assertSame($juniorHash, $juniorAdmin->fresh()->password);
        $this->assertSame($seniorHash, $seniorAdmin->fresh()->password);
        $this->assertSame($seniorEmail, $seniorAdmin->fresh()->email);
        $this->assertSame('Demo Junior School', $junior->fresh()->name);
        $this->assertSame('Demo Senior School', $senior->fresh()->name);
    }

    public function test_sets_the_login_without_mail_and_without_logging_the_password(): void
    {
        $school = $this->seedJunior();
        $admin = User::where('school_id', $school->id)->where('usergroup_id', 3)->where('status', 'active')->firstOrFail();
        $teacher = User::where('school_id', $school->id)->where('email', 'teacher1@junior.demo.klassapp.test')->firstOrFail();
        $teacherHash = $teacher->password;
        $role = (int) $admin->usergroup_id;
        $schoolId = (int) $admin->school_id;

        $admin->forceFill([
            'email' => self::LEGACY,
            'is_reset' => 1,
            'email_verified' => 0,
            'email_verified_at' => null,
        ])->save();
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

        Mail::fake();
        Notification::fake();
        \Log::info('demo:set-admin-login test marker');

        $exit = Artisan::call('demo:set-admin-login', [
            'school' => $school->id,
            '--password' => self::FIXTURE,
        ]);

        $this->assertSame(0, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('1 school admin updated', $output);
        $this->assertStringNotContainsString(self::FIXTURE, $output);

        $admin->refresh();
        $this->assertSame(DemoJuniorSchoolSeeder::ADMIN_EMAIL, $admin->email);
        $this->assertTrue(Hash::check(self::FIXTURE, $admin->password));
        $this->assertNotSame(self::FIXTURE, $admin->password);
        $this->assertSame('active', $admin->status);
        $this->assertSame(1, (int) $admin->email_verified);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertSame(0, (int) $admin->is_reset);
        $this->assertSame($role, (int) $admin->usergroup_id);
        $this->assertSame($schoolId, (int) $admin->school_id);
        $this->assertSame('active', DB::table('userprofiles')->where('user_id', $admin->id)->value('status'));
        $auth = DB::table('authentications')->where('user_id', $admin->id)->first();
        $this->assertSame(0, (int) $auth->attempts);
        $this->assertNull($auth->locked_until);
        $this->assertSame($teacherHash, $teacher->fresh()->password);
        $this->assertNull(User::where('email', self::LEGACY)->first());

        Mail::assertNothingSent();
        Notification::assertNothingSent();

        foreach ((array) glob(storage_path('logs/*.log')) as $file) {
            $this->assertStringNotContainsString(self::FIXTURE, (string) file_get_contents($file));
        }

        $activityHit = DB::table('activity_log')->where(function ($query) {
            $query->where('description', 'like', '%' . self::FIXTURE . '%')
                ->orWhere('properties', 'like', '%' . self::FIXTURE . '%');
        })->count();
        $this->assertSame(0, $activityHit);
    }
}
