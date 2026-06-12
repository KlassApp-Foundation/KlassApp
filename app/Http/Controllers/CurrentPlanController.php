<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCurrentPlanRequest;
use App\Http\Requests\UpdateCurrentPlanRequest;
use App\Models\CurrentPlan;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CurrentPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $currentPlans = CurrentPlan::query()
            ->with(['school', 'plan'])
            ->latest()
            ->paginate(15);

        return view('current-plans.index', compact('currentPlans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('current-plans.create', compact('plans'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCurrentPlanRequest $request): RedirectResponse
    {
        CurrentPlan::updateOrCreate(
            [
                'school_id' => Auth::user()->school_id,
            ],
            [
                'plan_id' => $request->validated('plan_id'),
                'status' => $request->validated('status', true),
            ]
        );

        return redirect()
            ->route('current-plans.index')
            ->with('success', 'Current plan saved successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CurrentPlan $currentPlan): View
    {
        $currentPlan->load(['school', 'plan']);

        return view('current-plans.show', compact('currentPlan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CurrentPlan $currentPlan): View
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return view(
            'current-plans.edit',
            compact('currentPlan', 'plans')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateCurrentPlanRequest $request,
        CurrentPlan $currentPlan
    ): RedirectResponse {
        $currentPlan->update($request->validated());

        return redirect()
            ->route('current-plans.index')
            ->with('success', 'Current plan updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CurrentPlan $currentPlan): RedirectResponse
    {
        $currentPlan->delete();

        return redirect()
            ->route('current-plans.index')
            ->with('success', 'Current plan deleted successfully.');
    }
}