<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Support\Facades\DB;

class UsersSiteAdminTableSeeder extends Seeder
{
    public function run(): void
    {
        // Step 1: Try to find existing siteadmin by name (since name is unique)
        $siteAdmin = User::where('name', 'siteadmin')->first();

        if (! $siteAdmin) {
            // Only create if no user with this name exists
            $siteAdmin = User::create([
                'name'         => 'siteadmin',
                'email'        => 'siteadmin@gmail.com',
                'password'     => bcrypt('password'), // CHANGE THIS!
                'mobile_no'    => '+256781490899',
                'usergroup_id' => 1,
            ]);
        }

        // Step 2: Safe profile creation (won't duplicate if user_id already has profile)
        Userprofile::firstOrCreate(
            ['user_id' => $siteAdmin->id],
            [
                'usergroup_id'  => 1,
                'firstname'     => 'Site',
                'lastname'      => 'Admin',
                'profession'    => 'Super Administrator',
                'address'       => 'Plot 15, Nakasero Road, Kampala, Uganda',
                'country_id'    => DB::table('countries')->where('name', 'Uganda')->value('id'),
                'state_id'      => DB::table('states')->where('name', 'Central Region')->value('id'),
                'city_id'       => DB::table('cities')->where('name', 'Kampala')->value('id'),
                'pincode'       => null,
            ]
        );

        $this->command->info('Site admin user & profile seeded/ensured successfully.');
    }
}