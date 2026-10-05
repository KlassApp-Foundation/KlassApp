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
            // An academic row needs a year; without one there is nothing safe to create.
            $yearId = \App\Models\AcademicYear::where('school_id', $student->school_id)->orderByDesc('id')->value('id');
            if ($yearId === null) {
                continue;
            }

            // KLS number: reuse an existing one, otherwise assign through the generator.
            $klsNumber = \App\Services\StudentIdGeneratorService::ensureForStudent($student);

            // Only create if no record exists for this user
            StudentAcademic::firstOrCreate(
                [
                    'user_id'   => $student->id,
                    'school_id' => $student->school_id,
                ],
                [
                    'academic_year_id' => $yearId,
                    'klassapp_student_id' => $klsNumber,
                ]
            );

            $createdCount++;
        }

        $this->command->info("Processed {$students->count()} students. Created/ensured {$createdCount} student academic records.");
    }
}