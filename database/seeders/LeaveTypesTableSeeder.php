<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\AcademicYear;

class LeaveTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Common leave types for schools (Uganda context - adjust days if needed)
        $leaveTypes = [
            [
                'name'           => 'Earned Leave / Privilege Leave',
                'max_no_of_days' => 2,
                'status'         => 1,
            ],
            [
                'name'           => 'Casual Leave',
                'max_no_of_days' => 1,
                'status'         => 1,
            ],
            [
                'name'           => 'Sick Leave / Medical Leave',
                'max_no_of_days' => 1,
                'status'         => 1,
            ],
            [
                'name'           => 'Maternity Leave',
                'max_no_of_days' => 45,   // Uganda labor law allows ~60 days paid, but 45 is common in some sectors
                'status'         => 1,
            ],
            [
                'name'           => 'Quarantine Leave',
                'max_no_of_days' => 5,
                'status'         => 1,
            ],
            [
                'name'           => 'Study Leave / Sabbatical Leave',
                'max_no_of_days' => 7,
                'status'         => 1,
            ],
        ];

        $schools = School::where('status', 1)->get();

        foreach ($schools as $school) {
            $academicYear = AcademicYear::where([
                'school_id' => $school->id,
                'status'    => 1,
            ])->first();

            if (! $academicYear) {
                continue; // Skip if no active academic year
            }

            foreach ($leaveTypes as $type) {
                DB::table('leave_types')->updateOrInsert(
                    [
                        'school_id'        => $school->id,
                        'academic_year_id' => $academicYear->id,
                        'name'             => $type['name'],
                    ],
                    [
                        'max_no_of_days' => $type['max_no_of_days'],
                        'status'         => $type['status'],
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]
                );
            }
        }
    }
}