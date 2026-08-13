<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserDisplayNameTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Display Name Test School', 'email' => 'display@test.sch.ug',
            'phone' => '0700000002', 'slug' => 'display-name-test-school', 'status' => 1,
        ]);
    }

    public function test_uses_profile_first_and_last_name_natural_order(): void
    {
        $user = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'ESTHER ISHIMME',
        ]);

        Userprofile::factory()->create([
            'usergroup_id' => 6,
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'firstname' => 'Esther',
            'lastname' => 'Ishimme',
        ]);

        $this->assertSame('ESTHER ISHIMME', $user->displayName);
    }

    public function test_strips_numeric_suffix_from_profile_names(): void
    {
        $user = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'MARY POLITE',
        ]);

        Userprofile::factory()->create([
            'usergroup_id' => 6,
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'firstname' => 'MARY POLITE33453',
            'lastname' => 'AKAMPA-2',
        ]);

        $this->assertSame('MARY POLITE AKAMPA', $user->displayName);
    }

    public function test_falls_back_to_name_without_profile(): void
    {
        $user = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'nuwagira darius5373',
        ]);

        $this->assertSame('NUWAGIRA DARIUS', $user->displayName);
    }

    public function test_single_token_name_is_returned_stripped(): void
    {
        $user = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'TOSHI42',
        ]);

        $this->assertSame('TOSHI', $user->displayName);
    }

    public function test_empty_name_returns_empty_string(): void
    {
        $user = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => '',
        ]);

        $this->assertSame('', $user->displayName);
    }

    public function test_does_not_break_fullname_attribute(): void
    {
        $user = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'ESTHER ISHIMME',
        ]);

        Userprofile::factory()->create([
            'usergroup_id' => 6,
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'firstname' => 'Esther',
            'lastname' => 'Ishimme',
        ]);

        $this->assertSame('ESTHER ISHIMME', $user->FullName);
        $this->assertSame('ESTHER ISHIMME', $user->displayName);
    }
}