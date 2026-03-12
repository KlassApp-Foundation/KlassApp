<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QualificationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $qualifications = [
            // Others / Catch-all
            [
                'id'           => 1,
                'display_name' => 'Others / Not Listed',
                'type'         => 'others',
                'status'       => 1,
            ],

            // Teacher-specific qualifications (Uganda common)
            [
                'id'           => 2,
                'display_name' => 'Grade III Primary Teacher Certificate',
                'type'         => 'teacher',
                'status'       => 1,
            ],
            [
                'id'           => 3,
                'display_name' => 'Grade V Primary Teacher Certificate',
                'type'         => 'teacher',
                'status'       => 1,
            ],
            [
                'id'           => 4,
                'display_name' => 'Diploma in Primary Education (DPE)',
                'type'         => 'teacher',
                'status'       => 1,
            ],
            [
                'id'           => 5,
                'display_name' => 'Diploma in Secondary Education (Dip.Sec.Ed)',
                'type'         => 'teacher',
                'status'       => 1,
            ],
            [
                'id'           => 6,
                'display_name' => 'Bachelor of Education (B.Ed.)',
                'type'         => 'teacher',
                'status'       => 1,
            ],
            [
                'id'           => 7,
                'display_name' => 'Bachelor of Science with Education (B.Sc.Ed)',
                'type'         => 'teacher',
                'status'       => 1,
            ],
            [
                'id'           => 8,
                'display_name' => 'Bachelor of Arts with Education (B.A.Ed)',
                'type'         => 'teacher',
                'status'       => 1,
            ],
            [
                'id'           => 9,
                'display_name' => 'Postgraduate Diploma in Education (PGDE)',
                'type'         => 'teacher',
                'status'       => 1,
            ],
            [
                'id'           => 10,
                'display_name' => 'Master of Education (M.Ed.)',
                'type'         => 'teacher',
                'status'       => 1,
            ],

            // Undergraduate (UG) common degrees
            [
                'id'           => 11,
                'display_name' => 'Bachelor of Arts (B.A.)',
                'type'         => 'ug',
                'status'       => 1,
            ],
            [
                'id'           => 12,
                'display_name' => 'Bachelor of Science (B.Sc.)',
                'type'         => 'ug',
                'status'       => 1,
            ],
            [
                'id'           => 13,
                'display_name' => 'Bachelor of Business Administration (BBA)',
                'type'         => 'ug',
                'status'       => 1,
            ],
            [
                'id'           => 14,
                'display_name' => 'Bachelor of Commerce (B.Com.)',
                'type'         => 'ug',
                'status'       => 1,
            ],
            [
                'id'           => 15,
                'display_name' => 'Bachelor of Laws (LL.B.)',
                'type'         => 'ug',
                'status'       => 1,
            ],

            // Postgraduate (PG) common
            [
                'id'           => 16,
                'display_name' => 'Master of Arts (M.A.)',
                'type'         => 'pg',
                'status'       => 1,
            ],
            [
                'id'           => 17,
                'display_name' => 'Master of Science (M.Sc.)',
                'type'         => 'pg',
                'status'       => 1,
            ],
            [
                'id'           => 18,
                'display_name' => 'Master of Business Administration (MBA)',
                'type'         => 'pg',
                'status'       => 1,
            ],
            [
                'id'           => 19,
                'display_name' => 'Doctor of Philosophy (Ph.D.)',
                'type'         => 'pg',
                'status'       => 1,
            ],
        ];

        foreach ($qualifications as $qual) {
            DB::table('qualifications')->updateOrInsert(
                ['id' => $qual['id']],
                [
                    'display_name' => $qual['display_name'],
                    'type'         => $qual['type'],
                    'status'       => $qual['status'],
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]
            );
        }
    }
}