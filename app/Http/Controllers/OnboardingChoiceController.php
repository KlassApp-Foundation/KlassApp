<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OnboardingChoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Request $request)
    {
        $school = $request->user()->school;

        if ($school === null || $school->onboarding_finished_at !== null) {
            return redirect('/admin/dashboard');
        }

        return view('auth.onboarding-choice', ['school' => $school]);
    }

    public function store(Request $request)
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
            $school->forceFill([
                'toshi_mode' => 'onboarding',
                'toshi_enabled' => 0,
            ])->save();

            return redirect('/admin/dashboard')
                ->with('open_toshi_onboarding', true)
                ->with('successmessage', 'Toshi is ready to set up your school.');
        }

        $school->forceFill([
            'toshi_mode' => 'preview',
            'toshi_enabled' => 0,
        ])->save();

        return redirect()->route('admin.onboarding.wizard');
    }
}
