<?php

namespace App\Http\Controllers\Admin\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeesCategoriesRequest;
use App\Models\AcademicTerm;
use App\Models\FeesCategories;
use App\Models\Section;
use App\Models\Standard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeesCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $school_id = Auth::user()->school_id;
        $fees = FeesCategories::where("school_id", $school_id)->get();
        return view("admin.school.fees.index", compact(
            "fees"
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $school_id = Auth::user()->school_id;
        $standards = Standard::where("school_id", $school_id)->get();
        $sections = Section::where("school_id", $school_id)->where("status", 1)->get();
        $terms = AcademicTerm::where("school_id", $school_id)->get();
        return view("admin.school.fees.create", compact(
            "standards", "sections", "terms"
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FeesCategoriesRequest $request)
    {
        //
        $validated = $request->validated();
        FeesCategories::create($validated);
        return redirect()->route("admin.fees-categories")->with("successmessage", "Added Fee Category!");
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $school_id = Auth::user()->school_id;
        FeesCategories::where("school_id", $school_id)->where("id", $id)->delete();
        return redirect()->route("admin.fees-categories")
                         ->with("successmessage", "Deleted Fees Category with Id $id");
    }
}
