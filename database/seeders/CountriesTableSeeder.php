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
        ['name'=> 'Uganda', 'short_name'=> 'UG', 'iso_code'=> 'UGA', 'tel_prefix'=> '256', 'status'=> '1'],
        ['name'=> 'Kenya', 'short_name'=> 'KE', 'iso_code'=> 'KEN', 'tel_prefix'=> '254', 'status' => '1'],
        ['name' => 'Tanzania', 'short_name'=> 'TZ', 'iso_code' => 'TZA', 'tel_prefix'  => '255', 'status' => '1'],
        ['name' => 'Rwanda', 'short_name' => 'RW', 'iso_code' => 'RWA', 'tel_prefix' => '250', 'status'=> '1'],
        ['name' => 'Burundi', 'short_name' => 'BI', 'iso_code'=> 'BDI', 'tel_prefix' => '257', 'status'=> '1',],
        ['name'=> 'South Sudan', 'short_name' => 'SS', 'iso_code'=> 'SSD', 'tel_prefix' => '211', 'status'=> '1'],
        ['name' => 'DRC','short_name'=> 'CD','iso_code'  => 'COD','tel_prefix'=> '243','status' => '1',],
        ['name' => 'Nigeria', 'short_name'=> 'NG', 'iso_code'=> 'NGA', 'tel_prefix' => '234', 'status' => '1',],
        ['name'=> 'Egypt', 'short_name'=> 'EG', 'iso_code'  => 'EGY', 'tel_prefix'=> '20', 'status'=> '1'],
        ['name'  => 'Other','short_name'=> 'OT','iso_code'=> 'OTH','tel_prefix'=> '','status'=> '1',]
         ];

        //  loop through all countries
         foreach ($countries as $country){
             DB::table('countries')->updateOrInsert(
                ['name' => $country['name']],
                [
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
