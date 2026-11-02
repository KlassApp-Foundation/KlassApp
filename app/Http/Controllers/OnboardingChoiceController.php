<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\OnboardingEngine;
use App\Services\Toshi\ToshiOnboardingPath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingChoiceController extends Controller
{
    public function __construct(private ToshiOnboardingPath $paths)
    {
        $this->middleware('auth');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $school = $request->user()->school;

        if ($school === null || $school->onboarding_finished_at !== null) {
            return redirect('/admin/dashboard');
        }

        if ($request->query('path') === 'toshi') {
            return $this->selectToshi($school);
        }

        if ($this->paths->mustUseWizard($school)) {
            return $this->paths->sendToWizard($school);
        }

        return view('auth.onboarding-choice', [
            'school' => $school,
            'toshiPathAvailable' => $this->paths->openFor($school),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'path' => ['required', 'in:wizard,toshi'],
        ]);

        $school = $request->user()->school;
        abort_if($school === null, 403);

        if ($school->onboarding_finished_at !== null) {
            return redirect('/admin/dashboard');
        }

        if ($data['path'] === 'toshi') {
            return $this->selectToshi($school);
        }

        return $this->startWizard($school);
    }

    private function selectToshi(School $school): RedirectResponse
    {
        if (! $this->paths->openFor($school)) {
            return $this->paths->sendToWizard($school);
        }

        $school->forceFill([
            'toshi_mode' => 'onboarding',
            'toshi_enabled' => 0,
        ])->save();

        return redirect('/admin/dashboard')
            ->with('open_toshi_onboarding', true)
            ->with('successmessage', 'Toshi is ready to set up your school.');
    }

    private function startWizard(School $school): RedirectResponse
    {
        $school->forceFill([
            'toshi_mode' => 'preview',
            'toshi_enabled' => 0,
        ])->save();

        $destination = OnboardingEngine::setupDestination($school->fresh(), auth()->id());

        if ($destination === null) {
            return redirect('/admin/dashboard');
        }

        return redirect()->to($destination);
    }
}
