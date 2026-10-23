<?php

namespace App\Http\Controllers\TeacherSetup;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\TeacherSetup\TeacherSetupLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * School-wide staff link. A visitor is matched to the uploaded teacher list by
 * email or phone; anyone else is queued for admin approval. Nothing is created
 * automatically for a stranger.
 */
class StaffLinkController extends Controller
{
    public function __construct(private TeacherSetupLinkService $links) {}

    public function show(string $token): View
    {
        $row = $this->links->resolveStaffLink($token);
        if ($row === null) {
            return view('teacher-setup.invalid', ['reason' => 'This staff link is invalid, has expired, or has been switched off.']);
        }

        return view('teacher-setup.staff-form', [
            'token' => $token,
            'school' => School::find($row->school_id),
            'sent' => false,
        ]);
    }

    public function submit(Request $request, string $token): View|RedirectResponse
    {
        $row = $this->links->resolveStaffLink($token);
        if ($row === null) {
            return view('teacher-setup.invalid', ['reason' => 'This staff link is invalid, has expired, or has been switched off.']);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:120',
            'email' => 'nullable|email|max:190',
            'phone' => 'nullable|string|max:30',
        ]);

        if (blank($validated['email'] ?? null) && blank($validated['phone'] ?? null)) {
            return back()->withErrors(['email' => 'Enter the email or phone number your school has for you.']);
        }

        $school = School::findOrFail($row->school_id);
        $teacher = $this->links->matchTeacher($school, $validated['email'] ?? null, $validated['phone'] ?? null);

        if ($teacher !== null) {
            $personal = $this->links->issueFor($teacher);

            return redirect()->to($this->links->personalUrl($personal));
        }

        $this->links->requestApproval($school, $validated['name'] ?? null, $validated['email'] ?? null, $validated['phone'] ?? null);

        return view('teacher-setup.staff-form', [
            'token' => $token,
            'school' => $school,
            'sent' => true,
        ]);
    }
}
