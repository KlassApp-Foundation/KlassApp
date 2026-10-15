<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Services\Approvals\ApprovalDecisionService;
use App\States\Approval\Approved;
use App\States\Approval\Pending;
use App\States\Approval\Rejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalDecisionService $decisions,
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

        // The decision itself is shared with the WhatsApp link request cards.
        $decision = $this->decisions->approve(
            $approval,
            Auth::user(),
            $request->filled('matched_student_id') ? (int) $request->input('matched_student_id') : null,
            $request->comments,
        );

        return back()->with($decision->ok ? 'success' : 'error', $decision->message);
    }

    public function reject(Request $request, Approval $approval)
    {
        $request->validate(['comments' => 'required|string|max:1000']);

        $decision = $this->decisions->reject($approval, Auth::user(), $request->comments);

        return back()->with($decision->ok ? 'success' : 'error', $decision->message);
    }
}
