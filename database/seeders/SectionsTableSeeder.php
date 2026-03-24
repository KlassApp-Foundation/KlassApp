<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use App\Models\School;

class SectionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $schools = School::where('status',1)->get();

        foreach ($schools as $school) 
        {
            $sections = [
                'Baby Class',  'Middle Class','Top Class', 'Primary 1', 'Primary 2', 
                'Primary 3', 'Primary 4', 'Primary 5',   'Primary 6', 'Primary 7',
                ];

            foreach ($sections as $section) 
            {
                DB::table('sections')->updateOrInsert(
                    ['school_id'    =>  $school->id,
                    'name'         =>  $section,
                    ],
                    [
                    'status'       =>  '1',
                    'created_at'   =>   now(),
                    'updated_at'   =>   now(),
                ]);
            }
        }
    }
}