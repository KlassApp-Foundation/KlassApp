<?php

namespace Tests\Feature\Commands;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class FlagNeverLoggedInTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolA = School::create([
            'name' => 'Flag School A',
            'slug' => 'flag-school-a',
            'email' => 'a@flag.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);

        $this->schoolB = School::create([
            'name' => 'Flag School B',
            'slug' => 'flag-school-b',
            'email' => 'b@flag.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);
    }

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 5,
            'name' => 'Flag Target',
            'email' => 'target' . Str::random(8) . '@flag.test',
            'password' => bcrypt('SecretPass123!'),
            'is_reset' => 0,
            'status' => 'active',
        ], $overrides));
    }

    private function recordLogin(User $user, string $logName = 'login'): void
    {
        DB::table('activity_log')->insert([
            'log_name' => $logName,
            'description' => $logName === 'login' ? 'Logged In' : 'Something else',
            'causer_id' => $user->id,
            'causer_type' => 'App\Models\User',
            'properties' => json_encode(['ip' => '127.0.0.1']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_dry_run_reports_counts_without_flagging(): void
    {
        $one = $this->makeUser();
        $two = $this->makeUser();

        $this->artisan('gego:flag-never-logged-in', ['--school' => $this->schoolA->id])
            ->expectsOutputToContain('Never-logged-in active accounts with is_reset=0: 2')
            ->assertSuccessful();

        $this->assertSame(0, $one->fresh()->is_reset);
        $this->assertSame(0, $two->fresh()->is_reset);
    }

    public function test_apply_flags_matching_accounts_and_skips_all_others(): void
    {
        $target = $this->makeUser();
        $loggedIn = $this->makeUser();
        $this->recordLogin($loggedIn);

        // 'status' is not mass assignable on User — set it directly.
        $inactive = $this->makeUser();
        $inactive->status = 'inactive';
        $inactive->save();

        $alreadyReset = $this->makeUser(['is_reset' => 1]);
        $emptyPassword = $this->makeUser(['password' => '']);
        $otherLog = $this->makeUser();
        $this->recordLogin($otherLog, 'default');

        $this->artisan('gego:flag-never-logged-in', [
            '--apply' => true,
            '--school' => $this->schoolA->id,
        ])
            ->expectsOutputToContain('Flagged 2 account(s)')
            ->assertSuccessful();

        $this->assertSame(1, $target->fresh()->is_reset);
        $this->assertSame(1, $otherLog->fresh()->is_reset, 'a non-login activity row must not count as a login');
        $this->assertSame(0, $loggedIn->fresh()->is_reset, 'a user with a recorded login must not be flagged');
        $this->assertSame(0, $inactive->fresh()->is_reset, 'inactive accounts are out of scope');
        $this->assertSame(1, $alreadyReset->fresh()->is_reset, 'already-flagged accounts are untouched');
        $this->assertSame(0, $emptyPassword->fresh()->is_reset, 'accounts without a usable password must not be flagged');
    }

    public function test_school_option_scopes_the_flagging(): void
    {
        $inA = $this->makeUser();
        $inB = $this->makeUser(['school_id' => $this->schoolB->id]);

        $this->artisan('gego:flag-never-logged-in', [
            '--apply' => true,
            '--school' => $this->schoolA->id,
        ])->assertSuccessful();

        $this->assertSame(1, $inA->fresh()->is_reset);
        $this->assertSame(0, $inB->fresh()->is_reset);
    }

    public function test_apply_is_idempotent_on_second_run(): void
    {
        $this->makeUser();

        $this->artisan('gego:flag-never-logged-in', [
            '--apply' => true,
            '--school' => $this->schoolA->id,
        ])
            ->expectsOutputToContain('Flagged 1 account(s)')
            ->assertSuccessful();

        $this->artisan('gego:flag-never-logged-in', [
            '--apply' => true,
            '--school' => $this->schoolA->id,
        ])
            ->expectsOutputToContain('Never-logged-in active accounts with is_reset=0: 0')
            ->expectsOutputToContain('Nothing to flag.')
            ->assertSuccessful();
    }
}
