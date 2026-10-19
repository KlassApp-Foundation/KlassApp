<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersSiteAdminTableSeeder extends Seeder
{
    public function run(): void
    {
        $countryId = Country::where('name', 'Uganda')->value('id');
        $cityId = City::where('name', 'Kabale')->value('id');

        $admins = [
            // =============== team admins =================
            [
                'name'       => 'elicom',
                'email'      => env('TEAM_ADMIN_EMAIL', 'team.admin@klassapp.test'),
                'mobile_no'  => env('TEAM_ADMIN_MOBILE_NO', '+256700000000'),
                'firstname'  => 'Mugisha',
                'lastname'   => 'Elijah',
            ],

            [
                'name'       => 'mucu',
                'email'      => env('SITE_OWNER_EMAIL', 'owner@klassapp.test'),
                'mobile_no'  => env('SITE_OWNER_MOBILE_NO', '+256700000000'),
                'firstname'  => 'Mucunguzi',
                'lastname'   => 'Moses',
            ],
        ];

        foreach ($admins as $admin) {

            $user = User::where('email', $admin['email'])->first();

            if ($user === null) {
                $plain = Str::password(20);

                $user = new User([
                    'name'         => $admin['name'],
                    'email'        => $admin['email'],
                    'password'     => Hash::make($plain),
                    'mobile_no'    => $admin['mobile_no'],
                    'usergroup_id' => 1,
                    'is_reset'     => 1,
                    'email_verified' => 1,
                    'email_verified_at' => now(),
                ]);
                $attributes = ['status' => 'active'];
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_activated')) {
                    $attributes['is_activated'] = 1;
                }
                $user->forceFill($attributes)->save();

                // Never print a usable password outside local/test. Production
                // platform admins are created with platform:create-admin.
                if (app()->environment(['local', 'testing'])) {
                    $this->command->warn("Created {$admin['email']} with one-time password: {$plain}");
                } else {
                    $this->command->warn("Created {$admin['email']} without a printed password; use the platform admin command or a password reset.");
                }
            } else {
                $user->fill([
                    'name'         => $admin['name'],
                    'mobile_no'    => $admin['mobile_no'],
                    'usergroup_id' => 1,
                ])->save();
            }

            Userprofile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'usergroup_id' => 1,
                    'firstname'    => $admin['firstname'],
                    'lastname'     => $admin['lastname'],
                    'profession'   => 'admin',
                    'address'      => 'Plot 15, Nakasero Road, Kampala, Uganda',
                    'country_id'   => $countryId,
                    'city_id'      => $cityId,
                    'pincode'      => null,
                ]
            );
        }

        $this->command->info('Site administrators seeded successfully!');
    }
}