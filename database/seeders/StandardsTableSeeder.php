<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;

class StandardsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Realistic Ugandan school levels / classes
        // name = what teachers/students call it
        // order = for sorting in dropdowns/reports
        $ugandaStandards = [
            ['name' => 'Baby Class',     'order' => '1'],
            ['name' => 'Middle Class',    'order' => '2'],
            ['name' => 'Top Class',       'order' => '3'],
            ['name' => 'Primary ',  'order' => '4'],
            ['name' => 'Primary 2',  'order' => '5'],
            ['name' => 'Primary 3',  'order' => '6'],
            ['name' => 'Primary 4',  'order' => '7'],
            ['name' => 'Primary 5',  'order' => '8'],
            ['name' => 'Primary 6',  'order' => '9'],
            ['name' => 'Primary 7',  'order' => '10'],
            ['name' => 'Senior 1',   'order' => '11'],
            ['name' => 'Senior 2',   'order' => '12'],
            ['name' => 'Senior 3',   'order' => '13'],
            ['name' => 'Senior 4',   'order' => '14'],
            ['name' => 'Senior 5',   'order' => '15'],
            ['name' => 'Senior 6',   'order' => '16'],
        ];

        $schools = School::where('status', 1)->get();

        if ($schools->isEmpty()) {
            $this->command->warn('No active schools found. Skipping standards seeding.');
            return;
        }

        foreach ($schools as $school) {
            foreach ($ugandaStandards as $index => $level) {
                DB::table('standards')->updateOrInsert(
                    [
                        'school_id' => $school->id,
                        'name'      => $level['name'],
                    ],
                    [
                        'order'      => $level['order'],
                        'status'     => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            $this->command->info("Seeded Ugandan standards for school: {$school->name}");
        }
    }
}