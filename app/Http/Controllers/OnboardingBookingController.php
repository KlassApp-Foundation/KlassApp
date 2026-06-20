<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OnboardingBookingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_name'   => 'required|string|max:255',
            'contact_name'  => 'required|string|max:255',
            'phone'         => 'required|string|max:20',
            'email'         => 'required|email|max:255',
            'school_type'   => 'nullable|string|max:50',
            'preferred_time'=> 'nullable|date',
            'notes'         => 'nullable|string|max:2000',
        ]);

        Log::info('Onboarding booking received', $validated);

        try {
            Mail::raw(
                "New onboarding booking:\n\n" .
                "School: {$validated['school_name']}\n" .
                "Contact: {$validated['contact_name']}\n" .
                "Email: {$validated['email']}\n" .
                "Phone: {$validated['phone']}\n" .
                "Type: {$validated['school_type']}\n" .
                "Preferred: {$validated['preferred_time']}\n" .
                "Notes: {$validated['notes']}",
                function ($message) {
                    $message->to(config('mail.from.address'))
                            ->subject('New Onboarding Booking — ' . request('school_name'));
                }
            );
        } catch (\Exception $e) {
            Log::warning('Booking email failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Booking received.']);
    }
}
