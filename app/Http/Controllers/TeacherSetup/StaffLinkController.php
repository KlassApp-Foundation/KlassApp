<?php

namespace App\Http\Controllers\TeacherSetup;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\TeacherSetup\TeacherSetupLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * School-wide staff link. An email match is mailed the setup link on record.
 * A phone match is told to ask the school admin. Anyone else waits for approval.
 * The page never includes a setup link, a name, or an address.
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
            'notice' => null,
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
        $typedEmail = mb_strtolower(trim((string) ($validated['email'] ?? '')));
        $teacher = $this->links->matchTeacher($school, $validated['email'] ?? null, $validated['phone'] ?? null);

        if ($teacher !== null) {
            $recordEmail = trim((string) $teacher->email);
            $emailMatched = $typedEmail !== ''
                && $recordEmail !== ''
                && mb_strtolower($recordEmail) === $typedEmail;

            if ($emailMatched) {
                $this->links->queuePersonalLink($teacher);

                return $this->notice($token, $school, 'Check your email to finish setting up');
            }

            return $this->notice($token, $school, 'Ask your school admin for your personal link');
        }

        $this->links->requestApproval($school, $validated['name'] ?? null, $validated['email'] ?? null, $validated['phone'] ?? null);

        return view('teacher-setup.staff-form', [
            'token' => $token,
            'school' => $school,
            'sent' => true,
            'notice' => null,
        ]);
    }

    private function notice(string $token, School $school, string $notice): View
    {
        return view('teacher-setup.staff-form', [
            'token' => $token,
            'school' => $school,
            'sent' => false,
            'notice' => $notice,
        ]);
    }
}
