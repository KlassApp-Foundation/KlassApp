<?php

namespace Tests\Feature\Demo;

use App\Models\MarksRelease;
use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\Demo\DemoFollowAlong;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Production demo: demo:provision gives the live roster (70 students with
 * marks, BIND codes, an unreleased exam) and a locked-down public demo
 * admin; demo:nightly-reset puts it all back, twice without change.
 */
class DemoProductionProvisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
        config(['app.timezone' => 'Pacific/Auckland']);
        Carbon::setTestNow(Carbon::parse('2026-10-15 09:00:00', 'Pacific/Auckland'));
        putenv('DEMO_PROVISIONING_ENABLED=true');
        $_ENV['DEMO_PROVISIONING_ENABLED'] = 'true';
    }

    protected function tearDown(): void
    {
        putenv('DEMO_PROVISIONING_ENABLED');
        unset($_ENV['DEMO_PROVISIONING_ENABLED']);
        putenv('DEMO_PROVISION_ADMIN_PASSWORD');
        unset($_ENV['DEMO_PROVISION_ADMIN_PASSWORD'], $_SERVER['DEMO_PROVISION_ADMIN_PASSWORD']);
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function provisionArgs(array $extra = []): array
    {
        return array_merge([
            '--name' => 'Riverside Primary',
            '--city' => 'Entebbe',
            '--admin-name' => 'Ada Nakato',
            '--admin-email' => 'ada.riverside@example.com',
            '--terms' => '2026-02-02,2026-04-30,2026-05-18,2026-08-21,2026-08-24,2026-11-28',
            '--confirm' => true,
            '--no-interaction' => true,
        ], $extra);
    }

    public function test_provision_without_a_password_refuses_instead_of_prompting(): void
    {
        $this->assertSame(1, Artisan::call('demo:provision', $this->provisionArgs()));
        $this->assertStringContainsString('an admin password is required', Artisan::output());
        $this->assertSame(0, School::where('is_demo', 1)->count());
    }

    public function test_provision_and_nightly_reset_on_a_fresh_database(): void
    {
        putenv('DEMO_PROVISION_ADMIN_PASSWORD=from-env-secret-123');
        $_ENV['DEMO_PROVISION_ADMIN_PASSWORD'] = 'from-env-secret-123';

        $this->assertSame(0, Artisan::call('demo:provision', $this->provisionArgs()));
        $out = Artisan::output();
        $this->assertStringNotContainsString('from-env-secret-123', $out);

        $school = School::where('name', 'Riverside Primary')->where('is_demo', 1)->firstOrFail();
        $admin = User::where('school_id', $school->id)->where('email', 'ada.riverside@example.com')->firstOrFail();
        $this->assertTrue($admin->isPublicDemo());
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('from-env-secret-123', $admin->password));

        // 70 students with marks in P.1 to P.7, each with a BIND code on /demo.
        $this->assertSame(76, StudentAcademic::where('school_id', $school->id)->count());
        $roster = DemoFollowAlong::roster($school);
        $this->assertGreaterThanOrEqual(70, $roster->count());
        $this->assertSame($roster->count(), $roster->pluck('code')->filter()->unique()->count());
        $this->assertSame(0, MarksRelease::where('school_id', $school->id)->count());

        // A day of demo use.
        $studentId = (int) StudentAcademic::where('school_id', $school->id)->value('user_id');
        WhatsAppUser::create(['phone' => '+256700000901', 'school_id' => $school->id, 'opted_in' => true,
            'demo_student_id' => $studentId, 'demo_linked_at' => now()]);
        MarksRelease::create(['school_id' => $school->id, 'exam_id' => \App\Models\Academics\Exam::where('school_id', $school->id)->value('id'),
            'scope' => 'school', 'scheduled_at' => now(), 'released_at' => now(), 'status' => 'released', 'created_by' => $admin->id, 'timezone' => 'Africa/Kampala']);
        $admin->forceFill(['name' => 'Changed', 'email' => 'changed@example.org'])->save();

        $this->assertSame(0, Artisan::call('demo:nightly-reset'));
        $first = Artisan::output();
        $this->assertStringContainsString('unlinked numbers=1', $first);
        $this->assertStringContainsString('releases removed=1', $first);
        // The reseed may already have put the admin back; either way it must be restored below.
        $admin->refresh();
        $this->assertSame('Ada Nakato', $admin->name);
        $this->assertSame('ada.riverside@example.com', $admin->email);
        $this->assertTrue($admin->isPublicDemo());
        $this->assertSame(0, WhatsAppUser::where('phone', '+256700000901')->count());
        $this->assertSame(0, MarksRelease::where('school_id', $school->id)->count());
        $this->assertSame(76, StudentAcademic::where('school_id', $school->id)->count());

        // Idempotent: a second run changes nothing.
        $this->assertSame(0, Artisan::call('demo:nightly-reset'));
        $this->assertStringContainsString('unlinked numbers=0 bind requests removed=0 releases removed=0 forgotten numbers=0 admin restored=0', Artisan::output());

        // A second provision run is idempotent too.
        $this->assertSame(0, Artisan::call('demo:provision', $this->provisionArgs()));
        $this->assertStringContainsString('school already provisioned; nothing changed', Artisan::output());
        $this->assertSame(1, School::where('is_demo', 1)->count());
    }

    public function test_nightly_reset_never_touches_a_school_that_is_not_demo(): void
    {
        $real = School::create(['name' => 'Real Primary', 'slug' => 'real-primary', 'email' => 'real@example.com', 'registration_country' => 'Uganda', 'curriculum' => 'UNEB', 'status' => 1]);
        WhatsAppUser::create(['phone' => '+256700000902', 'school_id' => $real->id, 'opted_in' => true, 'demo_linked_at' => now()]);
        $this->assertSame(0, Artisan::call('demo:nightly-reset'));
        $this->assertStringContainsString('no provisioned demo school; nothing changed', Artisan::output());
        $this->assertSame(1, WhatsAppUser::where('phone', '+256700000902')->whereNotNull('demo_linked_at')->count());
    }

    public function test_nightly_reset_is_scheduled_at_3am_kampala_behind_its_flag(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains((string) $e->command, 'demo:nightly-reset'));
        $this->assertNotNull($event);
        $this->assertSame('0 3 * * *', $event->expression);
        $this->assertSame('Africa/Kampala', $event->timezone);
        config(['klassapp.demo.nightly_reset_enabled' => false]);
        $this->assertFalse($event->filtersPass(app()));
        config(['klassapp.demo.nightly_reset_enabled' => true]);
        $this->assertTrue($event->filtersPass(app()));
    }

    public function test_is_public_demo_migration_runs_twice(): void
    {
        $migration = require database_path('migrations/2026_10_16_000002_add_is_public_demo_to_users.php');
        $migration->up();
        $migration->up();
        $this->assertTrue(Schema::hasColumn('users', 'is_public_demo'));
        $migration->down();
        $migration->down();
        $this->assertFalse(Schema::hasColumn('users', 'is_public_demo'));
        $migration->up();
        $this->assertTrue(Schema::hasColumn('users', 'is_public_demo'));
    }
}
