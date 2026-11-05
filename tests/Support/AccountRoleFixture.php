<?php

namespace Tests\Support;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Demo-only: one school and one active user per role, for the account menu and top bar tests. */
trait AccountRoleFixture
{
    protected School $school;

    /** usergroup id => label used in test names */
    protected const ROLES = [
        1 => 'platform admin', 3 => 'school admin', 4 => 'sub-admin', 5 => 'teacher', 6 => 'student',
        7 => 'parent', 8 => 'librarian', 9 => 'alumni', 10 => 'receptionist', 11 => 'bursar', 12 => 'stock keeper',
    ];

    protected function buildRoleFixture(): void
    {
        $now = now();
        DB::table('usergroups')->upsert(
            array_map(fn ($id, $name) => ['id' => $id, 'name' => $name, 'created_at' => $now, 'updated_at' => $now], array_keys(self::ROLES), array_values(self::ROLES)),
            'id',
        );
        DB::table('settings')->insertOrIgnore([['key' => 'login_status', 'value' => '1']]);
        config(['settings.login_status' => 1]);

        $this->school = School::create([
            'name' => 'Demo Account School', 'slug' => 'demo-account-'.uniqid(), 'email' => uniqid().'@demo.test',
            'phone' => '070'.random_int(1000000, 9999999), 'status' => 1,
        ]);
    }

    protected function userFor(int $usergroupId, string $password = 'old-pass-123'): User
    {
        return User::create([
            'school_id' => $this->school->id, 'usergroup_id' => $usergroupId,
            'name' => 'Demo '.self::ROLES[$usergroupId], 'email' => 'role'.$usergroupId.'-'.uniqid().'@demo.test',
            'password' => Hash::make($password), 'email_verified' => 1, 'status' => 'active', 'is_reset' => 0,
        ]);
    }

    /** @return array<int,int> usergroup ids of every role */
    protected function allRoleIds(): array
    {
        return array_keys(self::ROLES);
    }
}
