<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentPromotionRulesRequest;
use App\Http\Requests\UpdateStudentPromotionRulesRequest;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StudentPromotionRules;
use Illuminate\Support\Facades\Auth;

class StudentPromotionRuleController extends Controller
{
    //
      //  * Display a listing of the resource.
    public function index()
    {
        $rules = StudentPromotionRules::with([
                'standard',
                'section',
            ])
            ->where('school_id', Auth::user()->school_id)
            ->latest()
            ->get();

        return view("admin.school.promotion.index", compact("rules"));
    }

    // create
    public function create()
{
   $schoolId = auth()->user()->school_id;
    $sections = Section::where('school_id', $schoolId)->orderByDesc('id')->get();
    return view('admin.school.promotion.create', compact('sections'));
}

    //  * Store a newly created resource in storage.
    public function store(StoreStudentPromotionRulesRequest $request)
    {
        $validated = $request->validated();
         StudentPromotionRules::create($validated);
        return redirect()->route("students.promotion") ->with("successmessage", "Promotion Rule set!");
    }

    
    //  * Display the specified resource.
    public function show(StudentPromotionRules $studentPromotionRules)
    {
        abort_if(
            $studentPromotionRules->school_id !== Auth::user()->school_id,
            403
        );

        return response()->json([
            'success' => true,
            'data' => $studentPromotionRules->load([
                'standard',
                'section',
            ]),
        ]);
    }
// edit
    public function edit(StudentPromotionRules $rule)
{
    $schoolId = Auth::user()->school_id;
    $sections = Section::where('school_id', $schoolId)->orderByDesc('id')->get();
    return view('admin.school.promotion.create', compact('rule', 'sections'));
}

    //  * Update the specified resource in storage.
    public function update(
        UpdateStudentPromotionRulesRequest $request,
        StudentPromotionRules $rule
    ) {
       $rule->update($request->validated());
        return redirect()->route("students.promotion") ->with("successmessage", "Promotion Rule Updated!");
    }

    
    //  * Remove the specified resource from storage.
    public function destroy(StudentPromotionRules $studentPromotionRules)
    {
        abort_if(
            $studentPromotionRules->school_id !== Auth::user()->school_id,
            403
        );

        $studentPromotionRules->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promotion rule deleted successfully.',
        ]);
    }
}
