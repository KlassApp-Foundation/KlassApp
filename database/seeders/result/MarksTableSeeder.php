<?php

namespace Database\Seeders\result;

use App\Models\Academics\Remarks;
use Illuminate\Database\Seeder;
use App\Models\Academics\Exam;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Models\StudentAcademic;
use Illuminate\Support\Facades\DB;

class MarksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get real data (only active schools, real students)
        $schools = School::where('status', 1)->get();

        if ($schools->isEmpty()) {
            $this->command->warn('No active schools. Skipping marks seeding.');
            return;
        }

        $exams    = Exam::take(3)->get();           // Limit to 3 exams for demo
        $comments = Remarks::take(5)->pluck('remark')->toArray(); // few comments
        

        $totalMarks = 0;

        foreach ($schools as $school) {
            // Get students in this school
            $students = User::where([
                'school_id'     => $school->id,
                'usergroup_id'  => 6, // students
            ])->take(20)->get(); // limit to 20 students per school for demo

            if ($students->isEmpty()) continue;

            // Get subjects taught in this school (via standard links or subjects table)
            $subjects = Subject::where('school_id', $school->id)->take(8)->get();

            foreach ($students as $student) {
                // Get student's class room (from StudentAcademic)
                $academic = StudentAcademic::where('user_id', $student->id)->first();

                if (! $academic) continue;

                $classRoom = $academic->standardLink;

                if (! $classRoom) continue;

                foreach ($subjects as $subject) {
                    // Only seed marks for subjects that make sense for this class
                    // (you can skip or filter better later)
                    foreach ($exams as $exam) {
                        // Random marks & comment
                        $marks   = rand(45, 95);
                        $comment = $comments[array_rand($comments)] ?? 'Good performance';

                        DB::table('marks')->updateOrInsert(
                            [
                                'student_id'  => $student->id,
                                'subject_id'  => $subject->id,
                                'exam_id'     => $exam->id,
                            ],
                            [
                                'school_id'     => $school->id,
                                'teacher_id'    => $student->school_id ? rand(1, 100) : null, // fake teacher ID
                                'marks'         => $marks,
                                'comment'       => $comment,
                                'created_at'    => now(),
                                'updated_at'    => now(),
                            ]
                        );

                        $totalMarks++;
                    }
                }
            }

            $this->command->info("Seeded marks for school: {$school->name} ({$students->count()} students)");
        }

        $this->command->info("Total marks records seeded: {$totalMarks}");
    }
}