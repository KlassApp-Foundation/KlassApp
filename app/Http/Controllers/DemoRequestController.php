<?php

namespace App\Http\Controllers;

use App\Mail\DemoRequestAlertMail;
use App\Models\DemoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DemoRequestController extends Controller
{
    /**
     * Capture a demo request from the landing page.
     *
     * The request is saved first; the lead alert email is a best-effort
     * notification and never blocks or fails the capture.
     */
    public function store(Request $request): RedirectResponse
    {
        // Honeypot: a real visitor never fills this. Silently accept and drop.
        if ($request->filled('website')) {
            return redirect(url('/') . '#demo')->with('demo_request_success', true);
        }

        $validated = $request->validate([
            'school_name'  => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone'        => 'required|string|max:50',
            'email'        => 'required|email|max:255',
            'district'     => 'nullable|string|max:255',
            'message'      => 'nullable|string|max:2000',
            'source_page'  => 'nullable|string|max:255',
        ]);

        $demoRequest = DemoRequest::create($validated);

        $leadsEmail = config('services.demo.leads_email');
        if ($leadsEmail) {
            try {
                Mail::to($leadsEmail)->queue(new DemoRequestAlertMail($demoRequest));
            } catch (\Throwable $e) {
                Log::warning('Demo request: lead alert email failed', [
                    'demo_request_id' => $demoRequest->id,
                    'error'           => $e->getMessage(),
                ]);
            }
        } else {
            Log::warning('Demo request: LEADS_EMAIL is not configured; request saved without alert.');
        }

        return redirect(url('/') . '#demo')->with('demo_request_success', true);
    }
}
