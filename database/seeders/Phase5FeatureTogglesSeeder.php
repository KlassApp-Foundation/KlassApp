<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Phase5FeatureTogglesSeeder extends Seeder
{
    public function run(): void
    {
        $toggles = [
            ['feature' => 'roster', 'enabled' => true],
            ['feature' => 'report_generation', 'enabled' => true],
            ['feature' => 'bulk_attendance', 'enabled' => false],
        ];

        $now = now();

        foreach (School::pluck('id') as $schoolId) {
            foreach ($toggles as $toggle) {
                DB::table('school_feature_toggles')->updateOrInsert(
                    ['school_id' => $schoolId, 'feature' => $toggle['feature']],
                    ['enabled' => $toggle['enabled'], 'updated_at' => $now, 'created_at' => $now],
                );
            }
        }

        $schoolCount = School::count();
        $this->command?->info("Feature toggles seeded for {$schoolCount} school(s).");
    }
}
