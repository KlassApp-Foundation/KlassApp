<?php

namespace Database\Seeders;

use App\Models\EmisSchool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class EmisSchoolTableSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('uganda_schools_dataset.json');

        if (!file_exists($path)) {
            $this->command->warn('EMIS dataset not found at ' . $path);
            return;
        }

        $json = json_decode(file_get_contents($path), true);
        $districts = $json['uganda']['districts'] ?? [];

        $count = 0;

        foreach ($districts as $district => $schools) {
            foreach ($schools as $school) {
                EmisSchool::updateOrCreate(
                    ['emis_code' => $school['emis']],
                    [
                        'school_name' => $school['name'],
                        'district' => $district,
                        'raw_data' => $school,
                    ]
                );
                $count++;
            }
        }

        $this->command->info("Imported {$count} EMIS school records.");
    }
}
