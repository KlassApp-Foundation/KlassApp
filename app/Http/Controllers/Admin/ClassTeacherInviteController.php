<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Services\ClassTeacherInviteService;
use App\Services\TeacherInviteLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassTeacherInviteController extends Controller
{
    /**
     * Display the invite form for a specific class section.
     */
    public function create(Section $section)
    {
        $user = Auth::user();
        abort_if(
            (int) $user->school_id !== (int) $section->school_id,
            403,
            'You are not authorized for this school.'
        );
        abort_if((int) $section->status !== 1, 404, 'Class not found.');

        $schoolId = (int) $user->school_id;
        $year = \App\Helpers\SiteHelper::getAcademicYear($schoolId);

        // Find the matching StandardLink (stream) for this section
        $standardLink = StandardLink::where('school_id', $schoolId)
            ->where('section_id', $section->id)
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->where('status', 1)
            ->first();

        if (! $standardLink) {
            return redirect()->route('admin.classes')->with('errormessage', 'No active stream found for this class.');
        }

        $existingTeachers = User::where('school_id', $schoolId)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $pendingInvites = TeacherInvite::where('school_id', $schoolId)
            ->whereNull('claimed_at')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.class-teacher-invite.create', compact('section', 'standardLink', 'existingTeachers', 'pendingInvites'));
    }

    /**
     * Handle the invite/reassignment POST.
     */
    public function store(Request $request, StandardLink $standardLink)
    {
        $user = Auth::user();
        abort_if(
            (int) $user->school_id !== (int) $standardLink->school_id,
            403,
            'You are not authorized for this school.'
        );
        abort_if((int) $standardLink->status !== 1, 404, 'Class not found.');

        $validated = $request->validate([
            'email'               => 'required|email',
            'name'                => 'nullable|string|min:3',
            'phone'               => 'nullable|string',
            'existing_teacher_id' => 'nullable|integer|min:1',
        ]);

        $result = ClassTeacherInviteService::invite($user, $standardLink, $validated);

        $flashKey = $result['success'] ? 'successmessage' : 'errormessage';

        return redirect()->route('admin.classes')->with($flashKey, $result['message']);
    }

    /**
     * Resend an invite with a fresh token. The previous link stops working.
     */
    public function resend(TeacherInvite $invite)
    {
        $user = Auth::user();
        abort_if(
            (int) $invite->school_id !== (int) $user->school_id,
            403,
            'You are not authorized for this school.'
        );

        if ($invite->isClaimed()) {
            return redirect()->route('admin.classes')
                ->with('errormessage', 'This invite has already been used.');
        }

        $result = TeacherInviteLinkService::reissue($invite);
        $school = $invite->school;
        $className = $invite->standardLink?->section?->name
            ?? $invite->standardLink?->stream
            ?? null;

        TeacherInviteLinkService::sendEmail($result['invite'], $result['token'], $school, $className);

        if ($invite->phone !== null && trim((string) $invite->phone) !== '') {
            TeacherInviteLinkService::sendWhatsApp($result['invite'], $result['token'], $school, $className);
        }

        return back()->with('successmessage', 'A fresh invite link has been sent.');
    }
}
