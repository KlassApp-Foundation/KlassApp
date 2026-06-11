<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSiteAdminTableSeeder extends Seeder
{
    public function run(): void
    {
        $countryId = Country::where('name', 'Uganda')->value('id');

        $stateId = State::where('name', 'Western')->value('id');

        $cityId = City::where('name', 'Kabale')->value('id');

        $admins = [
            // ======== site admin ==============
            [
                'name'       => 'siteadmin',
                'email'      => 'siteadmin@gmail.com',
                'mobile_no'  => '+256700000011',
                'firstname'  => 'Site',
                'lastname'   => 'Admin',
            ],

            // =============== super admins =================
            [
                'name'       => 'superadmin',
                'email'      => 'superadmin@gmail.com',
                'mobile_no'  => '+256765275289',
                'firstname'  => 'Super',
                'lastname'   => 'Admin',
            ],

            [
                'name'       => 'elicom',
                'email'      => 'elicomelijah330@gmail.com',
                'mobile_no'  => '+256781490899',
                'firstname'  => 'Mugisha',
                'lastname'   => 'Elijah',
            ],

             [
                'name'       => 'mucu',
                'email'      => 'moemucu@gmail.com',
                'mobile_no'  => '+256781940358',
                'firstname'  => 'Mucunguzi',
                'lastname'   => 'Moses',
            ],
        ];

        foreach ($admins as $admin) {

            $user = User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name'         => $admin['name'],
                    'password'     => Hash::make('password'),
                    'mobile_no'    => $admin['mobile_no'],
                    'usergroup_id' => 1,
                ]
            );

            Userprofile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'usergroup_id' => 1,
                    'firstname'    => $admin['firstname'],
                    'lastname'     => $admin['lastname'],
                    'profession'   => 'Super Administrator',
                    'address'      => 'Plot 15, Nakasero Road, Kampala, Uganda',
                    'country_id'   => $countryId,
                    'state_id'     => $stateId,
                    'city_id'      => $cityId,
                    'pincode'      => null,
                ]
            );
        }

        $this->command->info('Site administrators seeded successfully!');
    }
}