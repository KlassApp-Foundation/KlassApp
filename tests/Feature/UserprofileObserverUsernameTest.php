<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserprofileObserverUsernameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_observer_does_not_append_digit_suffix_when_name_already_set(): void
    {
        $school = School::create([
            'name' => 'Observer School',
            'email' => 'obs@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'observer-school-'.Str::random(4),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => 'Sarah',
            'email' => 'sarah@observer.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'usergroup_id' => 5,
            'firstname' => 'Sarah',
            'lastname' => '',
            'status' => 'active',
        ]);

        $user->refresh();

        $this->assertSame('Sarah', $user->name);
        $this->assertDoesNotMatchRegularExpression('/\d/', $user->name);
    }

    public function test_observer_fills_empty_name_from_profile_without_digits(): void
    {
        $school = School::create([
            'name' => 'Empty Name School',
            'email' => 'empty@test.sch.ug',
            'phone' => '0700000098',
            'slug' => 'empty-name-'.Str::random(4),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => 'placeholder',
            'email' => 'emptyname@observer.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        // Simulate a create path that left name blank before profile exists.
        User::where('id', $user->id)->update(['name' => '']);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'usergroup_id' => 5,
            'firstname' => 'Amina',
            'lastname' => 'Okello',
            'status' => 'active',
        ]);

        $user->refresh();

        $this->assertSame('AMINA OKELLO', $user->name);
        $this->assertDoesNotMatchRegularExpression('/\d/', $user->name);
    }
}
