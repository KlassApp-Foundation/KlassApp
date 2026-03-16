<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssignmentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Demo assignments - all for school 1, academic year 1, teacher 363
        // In real use, you'd loop over schools/years/teachers or generate dynamically
        $assignments = [
            [
                'id'               => 1,
                'school_id'        => 1,
                'academic_year_id' => 1,
                'standardLink_id'  => 1,
                'subject_id'       => 1,
                'teacher_id'       => 363,
                'title'            => 'Test Assignment 1',
                'description'      => 'Dolor morbi non arcu risus quis varius quam quisque id. Commodo ullamcorper a lacus vestibulum sed arcu non odio euismod. Elit ullamcorper dignissim cras tincidunt lobortis feugiat.',
                'attachment'       => null,
                'marks'            => 20,
                'assigned_date'    => '2026-03-01 00:00:00',
                'submission_date'  => '2026-03-15 23:59:59',
                'status'           => 'ongoing',
                'deleted_at'       => null,
            ],

            [
                'id'               => 2,
                'school_id'        => 1,
                'academic_year_id' => 1,
                'standardLink_id'  => 2,
                'subject_id'       => 1,
                'teacher_id'       => 363,
                'title'            => 'Example Assignment',
                'description'      => 'Eget nunc scelerisque viverra mauris in aliquam. Posuere morbi leo urna molestie at elementum. Est pellentesque elit ullamcorper dignissim cras tincidunt lobortis feugiat.',
                'attachment'       => null,
                'marks'            => 30,
                'assigned_date'    => '2026-03-05 00:00:00',
                'submission_date'  => '2026-03-20 23:59:59',
                'status'           => 'pending',
                'deleted_at'       => null,
            ],

            [
                'id'               => 3,
                'school_id'        => 1,
                'academic_year_id' => 1,
                'standardLink_id'  => 1,
                'subject_id'       => 1,
                'teacher_id'       => 363,
                'title'            => 'Math Homework - Week 1',
                'description'      => 'Ac tincidunt vitae semper quis lectus nulla. Ultricies integer quis auctor elit sed vulputate mi sit amet. Dolor morbi non arcu risus quis varius quam quisque id.',
                'attachment'       => 'uploads/assignments/math_week1.pdf',
                'marks'            => 10,
                'assigned_date'    => '2026-02-20 00:00:00',
                'submission_date'  => '2026-03-05 23:59:59',
                'status'           => 'completed',
                'deleted_at'       => null,
            ],

            [
                'id'               => 4,
                'school_id'        => 1,
                'academic_year_id' => 1,
                'standardLink_id'  => 2,
                'subject_id'       => 1,
                'teacher_id'       => 363,
                'title'            => 'Science Project Proposal',
                'description'      => 'Ac tincidunt vitae semper quis lectus nulla. Ultricies integer quis auctor elit sed vulputate mi sit amet. Commodo ullamcorper a lacus vestibulum sed arcu non odio euismod.',
                'attachment'       => 'uploads/assignments/science_proposal.pdf',
                'marks'            => 15,
                'assigned_date'    => '2026-03-10 00:00:00',
                'submission_date'  => '2026-03-25 23:59:59',
                'status'           => 'pending',
                'deleted_at'       => null,
            ],

            [
                'id'               => 5,
                'school_id'        => 1,
                'academic_year_id' => 1,
                'standardLink_id'  => 1,
                'subject_id'       => 1,
                'teacher_id'       => 363,
                'title'            => 'English Essay - Topic: My Future Career',
                'description'      => 'Tincidunt nunc pulvinar sapien et. Donec pretium vulputate sapien nec sagittis aliquam malesuada bibendum arcu. Dui sapien eget mi proin sed libero enim sed faucibus.',
                'attachment'       => null,
                'marks'            => 15,
                'assigned_date'    => '2026-03-12 00:00:00',
                'submission_date'  => '2026-03-26 23:59:59',
                'status'           => 'ongoing',
                'deleted_at'       => null,
            ],
        ];

        foreach ($assignments as $assignment) {
            // Using updateOrInsert assuming 'id' is primary key
            // If your table has unique constraints on other fields, adjust the first array
            DB::table('assignments')->updateOrInsert(
                ['id' => $assignment['id']],
                [
                    ...$assignment,
                    'created_at' => $now,  // or keep original dates if you want historical feel
                    'updated_at' => $now,
                ]
            );
        }

        // Alternative: if you don't care about IDs and just want to seed without duplicates
        // DB::table('assignments')->insertOrIgnore($assignments);
        // But then you'd lose control over specific IDs
    }
}