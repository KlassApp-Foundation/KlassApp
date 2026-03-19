<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class StatesTableSeeder extends Seeder
{
    public function run()
    {

      $states = [
        ['country' => 'Uganda', 'name' => 'UG', 'status' => '1'],
        ['country' => 'Kenya', 'name' => 'KE', 'status' => '1'],
        ['country' => 'Tanzania', 'name' => 'TZ', 'status' => '1'],
        ['country' => 'Rwanda', 'name' => 'RW', 'status' => '1'],
        ['country' => 'Burundi', 'name' => 'BU', 'status' => '1'],
        ['country' => 'South Sudan', 'name' => 'SS', 'status' => '1'],
        ['country' => 'DRC', 'name' => 'DR', 'status' => '1'],
        ['country' => 'Nigeria', 'name' => 'NG', 'status' => '1'],
        ['country' => 'Egypt', 'name' => 'EG', 'status' => '1'],
        ['country' => 'Other', 'name' => 'OTHER', 'status' => '1']
        ];
      foreach($states as $state){
        $country = DB::table("countries")->where("name", $state['country'])->first();
        DB::table("states")->updateOrInsert(
          ['country_id'=> $country->id,
           'name' => $state["name"],
          ],
          [
        'status'    => $state['status'],
        'created_at'=> date("Y-m-d H:i:s"),
        'updated_at'=> date("Y-m-d H:i:s"), 
        ]);
      }
    }
    }