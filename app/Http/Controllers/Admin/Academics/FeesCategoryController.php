<?php

namespace App\Http\Controllers\Admin\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeesCategoriesRequest;
use App\Http\Requests\UpdateFeesCategoryRequest;
use App\Models\AcademicTerm;
use App\Models\FeesCategories;
use App\Models\Section;
use App\Models\Standard;
use App\Services\FeeStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeesCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    private function formatData(){
         $school_id = Auth::user()->school_id;

         return [
            "standards" => Standard::where("school_id", $school_id)->get(),
             "sections" =>  Section::where("school_id", $school_id)->where("status", 1)->orderByDesc("id")->get(),
              "terms" =>   AcademicTerm::where("school_id", $school_id)->get()
         ];
    }
    public function index()
    {
        //
        // $region = DB::table("states")
        //            ->where("country_id", 1)
        //            ->get();
        //            dd($region);
        $school_id = Auth::user()->school_id;
        $structure = app(FeeStructureService::class)->structure((int) $school_id);
        $fees = $structure['rows'];
        $classTotals = $structure['totals'];

        return view("admin.school.fees.index", compact(
            "fees",
            "classTotals"
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view("admin.school.fees.create", array_merge($this->formatData(), [
            'appliesTo' => 'whole_school',
            'selectedSections' => [],
        ]));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FeesCategoriesRequest $request)
    {
        //
        
        $validated = $request->validated();
        $schoolId = (int) Auth::user()->school_id;
        $wholeSchool = ($validated['applies_to'] ?? 'whole_school') !== 'classes';

        $fee = DB::transaction(function () use ($validated, $schoolId, $wholeSchool) {
            $fee = FeesCategories::create([
                'school_id' => $schoolId,
                'name' => $validated['name'],
                'amount' => $validated['amount'] ?? 0,
                'fee_type' => $validated['fee_type'] ?? 'tuition',
                'duration' => $validated['duration'] ?? FeesCategories::DURATION_TERMLY,
                'academic_term_id' => $validated['academic_term_id'] ?? null,
                'standard_id' => null,
                'section_id' => null,
                'applies_school_wide' => $wholeSchool,
            ]);

            app(FeeStructureService::class)->assignClasses(
                $fee,
                $wholeSchool,
                $validated['section_ids'] ?? []
            );

            return $fee;
        });

        return $this->saved('Added Fee Category!', $fee);
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
    public function edit(FeesCategories $fee)
    {
        abort_unless(
            (int) $fee->school_id === (int) Auth::user()->school_id,
            404
        );

        //
        // dd($fee);
        $state = app(FeeStructureService::class)->editState($fee);

        return view("admin.school.fees.create", array_merge(
            $this->formatData(),
            [
                "fee" => $fee,
                'appliesTo' => $state['applies_to'],
                'selectedSections' => $state['section_ids'],
            ]
            ));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFeesCategoryRequest $request, string $fee)
    {
        $validated = $request->validated();

        $category = FeesCategories::where("school_id", Auth::user()->school_id)
            ->where("id", $fee)
            ->firstOrFail();

        $attributes = [];
        foreach (['name', 'amount', 'fee_type', 'duration', 'academic_term_id'] as $key) {
            if (array_key_exists($key, $validated)) {
                $attributes[$key] = $validated[$key];
            }
        }
        if ($attributes !== []) {
            $category->update($attributes);
        }

        if (array_key_exists('applies_to', $validated)) {
            app(FeeStructureService::class)->assignClasses(
                $category,
                $validated['applies_to'] !== 'classes',
                $validated['section_ids'] ?? []
            );
        }

        return $this->saved('Updated Fee Category with Id '.$fee.' !', $category);
    }

    private function saved(string $message, FeesCategories $fee): RedirectResponse
    {
        $redirect = redirect()->route('admin.fees-categories')->with('successmessage', $message);
        $warning = app(FeeStructureService::class)->sameNameStackWarning($fee->fresh() ?? $fee);
        if ($warning !== null) {
            $redirect->with('warningmessage', $warning);
        }

        return $redirect;
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
