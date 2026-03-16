<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\StudentAcademic;

class StudentAcademicTableSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('usergroup_id', 6)->get(); // students

        if ($students->isEmpty()) {
            $this->command->info('No students found (usergroup_id = 6). Skipping.');
            return;
        }

        $createdCount = 0;

        foreach ($students as $student) {
            // Only create if no record exists for this user
            StudentAcademic::firstOrCreate(
                [
                    'user_id'   => $student->id,
                    'school_id' => $student->school_id,
                ],
                [
                    // Add any other default fields if needed
                    // e.g. 'academic_year_id' => ..., 'status' => 1, etc.
                ]
            );

            $createdCount++;
        }

        $this->command->info("Processed {$students->count()} students. Created/ensured {$createdCount} student academic records.");
    }
}