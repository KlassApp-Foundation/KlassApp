<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Userprofile;
use Carbon\Carbon;

class UsersSiteAdminTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       $siteAdmin = User::factory()->create([
            'name'         =>   'siteadmin',
            'email'        =>   'siteadmin@gegok12.com',
            'password'     =>   bcrypt('password'),
            'mobile_no'    =>   '1230456789',
            'usergroup_id' =>   "1"
        ]);
        //dd($siteAdmin->id);
        $country=DB::table("countries")->where("name", "Uganda")->first();
       $state=DB::table("states")->where("country_id", $country->id)->first();
       $city=DB::table("cities")->where("name", "Kabale")->where("country_id", $country->id)->first();

    if (!$country || !$state || !$city) {
     throw new \Exception("Country, state or city not found. Check seeders!");
     }

        Userprofile::factory()->create([
            'user_id'       =>  $siteAdmin->id,
            'usergroup_id'  =>  1,
            'firstname'     =>  'John',
            'lastname'      =>  'Doe',
            'profession'    =>  'admin',
            'address'       =>  'Kabale Main Street',
            'country_id'    =>  $country->id,
            'city_id'       =>  $city->id,
            'state_id'      =>  $state->id,
            'pincode'       =>  '625001'
        ]);
    }
}