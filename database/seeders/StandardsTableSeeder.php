<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Standard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StandardsTableSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::where("status", 1)->get();
        $standards = [
            // Phases
            ['name' => 'Nursery',       'order' => 1,  'status' => '1'],
            ['name' => 'Primary',       'order' => 2,  'status' => '1'],
            ['name' => 'Secondary',     'order' => 3,  'status' => '1'],
        ];

     foreach ($schools as $school) {
    foreach ($standards as $data) {
        Standard::create( [...$data, "school_id" => $school->id] );
    }
}


        $this->command->info('Seeded ' . count($standards) . ' national standards.');
    }
}