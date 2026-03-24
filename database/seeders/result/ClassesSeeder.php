<?php

namespace Database\Seeders\result;

use App\Models\Academics\Classes;
use App\Models\School;
use App\Models\Standard;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $schools = School::where("status", 1)->get();
        $standards = Standard::where("status", 1)->get();
        $classes = [
            // Pre-primary (Nursery)
            ['name' => 'Baby Class',     'order' => 1,  'status' => '1'],
            ['name' => 'Middle Class',   'order' => 2,  'status' => '1'],
            ['name' => 'Top Class',      'order' => 3,  'status' => '1'],

            // Primary
            ['name' => 'Primary 1',      'order' => 4,  'status' => '1'],
            ['name' => 'Primary 2',      'order' => 5,  'status' => '1'],
            ['name' => 'Primary 3',      'order' => 6,  'status' => '1'],
            ['name' => 'Primary 4',      'order' => 7,  'status' => '1'],
            ['name' => 'Primary 5',      'order' => 8,  'status' => '1'],
            ['name' => 'Primary 6',      'order' => 9,  'status' => '1'],
            ['name' => 'Primary 7',      'order' => 10, 'status' => '1'],

            // Secondary (O & A level)
            ['name' => 'Senior 1',       'order' => 11, 'status' => '1'],
            ['name' => 'Senior 2',       'order' => 12, 'status' => '1'],
            ['name' => 'Senior 3',       'order' => 13, 'status' => '1'],
            ['name' => 'Senior 4',       'order' => 14, 'status' => '1'],
            ['name' => 'Senior 5',       'order' => 15, 'status' => '1'],
            ['name' => 'Senior 6',       'order' => 16, 'status' => '1'],
        ];

     foreach ($schools as $school) {
    foreach ($standards as $standard) {
        foreach ($classes as $class){
            Classes::create([
                ...$class, 
                "school_id" => $school->id, 
                "standard_id" => $standard->id,
                ]);
                
        }
    }
}


        $this->command->info('Seeded ' . count($standards) . ' national standards.');
    }
}
