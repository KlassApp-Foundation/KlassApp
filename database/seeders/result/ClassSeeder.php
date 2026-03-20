<?php

namespace Database\Seeders\result;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
         $classes = [
            // Pre-primary (Nursery)
            ['name' => 'Baby Class', "num" => 1 ],
            ['name' => 'Middle Class', "num" => 2],
            ['name' => 'Top Class', "num" => 3 ],

            // Primary
            ['name' => 'Primary 1', "num" => 4 ],
            ['name' => 'Primary 2', "num" => 5 ],
            ['name' => 'Primary 3', "num" => 6 ],
            ['name' => 'Primary 4', "num" => 7 ],
            ['name' => 'Primary 5', "num" => 8 ],
            ['name' => 'Primary 6', "num" => 9 ],
            ['name' => 'Primary 7', "num" => 10 ],

            // Secondary (O & A level)
            ['name' => 'Senior 1', "num" => 11 ],
            ['name' => 'Senior 2', "num" => 12 ],
            ['name' => 'Senior 3', "num" => 13 ],
            ['name' => 'Senior 4', "num" => 14 ],
            ['name' => 'Senior 5', "num" => 15 ],
            ['name' => 'Senior 6', "num" => 16 ],
        ];

        foreach ($classes as $class){
            DB::table("classes")->updateOrInsert(
                ["num"=>$class['num']],
                ["name" => $class['name']]
            );
        }
       
    }
}
