<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class CountriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
       
       $countries = [
        ['name'=> 'Uganda', 'short_name'=> 'AF', 'iso_code'=> 'AFG', 'tel_prefix'=> '256', 'status'=> '0'],
        ['name'=> 'Kenya', 'short_name'=> 'AL', 'iso_code'=> 'ALB', 'tel_prefix'=> '355', 'status' => '0'],
        ['name' => 'Tanzania', 'short_name'=> 'AR', 'iso_code' => 'ARG', 'tel_prefix'  => '54', 'status' => '0'],
        ['name' => 'Rwanda', 'short_name' => 'AU', 'iso_code' => 'AUS', 'tel_prefix' => '61', 'status'=> '0'],
        ['name' => 'Burundi', 'short_name' => 'CH', 'iso_code'=> 'CHN', 'tel_prefix' => '86', 'status'=> '0',],
        ['name'=> 'South Sudan', 'short_name' => 'IN', 'iso_code'=> 'IND', 'tel_prefix' => '91', 'status'=> '1'],
        ['name' => 'DRC','short_name'=> 'MA','iso_code'  => 'MYS','tel_prefix'=> '60','status' => '0',],
        ['name' => 'Nigeria', 'short_name'=> 'SW', 'iso_code'=> 'CHE', 'tel_prefix' => '41', 'status' => '0',],
        ['name'=> 'Egypt', 'short_name'=> 'EG', 'iso_code'  => 'EGY', 'tel_prefix'=> '20', 'status'=> '0'],
        ['name'  => 'Other','short_name'=> 'OT','iso_code'=> 'OT','tel_prefix'=> '20','status'=> '0',]
         ];

        //  loop through all countries
         foreach ($countries as $country){
             DB::table('countries')->Insert([
            'name'        => $country['name'],
            'short_name'  => $country['short_name'],
            'iso_code'    => $country['iso_code'],
            'tel_prefix'  => $country['tel_prefix'],
            'status'      => $country['status'],
            'created_at'  => date("Y-m-d H:i:s"),
            'updated_at'  => date("Y-m-d H:i:s"),
       ]);
        }

    }
}
