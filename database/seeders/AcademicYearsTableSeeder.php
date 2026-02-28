<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use App\Models\School;

class AcademicYearsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $schools = School::where('status',1)->get();

        foreach ($schools as $school) 
        {
            DB::table('academic_years')->Insert([
                'school_id'    =>  $school->id,
                'name'         =>  '2026-2027',
                'description'  =>  'This is Current Academic Year',
                'start_date'   =>  '2026-02-05',
                'end_date'     =>  '2026-12-03',
                'status'       =>  '1',
                'created_at'   =>   date("Y-m-d H:i:s"),
                'updated_at'   =>   date("Y-m-d H:i:s"),
            ]);

            DB::table('academic_years')->Insert([
                'school_id'    =>  $school->id,
                'name'         =>  '2027-2028',
                'description'  =>  'This is Next Academic Year',
                'start_date'   =>  '2027-02-04',
                'end_date'     =>  '2027-12-2',
                'status'       =>  '1',
                'created_at'   =>   date("Y-m-d H:i:s"),
                'updated_at'   =>   date("Y-m-d H:i:s"),
            ]);
        }
    }
}