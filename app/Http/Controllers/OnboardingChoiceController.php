<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\OnboardingEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Setup entry. The wizard is the only setup surface
 * (docs/onboarding/decisions-2026-11.md, D2): this route exists only to send a
 * school admin to the right wizard step, and never to a chat path.
 */
class OnboardingChoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Request $request): RedirectResponse
    {
        return $this->sendToWizard($request);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->sendToWizard($request);
    }

    private function sendToWizard(Request $request): RedirectResponse
    {
        $school = $request->user()?->school;

        if (! $school instanceof School || $school->onboarding_finished_at !== null) {
            return redirect('/admin/dashboard');
        }

        // A school parked in the retired chat mode is moved to preview on the
        // way in; the migration does the same for rows that never come here.
        if (($school->toshi_mode?->value ?? (string) $school->toshi_mode) === 'onboarding') {
            $school->forceFill(['toshi_mode' => 'preview', 'toshi_enabled' => 0])->save();
            $school->refresh();
        }

        $destination = OnboardingEngine::setupDestination($school, (int) $request->user()->id);

        return $destination !== null
            ? redirect()->to($destination)
            : redirect('/admin/dashboard');
    }
}
