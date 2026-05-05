<?php

namespace App\Http\Controllers\Admin\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSchoolGradingSystem;
use App\Http\Requests\UpdateSchoolGradingSystem;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\AcademicYear;
use App\Models\Standard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchoolGradingSystemController extends Controller
{
// form data
private function formatData(){
        $school_id = Auth::user()->school_id;
        return [
         "standards" => Standard::where("school_id", $school_id)->get()
        ];
    }
      //store comments
    public function create(){
         return view("admin.school.grades.create", $this->formatData());
    }

     public function store(CreateSchoolGradingSystem $request){
        $validated = $request->validated();
        SchoolGradingSystem::create($validated);
        return redirect()->route("admin.grades")->with("successmessage", "Grade Rule created!");
    }
    // retrieve comments
    public function index(){
        $schoolId = Auth::user()->school_id;
        $gradingRules = SchoolGradingSystem::where("school_id", $schoolId)->get();
        return view("admin.school.grades.index", compact("gradingRules"));
    }
    // update comments
    public function update(UpdateSchoolGradingSystem $request){

    }
    // delete comment
    public function destroy(){

    }
}
