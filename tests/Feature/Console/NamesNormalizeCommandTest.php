<?php

namespace Tests\Feature\Console;

use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T-105: names:normalize. Synthetic people only. */
class NamesNormalizeCommandTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('usergroups')->insertOrIgnore([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->school = School::create([
            'name' => 'Synthetic Names School', 'slug' => 'syn-'.uniqid(), 'email' => uniqid().'@example.test', 'phone' => '+256414000333', 'status' => 1, 'registration_country' => 'Uganda',
        ]);
    }

    private function profile(int $group, ?string $first, ?string $last, ?School $school = null): Userprofile
    {
        $school ??= $this->school;
        $user = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => $group, 'name' => 'handle-'.uniqid()]);

        return Userprofile::create(['user_id' => $user->id, 'school_id' => $school->id, 'usergroup_id' => $group, 'firstname' => $first, 'lastname' => $last, 'status' => 'active']);
    }

    private function names(Userprofile $p): array
    {
        $row = DB::table('userprofiles')->where('id', $p->id)->first();

        return [$row->firstname, $row->lastname];
    }

    public function test_dry_run_is_the_default_and_writes_nothing(): void
    {
        $p = $this->profile(7, 'AGNES KALIISA', '');

        $this->assertSame(0, Artisan::call('names:normalize'));
        $out = Artisan::output();

        $this->assertStringContainsString('DRY RUN', $out);
        $this->assertStringContainsString('AGNES KALIISA | => Agnes | Kaliisa', $out);
        $this->assertSame(['AGNES KALIISA', ''], $this->names($p));
    }

    public function test_apply_splits_and_proper_cases_staff_and_parents(): void
    {
        $parent = $this->profile(7, 'AGNES KALIISA', '');
        $teacher = $this->profile(5, 'RUTH', 'OKORO');
        $fine = $this->profile(5, 'Daniel', 'Okafor');

        Artisan::call('names:normalize', ['--apply' => true]);

        $this->assertSame(['Agnes', 'Kaliisa'], $this->names($parent));
        $this->assertSame(['Ruth', 'Okoro'], $this->names($teacher));
        $this->assertSame(['Daniel', 'Okafor'], $this->names($fine));
    }

    public function test_ambiguous_names_are_reported_and_left_alone(): void
    {
        $one = $this->profile(7, 'AGNES', '');
        $three = $this->profile(7, 'MARY JANE NAKATO', '');

        Artisan::call('names:normalize', ['--apply' => true]);
        $out = Artisan::output();

        $this->assertSame(['AGNES', ''], $this->names($one));
        $this->assertSame(['MARY JANE NAKATO', ''], $this->names($three));
        $this->assertStringContainsString('2 name(s) were ambiguous', $out);
    }

    public function test_students_are_never_touched(): void
    {
        $student = $this->profile(6, 'BRIAN OKELLO', '');

        Artisan::call('names:normalize', ['--apply' => true]);

        $this->assertSame(['BRIAN OKELLO', ''], $this->names($student));
    }

    public function test_the_login_handle_is_never_changed(): void
    {
        $p = $this->profile(7, 'AGNES KALIISA', '');
        $before = DB::table('users')->where('id', $p->user_id)->value('name');

        Artisan::call('names:normalize', ['--apply' => true]);

        $this->assertSame($before, DB::table('users')->where('id', $p->user_id)->value('name'));
    }

    public function test_one_school_only_and_a_second_run_changes_nothing(): void
    {
        $other = School::create(['name' => 'Other Synthetic School', 'slug' => 'syn-'.uniqid(), 'email' => uniqid().'@example.test', 'phone' => '+256414000444', 'status' => 1, 'registration_country' => 'Uganda']);
        $mine = $this->profile(7, 'AGNES KALIISA', '');
        $theirs = $this->profile(7, 'RUTH OKORO', '', $other);

        Artisan::call('names:normalize', ['--apply' => true, '--school' => $this->school->id]);
        $this->assertSame(['Agnes', 'Kaliisa'], $this->names($mine));
        $this->assertSame(['RUTH OKORO', ''], $this->names($theirs));

        Artisan::call('names:normalize', ['--school' => $this->school->id]);
        $this->assertStringContainsString('Would split', Artisan::output());
        $this->assertStringNotContainsString('=> ', explode('Samples', Artisan::output().'Samples')[1] ?? '');
    }

    public function test_apply_and_dry_run_together_are_refused(): void
    {
        $this->assertNotSame(0, Artisan::call('names:normalize', ['--apply' => true, '--dry-run' => true]));
    }
}
