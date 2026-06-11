<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index()
    {
        $subscriptions = Subscription::with(['user', 'school', 'plan'])
            ->latest()
            ->paginate(15);

        return view("admin.subscription.index", compact("subscriptions"));
    }

    /**
     * Show the form for creating a new subscription.
     */
    public function create()
    {
        // Pass necessary data for dropdowns
        $users = \App\Models\User::all();           // or scope to relevant users
        $schools = \App\Models\School::all();
        $plans = \App\Models\Plan::all();

        return view("admin.subscription.create", compact('users', 'schools', 'plans'));
    }

    /**
     * Store a newly created subscription.
     */
    public function store(StoreSubscriptionRequest $request)
    {
        $subscription = Subscription::create($request->validated());

        // Optional: handle any extra logic (e.g., calculate end_date based on plan, send email, etc.)

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', 'Subscription created successfully.');
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
    public function edit(Subscription $subscription)
    {
        $users = \App\Models\User::all();
        $schools = \App\Models\School::all();
        $plans = \App\Models\Plan::class::all();

        return view("admin.subscription.edit", compact('subscription', 'users', 'schools', 'plans'));
    }

    /**
     * Update the specified subscription.
     */
    public function update(UpdateSubscriptionRequest $request, Subscription $subscription)
    {
        $subscription->update($request->validated());

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', 'Subscription updated successfully.');
    }

    /**
     * Remove the specified subscription from storage.
     */
    public function destroy(Subscription $subscription)
    {
        $subscription->delete(); // Soft delete

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', 'Subscription deleted successfully.');
    }

    /**
     * Optional: Force delete (if needed)
     */
    public function forceDestroy(Subscription $subscription)
    {
        $subscription->forceDelete();

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', 'Subscription permanently deleted.');
    }
}