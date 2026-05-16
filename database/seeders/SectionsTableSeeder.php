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
                'Baby Class',  'Middle Class','Top Class', 'Primary One', 'Primary Two', 
                'Primary Three', 'Primary Four', 'Primary Five',   'Primary Six', 'Primary Seven',
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