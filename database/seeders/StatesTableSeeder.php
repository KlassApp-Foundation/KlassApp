<?php

namespace Database\Seeders;

// use DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
       $ugandanRegions = [
           ['name' => 'Central'],
           ['name' => 'Eastern'],
           ['name' => 'Northern'],
           ['name' => 'Western'],
        ];

      foreach($states as $state){
        $country = DB::table("countries")->where("name", $state['country'])->first();

        if(!$country){
          continue;
        }

        if ($state['country'] === 'Uganda') {
                // Special handling for Uganda → use regions instead of country code
                foreach ($ugandanRegions as $region) {
                    DB::table('states')->updateOrInsert(
                        [
                            'country_id' => $country->id,
                            'name'       => $region['name'],  
                        ],
                        [
                            'status'     => $state['status'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }else{
        DB::table("states")->updateOrInsert(
          ['country_id'=> $country->id,
           'name' => $state["name"],
          ],
          [
        'status'    => $state['status'],
        'created_at'=> now(),
        'updated_at'=> now(), 
        ]);
        }
      }
    }
    }