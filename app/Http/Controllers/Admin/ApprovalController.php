<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\TeacherLeaveApplication;
use App\Models\User;
use App\Services\ParentLinkService;
use App\Services\WhatsApp\ParentLinkRequestService;
use App\States\Approval\Approved;
use App\States\Approval\Pending;
use App\States\Approval\Rejected;
use App\Traits\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ApprovalController extends Controller
{
    use LogActivity;

    public function __construct(
        protected ParentLinkService $parentLinks,
        protected ParentLinkRequestService $parentLinkRequests,
    ) {}

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
                ->whereHas('approvable', fn ($q) => $q->where('school_id', $schoolId))
                ->count(),
            'rejectedCount' => Approval::where('state', Rejected::class)
                ->whereHas('approvable', fn ($q) => $q->where('school_id', $schoolId))
                ->count(),
        ]);
    }

    public function approve(Request $request, Approval $approval)
    {
        $approval->loadMissing('approvable');

        if ($approval->approvable instanceof ParentLinkRequest) {
            $request->validate([
                'comments' => 'nullable|string|max:1000',
                'matched_student_id' => 'required|integer|exists:users,id',
            ]);
        } else {
            $request->validate(['comments' => 'nullable|string|max:1000']);
        }

        $this->authorizeApproval($approval);

        if (! $approval->state->canTransitionTo(Approved::class)) {
            return back()->with('error', 'This approval cannot be transitioned to Approved.');
        }

        if ($approval->approvable instanceof ParentLinkRequest) {
            $linkResult = $this->approveParentLinkRequest($approval->approvable, $request);
            if ($linkResult !== null) {
                return $linkResult;
            }
        }

        $approval->state->transitionTo(Approved::class);
        $approval->approved_by = Auth::id();
        $approval->comments = $request->comments ?? $approval->comments;
        $approval->resolved_at = now();
        $approval->save();

        if ($approval->approvable instanceof ParentLinkRequest) {
            $this->parentLinkRequests->notifyApproved($approval->approvable->fresh(['school']));
        }

        $this->doActivityLog(
            $approval->approvable ?? $approval,
            Auth::user(),
            ['approval_id' => $approval->id, 'action' => 'approved'],
            'approval',
            "Approval #{$approval->id} approved by ".Auth::user()->name
        );

        return back()->with('success', 'Request approved successfully.');
    }

    public function reject(Request $request, Approval $approval)
    {
        $request->validate(['comments' => 'required|string|max:1000']);

        $this->authorizeApproval($approval);

        if (! $approval->state->canTransitionTo(Rejected::class)) {
            return back()->with('error', 'This approval cannot be transitioned to Rejected.');
        }

        if ($approval->approvable instanceof ParentLinkRequest) {
            $approval->approvable->update(['status' => 'rejected']);
        }

        $approval->state->transitionTo(Rejected::class);
        $approval->approved_by = Auth::id();
        $approval->comments = $request->comments;
        $approval->resolved_at = now();
        $approval->save();

        if ($approval->approvable instanceof ParentLinkRequest) {
            $this->parentLinkRequests->notifyRejected(
                $approval->approvable->fresh(['school']),
                $request->comments,
            );
        }

        $this->doActivityLog(
            $approval->approvable ?? $approval,
            Auth::user(),
            ['approval_id' => $approval->id, 'action' => 'rejected'],
            'approval',
            "Approval #{$approval->id} rejected by ".Auth::user()->name
        );

        return back()->with('success', 'Request rejected.');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|null  Error redirect, or null on success.
     */
    private function approveParentLinkRequest(ParentLinkRequest $linkRequest, Request $request): ?\Illuminate\Http\RedirectResponse
    {
        $studentId = (int) $request->input('matched_student_id');

        $student = User::query()
            ->where('id', $studentId)
            ->where('school_id', $linkRequest->school_id)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->first();

        if ($student === null) {
            return back()->with('error', 'Selected student is not active in this school.');
        }

        $result = $this->parentLinks->linkByStudentId(
            $linkRequest->phone,
            $studentId,
            $linkRequest->parent_name,
        );

        if (! $result->linked) {
            return back()->with('error', 'Could not create parent link ('.$result->outcome.').');
        }

        $linkRequest->update([
            'status' => 'approved',
            'matched_student_id' => $studentId,
        ]);

        return null;
    }

    private function authorizeApproval(Approval $approval): void
    {
        $approval->loadMissing('approvable');

        if ($approval->approvable instanceof TeacherLeaveApplication
            && ! Gate::allows('teacher-leave-manage', $approval->approvable)) {
            abort(403);
        }

        if ($approval->approvable instanceof ParentLinkRequest
            && ! Gate::allows('parent-link-request-manage', $approval->approvable)) {
            abort(403);
        }
    }
}
