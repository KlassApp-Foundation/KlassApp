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
            ['name' => 'Baby Class',     'position' => 1,  'status' => '1'],
            ['name' => 'Middle Class',   'position' => 2,  'status' => '1'],
            ['name' => 'Top Class',      'position' => 3,  'status' => '1'],
            ['name' => 'Primary 1',      'position' => 4,  'status' => '1'],
            ['name' => 'Primary 2',      'position' => 5,  'status' => '1'],
            ['name' => 'Primary 3',      'position' => 6,  'status' => '1'],
            ['name' => 'Primary 4',      'position' => 7,  'status' => '1'],
            ['name' => 'Primary 5',      'position' => 8,  'status' => '1'],
            ['name' => 'Primary 6',      'position' => 9,  'status' => '1'],
            ['name' => 'Primary 7',      'position' => 10, 'status' => '1'],
            ['name' => 'Senior 1',       'position' => 11, 'status' => '1'],
            ['name' => 'Senior 2',       'position' => 12, 'status' => '1'],
            ['name' => 'Senior 3',       'position' => 13, 'status' => '1'],
            ['name' => 'Senior 4',       'position' => 14, 'status' => '1'],
            ['name' => 'Senior 5',       'position' => 15, 'status' => '1'],
            ['name' => 'Senior 6',       'position' => 16, 'status' => '1'],
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
