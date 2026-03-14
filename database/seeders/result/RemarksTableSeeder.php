<?php

namespace Database\Seeders\result;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;

class RemarksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Realistic teacher feedback comments for Ugandan schools (demo variety)
        // These are the kinds of things teachers write on report cards/assignments
        $remarks = [
            'Excellent performance! Keep it up.',
            'Good effort. Needs to improve concentration.',
            'Very hardworking student. Well done!',
            'Fair work. More practice required in this topic.',
            'Outstanding! Top of the class.',
            'Shows great potential. Needs to complete homework regularly.',
            'Satisfactory. Pay more attention in class.',
            'Poor effort. Must work harder next term.',
            'Creative and participatory. Excellent in group work.',
            'Consistent improvement noted. Proud of you!',
            'Disruptive at times. Needs better discipline.',
            'Good grasp of concepts. Keep revising.',
        ];

        $schools = School::where('status', 1)->get();

        if ($schools->isEmpty()) {
            $this->command->warn('No active schools found. Skipping comments seeding.');
            return;
        }

        $seededCount = 0;

        foreach ($schools as $school) {
            foreach ($remarks as $remark) {
                DB::table('remarks')->updateOrInsert(
                    [
                        'school_id' => $school->id,
                        'remark'   => $remark,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $seededCount++;
            }

            $this->command->info("Seeded {$seededCount} comments for school: {$school->name}");
        }

        $this->command->info("Total unique comments seeded across all schools: {$seededCount}");
    }
}