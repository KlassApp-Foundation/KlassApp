<?php

namespace App\Http\Controllers;

use App\Models\EarlyAccessRequest;
use App\Models\User;
use App\Notifications\EarlyAccessRequestedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class EarlyAccessController extends Controller
{
    public function show(Request $request)
    {
        return response()
            ->view('early-access.show', [
                'launchAt' => \Carbon\CarbonImmutable::parse(config('klassapp.launch_at'))->utc(),
                'privacyUrl' => (string) config('klassapp.privacy_notice_url'),
                'submitted' => (bool) session('early_access_submitted'),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    }

    public function store(Request $request)
    {
        // Spam trap: a person never fills this hidden field. Accept and drop.
        if ($request->filled('website')) {
            return redirect()->route('early-access.show')->with('early_access_submitted', true);
        }

        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_role' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'country' => 'required|string|max:120',
            'approx_students' => 'required|integer|min:1|max:1000000',
            'consent' => 'accepted',
        ]);

        unset($validated['consent']);
        $validated['consented_at'] = now();

        $earlyAccess = EarlyAccessRequest::create($validated);

        Log::info('Early access request received', ['early_access_request_id' => $earlyAccess->id]);

        try {
            $admins = User::where('usergroup_id', 1)->where('status', 'active')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new EarlyAccessRequestedNotification($earlyAccess));
            }
        } catch (\Throwable $e) {
            Log::warning('Early access request: admin notification failed', [
                'early_access_request_id' => $earlyAccess->id,
                'exception' => $e::class,
            ]);
        }

        return redirect()->route('early-access.show')->with('early_access_submitted', true);
    }
}
