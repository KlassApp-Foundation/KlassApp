<?php

namespace Tests\Feature\Console;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/** T-077: phones:to-e164. Synthetic schools and numbers only. */
class PhonesToE164CommandTest extends TestCase
{
    use RefreshDatabase;

    private School $uganda;

    private School $kenya;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('usergroups')->insertOrIgnore([['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()]]);
        $this->uganda = $this->school('Synthetic Uganda School', 'Uganda', '0414 000111');
        $this->kenya = $this->school('Synthetic Kenya School', 'Kenya', '0200 000222');
    }

    private function school(string $name, string $country, string $phone): School
    {
        return School::create([
            'name' => $name, 'slug' => 'syn-'.uniqid(), 'email' => uniqid().'@example.test',
            'phone' => $phone, 'status' => 1, 'registration_country' => $country,
        ]);
    }

    private function person(School $school, ?string $mobile): User
    {
        return User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 7, 'mobile_no' => $mobile]);
    }

    private function mobile(User $user): ?string
    {
        return DB::table('users')->where('id', $user->id)->value('mobile_no');
    }

    public function test_dry_run_is_the_default_and_writes_nothing(): void
    {
        $user = $this->person($this->uganda, '0772 123456');

        $this->assertSame(0, Artisan::call('phones:to-e164', ['--table' => 'users']));
        $output = Artisan::output();

        $this->assertStringContainsString('DRY RUN', $output);
        $this->assertStringContainsString('0772 123456 => +256772123456', $output);
        $this->assertSame('0772 123456', $this->mobile($user));
    }

    public function test_apply_converts_local_spaced_256_and_leaves_e164(): void
    {
        $local = $this->person($this->uganda, '0772123456');
        $spaced = $this->person($this->uganda, '0772 123 457');
        $bare256 = $this->person($this->uganda, '256772123458');
        $already = $this->person($this->uganda, '+256772123459');

        Artisan::call('phones:to-e164', ['--apply' => true, '--table' => 'users']);

        $this->assertSame('+256772123456', $this->mobile($local));
        $this->assertSame('+256772123457', $this->mobile($spaced));
        $this->assertSame('+256772123458', $this->mobile($bare256));
        $this->assertSame('+256772123459', $this->mobile($already));
    }

    public function test_each_school_uses_its_own_country(): void
    {
        $ug = $this->person($this->uganda, '0772123456');
        $ke = $this->person($this->kenya, '0712345678');

        Artisan::call('phones:to-e164', ['--apply' => true, '--table' => 'users']);

        $this->assertSame('+256772123456', $this->mobile($ug));
        $this->assertSame('+254712345678', $this->mobile($ke));
    }

    public function test_unparseable_numbers_are_reported_logged_and_left_alone(): void
    {
        $bad = $this->person($this->uganda, 'call the office');
        $short = $this->person($this->uganda, '0772');
        $good = $this->person($this->uganda, '0772123456');
        Log::spy();

        Artisan::call('phones:to-e164', ['--apply' => true, '--table' => 'users']);
        $output = Artisan::output();

        $this->assertSame('call the office', $this->mobile($bad));
        $this->assertSame('0772', $this->mobile($short));
        $this->assertSame('+256772123456', $this->mobile($good));
        $this->assertStringContainsString('2 could not convert', $output);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message, $context) => $message === 'phones:to-e164 could not convert'
                && $context['table'] === 'users' && $context['id'] === $bad->id && ! isset($context['number']))
            ->once();
    }

    public function test_a_second_run_changes_nothing(): void
    {
        $this->person($this->uganda, '0772123456');
        Artisan::call('phones:to-e164', ['--apply' => true, '--table' => 'users']);

        Artisan::call('phones:to-e164', ['--table' => 'users']);

        $this->assertStringContainsString('0 would change', Artisan::output());
    }

    public function test_the_school_phone_is_converted_with_its_own_country(): void
    {
        Artisan::call('phones:to-e164', ['--apply' => true, '--table' => 'schools']);

        $this->assertSame('+256414000111', DB::table('schools')->where('id', $this->uganda->id)->value('phone'));
        $this->assertSame('+254200000222', DB::table('schools')->where('id', $this->kenya->id)->value('phone'));
    }

    public function test_one_school_only(): void
    {
        $ug = $this->person($this->uganda, '0772123456');
        $ke = $this->person($this->kenya, '0712345678');

        Artisan::call('phones:to-e164', ['--apply' => true, '--table' => 'users', '--school' => $this->kenya->id]);

        $this->assertSame('0772123456', $this->mobile($ug));
        $this->assertSame('+254712345678', $this->mobile($ke));
    }

    public function test_apply_and_dry_run_together_are_refused(): void
    {
        $user = $this->person($this->uganda, '0772123456');

        $this->assertNotSame(0, Artisan::call('phones:to-e164', ['--apply' => true, '--dry-run' => true]));
        $this->assertSame('0772123456', $this->mobile($user));
    }

    public function test_writes_go_in_chunks(): void
    {
        $users = [];
        foreach (range(1, 7) as $i) {
            $users[] = $this->person($this->uganda, '07721234'.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        Artisan::call('phones:to-e164', ['--apply' => true, '--table' => 'users', '--chunk' => 3]);

        foreach ($users as $i => $user) {
            $this->assertSame('+2567721234'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT), $this->mobile($user));
        }
    }
}
