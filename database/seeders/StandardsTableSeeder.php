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
            ['name' => 'Baby Class',     'order' => '01'],
            ['name' => 'Middle Class',    'order' => '02'],
            ['name' => 'Top Class',       'order' => '03'],
            ['name' => 'Primary 1 (P1)',  'order' => '04'],
            ['name' => 'Primary 2 (P2)',  'order' => '05'],
            ['name' => 'Primary 3 (P3)',  'order' => '06'],
            ['name' => 'Primary 4 (P4)',  'order' => '07'],
            ['name' => 'Primary 5 (P5)',  'order' => '08'],
            ['name' => 'Primary 6 (P6)',  'order' => '09'],
            ['name' => 'Primary 7 (P7)',  'order' => '10'],
            ['name' => 'Senior 1 (S1)',   'order' => '11'],
            ['name' => 'Senior 2 (S2)',   'order' => '12'],
            ['name' => 'Senior 3 (S3)',   'order' => '13'],
            ['name' => 'Senior 4 (S4)',   'order' => '14'],
            ['name' => 'Senior 5 (S5)',   'order' => '15'],
            ['name' => 'Senior 6 (S6)',   'order' => '16'],
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