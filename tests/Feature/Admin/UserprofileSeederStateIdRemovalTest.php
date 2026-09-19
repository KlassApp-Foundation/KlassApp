<?php

namespace Tests\Feature\Admin;

use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * userprofiles.state_id (and cities.state_id) were dropped — seeders must not write them (#552).
 */
class UserprofileSeederStateIdRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_do_not_assign_userprofile_state_id(): void
    {
        foreach ([
            database_path('seeders/UsersSchoolAdminTableSeeder.php'),
            database_path('seeders/UsersStudentTableSeeder.php'),
            database_path('seeders/UsersTableSeeder.php'),
        ] as $path) {
            $this->assertFileExists($path);
            $source = file_get_contents($path);
            $this->assertDoesNotMatchRegularExpression(
                "/['\"]state_id['\"]\s*=>/",
                $source,
                basename($path).' must not write state_id (column dropped)'
            );
        }
    }

    public function test_userprofile_can_be_created_without_state_id_like_fixed_seeders(): void
    {
        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $ugandaId = DB::table('countries')->insertGetId([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'iso_code' => 'UG',
            'tel_prefix' => '+256',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $school = School::create([
            'name' => 'Seeder State School',
            'email' => 'seeder-state.'.Str::random(6).'@t.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Admin Seeder State School',
            'email' => 'admin.'.Str::random(6).'@t.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        // Same shape as UsersSchoolAdminTableSeeder after state_id removal
        $profile = Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Admin',
            'lastname' => 'Staff',
            'profession' => 'admin',
            'address' => 'School Office, '.$school->name.', Kampala',
            'country_id' => $ugandaId,
            'city_id' => null,
            'pincode' => null,
        ]);

        $this->assertDatabaseHas('userprofiles', [
            'id' => $profile->id,
            'user_id' => $user->id,
            'country_id' => $ugandaId,
        ]);

        if (Schema::hasColumn('userprofiles', 'state_id')) {
            $this->assertNull($profile->fresh()->getAttribute('state_id'));
        } else {
            $this->assertArrayNotHasKey('state_id', $profile->fresh()->getAttributes());
        }
    }
}
