<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index()
    {
        $user = Auth::user();
        $subscriptions = Subscription::with(['user', 'school', 'plan'])
                         ->where("school_id", $user->school_id)
                         ->latest()
                         ->paginate(15);
                        //  ->get();
         $staticStatuses = ['approved','canceled','expired'];
         $currentPlan = CurrentPlan::query()
            ->with(['school', 'plan'])
            ->where('school_id', $user->school_id)
            ->where("status", "!=", "running")
            ->latest()
            ->first();
        return view("admin.subscription.index", compact("subscriptions", "staticStatuses", "currentPlan"));
    }

    /**
     * Store a newly created subscription.
     */
    public function store(StoreSubscriptionRequest $request)
    {
        $validated = $request->validated();
        // dd($validated);
        Subscription::create($validated);
        return redirect()
            ->route('admin.subscriptions.index')
            ->with('successmessage', 'Subscription created successfully!');
    }

    /**
     * Display the specified subscription.
     */
    public function show(Subscription $subscription)
    {
        $subscription->load(['user', 'school', 'plan']);

        return view("admin.subscription.show", compact("subscription"));
    }

    /**
     * Show the form for editing the specified subscription.
     */
    public function create()
{
    $user = Auth::user();
    $plans = Plan::all();
    $subscriptions = Subscription::with(['user', 'school', 'plan'])
                         ->where("school_id", $user->school_id)
                         ->latest()
                         ->first();
    //  if(in_array($subscriptions->status, ["pending", "approved"])) {
    //      return;
    //  }
    return view("admin.subscription.create", compact('plans'));
}

public function edit(Subscription $subscription)
{
    if($subscription->status === "approved"){
        return null;
    }
    if($subscription->status === "canceled"){
        return null;
    }
    if($subscription->status === "expired"){
        return null;
    }
    // $users = User::all();
    // $schools = School::all();
    $plans = Plan::all();

    return view("admin.subscription.create", compact('subscription', 'plans'));
}

    /**
     * Update the specified subscription.
     */
    public function update(UpdateSubscriptionRequest $request, Subscription $subscription)
    {
        $validated = $request->validated();
        // dd($validated);
        $subscription->update($validated);

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('successmessage', 'Subscription updated successfully!');
    }

    /**
     * Remove the specified subscription from storage.
     */
    public function destroy(Subscription $subscription)
    {
        $subscription->delete(); // Soft delete

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('successmessage', 'Subscription deleted successfully.');
    }

    /**
     * Optional: Force delete (if needed)
     */
    public function forceDestroy(Subscription $subscription)
    {
        $subscription->forceDelete();

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('successmessage', 'Subscription permanently deleted.');
    }
}
