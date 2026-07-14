<?php

namespace App\Http\Controllers\Admin\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicTermRequest;
use App\Http\Requests\UpdateAcademicTermRequest;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Standard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademicTermController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private function formatData(){
        $school_id = Auth::user()->school_id;
        return [
        "terms" => ["First Term", "Second Term", "Third Term"],
         "yr" => AcademicYear::where("school_id", $school_id)->where("description", "Current Academic Year")->value("id")
        ];
    }
    public function index()
    {
        //
        $school_id = Auth::user()->school_id;
        $academic_year = AcademicYear::where("school_id", $school_id)->where("description", "Current Academic Year")->first();
        
        $terms = AcademicTerm::where("school_id", $school_id)->where("academic_year_id", $academic_year->id)->get();

        $standards = Standard::with("subject")->where("name", "primary")->first();
        
        return view("admin.school.term.index", compact(
            "academic_year", "terms", "standards"
        ) );
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("admin.school.term.create-term", $this->formatData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AcademicTermRequest $request)
    {
        //
        $validated = $request->validated();
        // create the term
        AcademicTerm::create($validated);
        return redirect()->route("admin.academic-term")->with("successmessage", "Academic Term Created!");
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AcademicTerm $term)
    {
        //
        return view("admin.school.term.create-term", array_merge(
            $this->formatData(),
            ["new_term" => $term]
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAcademicTermRequest $request, string $term)
    {
        //
        $validated = $request->validated();
        AcademicTerm::where("id", $term)->update($validated);
        return redirect()->route("admin.academic-term")
                         ->with("successmessage", "Updated Academic Term of id $term !");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $termId)
    {
        //
        // dd($termId);
        $school_id = Auth::user()->school_id;
        AcademicTerm::where("school_id", $school_id)->where("id", $termId)->delete();
        return redirect()->route("admin.academic-term")->with("successmessage", "Academic Term Deleted!");
    }
}
