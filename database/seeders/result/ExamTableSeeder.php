<?php

namespace Database\Seeders\result;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\StandardLink;
use App\Models\User;

class ExamTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Common Uganda exam types (realistic for schools)
        $examTypes = [
            'Mid-Term Exam',
            'End-of-Term Exam',
            'Mock Exam',
            'Pre-Mock Exam',
            'Continuous Assessment 1',
            'Continuous Assessment 2',
            'UNEB Practice Test',
        ];

        $schools = School::where('status', 1)->get();

        if ($schools->isEmpty()) {
            $this->command->warn('No active schools found. Skipping exam seeding.');
            return;
        }

        $seededCount = 0;

        foreach ($schools as $school) {
            $academicYears = AcademicYear::where([
                'school_id' => $school->id,
                'status'    => 1,
            ])->get();

            if ($academicYears->isEmpty()) {
                continue;
            }

            // Get all class rooms (standard + section combos) for this school/year
            $classRooms = StandardLink::where('school_id', $school->id)
                ->whereIn('academic_year_id', $academicYears->pluck('id'))
                ->get();

            if ($classRooms->isEmpty()) continue;

            // Get subjects taught in this school
            $subjects = Subject::where('school_id', $school->id)->get();

            if ($subjects->isEmpty()) continue;

            // Optional: limit to a few teachers if needed
            $teachers = User::where('school_id', $school->id)
                ->where('usergroup_id', 5) // teachers
                ->take(10) // don't overdo it
                ->get();

            foreach ($classRooms as $classRoom) {
                foreach ($subjects as $subject) {
                    foreach ($examTypes as $examName) {
                        // Find or create exam record
                        DB::table('exams')->updateOrInsert(
                            [
                                'school_id'        => $school->id,
                                'academic_year_id' => $classRoom->academic_year_id,
                                'standard_id'      => $classRoom->standard_id,
                                'subject_id'       => $subject->id,
                                'name'             => $examName,
                            ],
                            [
                                'section_id'   => $classRoom->section_id, // optional if per section
                                'teacher_id'   => $teachers->isNotEmpty() ? $teachers->random()->id : null,
                                'max_marks'    => rand(50, 100),
                                'weight'       => rand(20, 40), // percentage weight in total
                                'exam_date'    => now()->addDays(rand(10, 90)),
                                'status'       => 'active',
                                'created_at'   => $now,
                                'updated_at'   => $now,
                            ]
                        );

                        $seededCount++;
                    }
                }
            }

            $this->command->info("Seeded exams for school: {$school->name} ({$seededCount} so far)");
        }

        $this->command->info("Total exams seeded: {$seededCount}");
    }
}