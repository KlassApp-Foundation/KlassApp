<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use App\Models\School;

class BooksCategoryTableSeeder extends Seeder
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
            $categories = ["Mathematics", "Biology", "Chemistry", "Entrepreneurship", "Physics", "Geography", "Religious Education", "Agriculture", "English Language", "History and Political Education
", "Kiswahili", "Physical Education (PE)", "", 'Astro Physics', 'Arts', 'Economics', 'Computer Studies', 'History', 'Music', 'Technology', 'Magazines', 'Question Bank', 'Projects'];
            foreach ($categories as $category) 
            {
                DB::table('books_category')->Insert([
                    'school_id'        =>   $school->id,
                    'category'         =>   $category,
                    'created_at'       =>   date("Y-m-d H:i:s"),
                    'updated_at'       =>   date("Y-m-d H:i:s"),
                ]);
            }
        }
    }
}