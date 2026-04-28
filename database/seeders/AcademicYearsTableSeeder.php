<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use Carbon\Carbon;

class AcademicYearsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Approximate Uganda 2026 academic year based on Ministry calendar
        // Full year ~Feb to Dec; adjust if your schools follow different patterns
        $academicYears = [
            // Current / Active for 2026
            [
                'name'        => '2026',
                'description' => 'Current Academic Year',
                'start_date'  => '2026-02-02', // Official Term 1 start
                'end_date'    => '2026-12-04', // Official Term 3 end
                'status'      => '1',          // Active
            ],
            // Next year (pre-seed for planning)
            [
                'name'        => '2027',
                'description' => 'Upcoming Academic Year',
                'start_date'  => '2027-02-01', // Tentative - usually early Feb
                'end_date'    => '2027-12-03', // Tentative
                'status'      => '0',          // Inactive / future
            ],
        ];

        $schools = School::where('status', 1)->get();

        foreach ($schools as $school) {
            foreach ($academicYears as $year) {
                DB::table('academic_years')->updateOrInsert(
                    [
                        'school_id'   => $school->id,
                        'name'        => $year['name'],
                    ],
                    [
                        'description' => $year['description'],
                        'start_date'  => $year['start_date'],
                        'end_date'    => $year['end_date'],
                        'status'      => $year['status'],
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]
                );
            }
        }
    }
}