<?php

namespace Tests\Feature\Demo;

use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\WhatsAppUser;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoProvisionCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $password = 'provision-test-secret';

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
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_provision_refuses_without_the_env_flag_and_without_confirm(): void
    {
        putenv('DEMO_PROVISIONING_ENABLED=false');
        $_ENV['DEMO_PROVISIONING_ENABLED'] = 'false';

        $this->assertSame(1, Artisan::call('demo:provision', $this->provisionOptions(['--confirm' => true])));
        $this->assertStringContainsString('DEMO_PROVISIONING_ENABLED', Artisan::output());

        putenv('DEMO_PROVISIONING_ENABLED=true');
        $_ENV['DEMO_PROVISIONING_ENABLED'] = 'true';
        $this->assertSame(1, Artisan::call('demo:provision', $this->provisionOptions()));
        $this->assertStringContainsString('--confirm', Artisan::output());
    }

    public function test_provision_creates_one_demo_school_and_cannot_touch_another(): void
    {
        $other = School::create([
            'name' => 'Real Neighbour School',
            'slug' => 'real-neighbour-school',
            'email' => 'office@real-neighbour.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);
        $other->forceFill(['is_demo' => 0])->save();
        $neighbour = User::create([
            'school_id' => $other->id,
            'usergroup_id' => 3,
            'name' => 'Neighbour Admin',
            'email' => 'admin@real-neighbour.test',
            'password' => Hash::make('neighbour-hash'),
            'status' => 'active',
        ]);
        $neighbourHash = $neighbour->password;
        $neighbourUpdated = $neighbour->updated_at?->toDateTimeString();

        Artisan::call('demo:provision', $this->provisionOptions(['--dry-run' => true]));
        $this->assertStringContainsString('nothing changed', Artisan::output());
        $this->assertSame(0, School::where('is_demo', 1)->count());

        $exit = Artisan::call('demo:provision', $this->provisionOptions(['--confirm' => true]));
        $output = Artisan::output();
        $this->assertSame(0, $exit, $output);
        $this->assertStringNotContainsString($this->password, $output);

        $schools = School::where('is_demo', 1)->get();
        $this->assertCount(1, $schools);
        $school = $schools->first();
        $this->assertSame('Riverside Primary', $school->name);
        $this->assertSame(0, (int) $school->is_test);
        $this->assertGreaterThanOrEqual(60, StudentAcademic::where('school_id', $school->id)->count());

        $parents = User::where('school_id', $school->id)->where('usergroup_id', 7)->get();
        $this->assertTrue($parents->every(fn ($parent) => str_ends_with($parent->email, '@example.com')));
        $opted = WhatsAppUser::where('school_id', $school->id)->whereIn('user_id', $parents->pluck('id'))->where('opted_in', 1)->count();
        $this->assertGreaterThan(0, $parents->count());
        $this->assertEqualsWithDelta(0.6, $opted / $parents->count(), 0.15);

        $admin = User::where('school_id', $school->id)->where('email', 'ada.riverside@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check($this->password, $admin->password));

        $this->assertSame($neighbourHash, $neighbour->fresh()->password);
        $this->assertSame($neighbourUpdated, $neighbour->fresh()->updated_at?->toDateTimeString());
        $this->assertSame(0, (int) $other->fresh()->is_demo);

        $admin->forceFill(['name' => 'Renamed Admin'])->save();
        Artisan::call('demo:provision', $this->provisionOptions(['--confirm' => true]));
        $this->assertSame('Renamed Admin', $admin->fresh()->name);
        $this->assertSame(1, School::where('is_demo', 1)->count());

        $this->assertSame(1, Artisan::call('demo:provision', $this->provisionOptions([
            '--confirm' => true,
            '--admin-email' => 'admin@real-neighbour.test',
            '--name' => 'Another Primary',
        ])));
        $this->assertSame(1, School::where('is_demo', 1)->count());
        $this->assertSame($neighbourHash, $neighbour->fresh()->password);
    }

    public function test_reset_restores_one_demo_school_and_keeps_password_hashes(): void
    {
        Artisan::call('demo:provision', $this->provisionOptions(['--confirm' => true]));
        $school = School::where('name', 'Riverside Primary')->firstOrFail();
        $admin = User::where('school_id', $school->id)->where('usergroup_id', 3)->firstOrFail();
        $hash = $admin->password;

        $other = School::create([
            'name' => 'Untouched School',
            'slug' => 'untouched-school',
            'email' => 'office@untouched.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);
        $otherAdmin = User::create([
            'school_id' => $other->id,
            'usergroup_id' => 3,
            'name' => 'Untouched Admin',
            'email' => 'admin@untouched.test',
            'password' => Hash::make('untouched-hash'),
            'status' => 'active',
        ]);
        $otherHash = $otherAdmin->password;

        Carbon::setTestNow(Carbon::parse('2026-10-17 09:00:00', 'Pacific/Auckland'));
        User::create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Added Later',
            'email' => 'added.later@example.com',
            'password' => Hash::make('added-hash'),
            'status' => 'active',
        ]);

        $this->assertSame(1, Artisan::call('demo:reset', ['school' => $other->id]));
        $this->assertStringContainsString('not a demo school', Artisan::output());
        $this->assertNotNull(User::where('email', 'admin@untouched.test')->first());

        Artisan::call('demo:reset', ['school' => $school->id, '--dry-run' => true]);
        $this->assertStringContainsString('nothing changed', Artisan::output());
        $this->assertNotNull(User::where('email', 'added.later@example.com')->first());

        Artisan::call('demo:reset', ['school' => $school->id]);
        $this->assertNull(User::where('email', 'added.later@example.com')->first());
        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertSame($otherHash, $otherAdmin->fresh()->password);
        $this->assertSame('Untouched Admin', $otherAdmin->fresh()->name);
    }

    private function provisionOptions(array $extra = []): array
    {
        return array_merge([
            'password' => $this->password,
            '--name' => 'Riverside Primary',
            '--city' => 'Entebbe',
            '--admin-name' => 'Ada Nakato',
            '--admin-email' => 'ada.riverside@example.com',
            '--terms' => '2026-02-02,2026-04-30,2026-05-18,2026-08-21,2026-08-24,2026-11-28',
        ], $extra);
    }
}
