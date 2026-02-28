<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class StatesTableSeeder extends Seeder
{
    public function run()
    {

      $states = [[
      'country_id' => '1', 'name' => 'Test State', 'status' => '1',
      ]];
      $id=1;
      foreach($states as $state){
        DB::table("states")->insert([
        'country_id'=> $id,
        'name'      => $state["name"],
        'status'    => '1',
        'created_at'=> date("Y-m-d H:i:s"),
        'updated_at'=> date("Y-m-d H:i:s"), 
        ]);
      }
    }
    }