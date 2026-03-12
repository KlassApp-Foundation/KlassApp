<?php

namespace Database\Seeders\result;
use App\Models\School;
// use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::where("status", 1)->get();
        $comments=["Test Comment1", "Test Comment2", "Test Comment3", "Test Comment4", "Test Comment5"];
        //
        foreach ($schools as $school){
            foreach ($comments as $comment){
                 DB::table("comments")->insert([
               "comment" => $comment,
               "school_id" => $school->id
             ]);
            }
        }
        
        
    }
}
