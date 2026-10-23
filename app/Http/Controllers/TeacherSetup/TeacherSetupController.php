<?php

namespace App\Http\Controllers\TeacherSetup;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Services\TeacherSetup\TeacherSetupLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Public entry for a class teacher's personal setup link: set a password, then
 * land on "Set up my class".
 */
class TeacherSetupController extends Controller
{
    public function __construct(private TeacherSetupLinkService $links) {}

    public function show(string $token): View
    {
        $row = $this->links->resolvePersonal($token);
        if ($row === null) {
            return view('teacher-setup.invalid', ['reason' => 'This setup link is invalid, has expired, or has already been used.']);
        }

        $teacher = User::query()->where('school_id', $row->school_id)->find($row->user_id);
        if (! $teacher) {
            return view('teacher-setup.invalid', ['reason' => 'This setup link no longer matches a teacher account.']);
        }

        return view('teacher-setup.set-password', [
            'token' => $token,
            'teacher' => $teacher,
            'school' => School::find($row->school_id),
        ]);
    }

    public function claim(Request $request, string $token): RedirectResponse
    {
        $row = $this->links->resolvePersonal($token);
        if ($row === null) {
            return redirect()->route('teacher.setup.form', $token)
                ->with('error', 'This setup link is invalid or has expired.');
        }

        $teacher = User::query()->where('school_id', $row->school_id)->find($row->user_id);
        if (! $teacher) {
            return redirect()->route('teacher.setup.form', $token)
                ->with('error', 'This setup link no longer matches a teacher account.');
        }

        $validated = $request->validate([
            'password' => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/',
            ],
        ], [
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must include a lower-case letter, an upper-case letter and a number.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        $teacher->forceFill([
            'password' => Hash::make($validated['password']),
            'is_reset' => 1,
            'email_verified' => 1,
            'status' => 'active',
        ])->save();

        $this->links->consumePersonal($row);

        Auth::login($teacher);

        return redirect()->route('teacher.setup.class');
    }
}
