<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
//use Faker\Generator as Faker;
use App\Models\AcademicYear;
use App\Helpers\SiteHelper;
use App\Models\School;
use App\Models\Book;

class BooksTableSeeder extends Seeder
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
    		$academic_year = AcademicYear::where([['school_id',$school->id],['status',1]])->first();
    		Book::factory(3)->create([
    			'school_id'			=>	$school->id,
    			'academic_year_id'	=>	$academic_year->id,
    		]);
    	}
    }
}