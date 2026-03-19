<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentAssignmentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Demo student submissions — linked to earlier assignment IDs (1 & 5)
        // Assume student user_ids: 3,4,5 and teacher/marker: 103
        $submissions = [
            [
                'id'                => 1,
                'assignment_id'     => 1,
                'user_id'           => 3,
                'assignment_file'   => 'uploads/student/submissions/math_assignment_1_student3.pdf',
                'obtained_marks'    => 18,
                'submitted_on'      => '2026-03-15',
                'comments'          => 'Good effort, well structured.',
                'marks_given_by'    => 103,
                'marks_given_on'    => '2026-03-17',
                'status'            => 'completed',
                'deleted_at'        => null,
            ],

            [
                'id'                => 2,
                'assignment_id'     => 1,
                'user_id'           => 4,
                'assignment_file'   => 'uploads/student/submissions/math_assignment_1_student4.pdf',
                'obtained_marks'    => null,
                'submitted_on'      => '2026-03-16',
                'comments'          => null,
                'marks_given_by'    => null,
                'marks_given_on'    => null,
                'status'            => 'submitted',
                'deleted_at'        => null,
            ],

            [
                'id'                => 3,
                'assignment_id'     => 1,
                'user_id'           => 5,
                'assignment_file'   => 'uploads/student/submissions/math_assignment_1_student5.pdf',
                'obtained_marks'    => 10,
                'submitted_on'      => '2026-03-14',
                'comments'          => 'Needs more explanation in steps.',
                'marks_given_by'    => 103,
                'marks_given_on'    => '2026-03-16',
                'status'            => 'completed',
                'deleted_at'        => null,
            ],

            [
                'id'                => 4,
                'assignment_id'     => 5,
                'user_id'           => 3,
                'assignment_file'   => 'uploads/student/submissions/english_essay_student3.pdf',
                'obtained_marks'    => null,
                'submitted_on'      => '2026-03-18',
                'comments'          => null,
                'marks_given_by'    => null,
                'marks_given_on'    => null,
                'status'            => 'submitted',
                'deleted_at'        => null,
            ],

            // Bonus one more for variety (late submission example)
            [
                'id'                => 5,
                'assignment_id'     => 1,
                'user_id'           => 6,
                'assignment_file'   => 'uploads/student/submissions/math_assignment_1_student6.pdf',
                'obtained_marks'    => 14,
                'submitted_on'      => '2026-03-25', // late
                'comments'          => 'Late submission but good content.',
                'marks_given_by'    => 103,
                'marks_given_on'    => '2026-03-27',
                'status'            => 'completed',
                'deleted_at'        => null,
            ],
        ];

        foreach ($submissions as $submission) {
            DB::table('student_assignments')->updateOrInsert(
                ['id' => $submission['id']],
                [
                    ...$submission,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info('Seeded ' . count($submissions) . ' student assignment submissions.');
    }
}