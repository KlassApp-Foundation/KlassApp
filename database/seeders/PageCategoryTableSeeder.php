<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\AcademicYear;

class PageCategoryTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Practical categories for classroom pages/resources in Ugandan schools
        $categories = [
            'Lesson Notes & Plans',
            'Schemes of Work',
            'Past Papers & Mock Exams',
            'Revision Materials',
            'Video Lessons',
            'Worksheets & Exercises',
            'Reference Materials',
            'Project Ideas & Activities',
            'Assessment & Tests',
            'Co-curricular Resources',
            'General Knowledge & Life Skills',
            'Others / Miscellaneous',
        ];

        $schools = School::where('status', 1)->get();

        foreach ($schools as $school) {
            $academicYear = AcademicYear::where([
                'school_id' => $school->id,
                'status'    => 1,
            ])->first();

            if (! $academicYear) {
                continue;
            }

            foreach ($categories as $name) {
                DB::table('class_room_page_categories')->updateOrInsert(
                    [
                        'school_id'        => $school->id,
                        'academic_year_id' => $academicYear->id,
                        'name'             => $name,
                    ],
                    [
                        'status'     => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}