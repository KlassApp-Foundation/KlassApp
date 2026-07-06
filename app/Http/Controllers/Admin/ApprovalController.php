<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\TeacherLeaveApplication;
use App\States\Approval\Approved;
use App\States\Approval\Pending;
use App\States\Approval\Rejected;
use App\Traits\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    use LogActivity;

    public function inbox()
    {
        $schoolId = Auth::user()->school_id;

        $pendingCount = Approval::where('state', Pending::class)
            ->whereHas('approvable', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->count();

        $approvals = Approval::with(['approvable', 'requester', 'reviewer'])
            ->whereHas('approvable', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('admin.approvals.inbox', [
            'approvals'     => $approvals,
            'pendingCount'  => $pendingCount,
            'approvedCount' => Approval::where('state', Approved::class)
                ->whereHas('approvable', fn($q) => $q->where('school_id', $schoolId))
                ->count(),
            'rejectedCount' => Approval::where('state', Rejected::class)
                ->whereHas('approvable', fn($q) => $q->where('school_id', $schoolId))
                ->count(),
        ]);
    }

    public function approve(Request $request, Approval $approval)
    {
        $request->validate(['comments' => 'nullable|string|max:1000']);

        if (! $approval->state->canTransitionTo(Approved::class)) {
            return back()->with('error', 'This approval cannot be transitioned to Approved.');
        }

        $approval->state->transitionTo(Approved::class);
        $approval->approved_by = Auth::id();
        $approval->comments = $request->comments ?? $approval->comments;
        $approval->resolved_at = now();
        $approval->save();

        $this->doActivityLog(
            $approval->approvable ?? $approval,
            Auth::user(),
            ['approval_id' => $approval->id, 'action' => 'approved'],
            'approval',
            "Approval #{$approval->id} approved by " . Auth::user()->name
        );

        return back()->with('success', 'Request approved successfully.');
    }

    public function reject(Request $request, Approval $approval)
    {
        $request->validate(['comments' => 'required|string|max:1000']);

        if (! $approval->state->canTransitionTo(Rejected::class)) {
            return back()->with('error', 'This approval cannot be transitioned to Rejected.');
        }

        $approval->state->transitionTo(Rejected::class);
        $approval->approved_by = Auth::id();
        $approval->comments = $request->comments;
        $approval->resolved_at = now();
        $approval->save();

        $this->doActivityLog(
            $approval->approvable ?? $approval,
            Auth::user(),
            ['approval_id' => $approval->id, 'action' => 'rejected'],
            'approval',
            "Approval #{$approval->id} rejected by " . Auth::user()->name
        );

        return back()->with('success', 'Request rejected.');
    }
}
