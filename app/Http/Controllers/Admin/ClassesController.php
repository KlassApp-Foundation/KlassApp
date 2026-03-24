<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classes;
use App\Models\Section;
use App\Models\Standard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassesController extends Controller
{
    //
    public function create(){
        $school_id = Auth::user()->school_id;
        $standards = Standard::where("school_id", $school_id)->get();
        $sects = Section::where("school_id", $school_id)->get();
        $nursery_classes = ["Baby Class", "Middle Class", "Top Class"];
        $primary_classes = [
            "Primary 1", "Primary 2", "Primary 3", "Primary 4", "Primary 5", "Primary 6", "Primary 7"
            ];
        $secondary_classes = ['Senior 1', 'Senior 2', 'Senior 3', 'Senior 4', 'Senior 5', 'Senior 6'];    

        return view("admin.classes.create", compact(
            "school_id", "standards", "nursery_classes", "primary_classes", "secondary_classes", "sects"
            ));
    }

     public function store(Request $request){

        $school_id = Auth::user()->school_id;
        // Validation
        $validated = $request->validate([
            'standard_id' => 'required',
            'name'  => 'required|string|max:255',
        ]);
        $names = [
            "Baby Class", "Middle Class", "Top Class",
            "Primary 1", "Primary 2", "Primary 3", "Primary 4", "Primary 5", "Primary 6", "Primary 7",
            'Senior 1', 'Senior 2', 'Senior 3', 'Senior 4', 'Senior 5', 'Senior 6'
        ];
        foreach ($names as $index => $name){
            if ($validated["name"] === $name){
                dd($index + 1);
                 Classes::create([
                     "name" => $validated["name"],
                     "standard_id" => $validated["standard_id"],
                     "school_id" => $school_id,
                     "status" => "1",
                     "position" => $index + 1
                 ]);
                 break;
            }
        }
       

        return redirect()->route("admin.classes.add")->with("successmessage", "Class created successfully!");
    }
}
