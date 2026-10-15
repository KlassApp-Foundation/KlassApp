<?php

namespace App\Services\Approvals;

use App\Models\Approval;
use App\Models\LessonPlan;
use App\Models\ParentLinkRequest;
use App\Models\Task;
use App\Models\TeacherLeaveApplication;
use App\Models\User;
use App\Services\Demo\DemoBindApproval;
use App\Services\ParentLinkService;
use App\Services\WhatsApp\ParentLinkRequestService;
use App\States\Approval\Approved;
use App\States\Approval\Rejected;
use App\Traits\LogActivity;
use Illuminate\Support\Facades\Gate;

/**
 * Approve or reject one approval. The single implementation behind the
 * admin Approvals page (ApprovalController) and the WhatsApp link request
 * cards (WhatsApp page and dashboard card), so both paths have exactly the
 * same effect:
 *
 *   - a demo BIND request (DemoBindApproval) binds the number, starts the
 *     DEMO_BIND_TTL_MINUTES clock at the approval and sends the WhatsApp
 *     confirmation (reject: the polite reply), through the demo-only send
 *     guard;
 *   - a WhatsApp Flow parent link request creates the parent link for the
 *     chosen student and sends the approval notice (reject: the rejection
 *     notice);
 *   - any other approvable type only changes state.
 *
 * Extracted unchanged from ApprovalController (approve, reject,
 * approveParentLinkRequest, authorizeApproval). Request validation stays
 * with each caller.
 */
class ApprovalDecisionService
{
    use LogActivity;

    public function __construct(
        protected ParentLinkService $parentLinks,
        protected ParentLinkRequestService $parentLinkRequests,
    ) {}

    public function approve(Approval $approval, User $admin, ?int $matchedStudentId = null, ?string $comments = null): ApprovalDecision
    {
        $approval->loadMissing('approvable');

        $this->authorize($approval, $admin);

        if (! $approval->state->canTransitionTo(Approved::class)) {
            return ApprovalDecision::fail('This approval cannot be transitioned to Approved.');
        }

        $linkRequest = $approval->approvable instanceof ParentLinkRequest ? $approval->approvable : null;

        // A demo BIND request (DEMO_BIND_REQUIRES_APPROVAL): bind the number
        // to the demo student and confirm to that one number. No parent
        // account is created and the normal Flow notice is not sent.
        if (DemoBindApproval::isDemoBindRequest($linkRequest)) {
            if (! app(DemoBindApproval::class)->approve($linkRequest, $approval, (int) $matchedStudentId, $admin->id)) {
                return ApprovalDecision::fail('Could not link this demo number (the student is not in this demo school).');
            }
            $this->doActivityLog($linkRequest, $admin, ['approval_id' => $approval->id, 'action' => 'approved', 'demo_bind' => true], 'approval', "Approval #{$approval->id} approved by ".$admin->name);

            return ApprovalDecision::ok('Request approved. The number now follows the student.');
        }

        if ($linkRequest !== null) {
            $failure = $this->approveParentLinkRequest($linkRequest, (int) $matchedStudentId);
            if ($failure !== null) {
                return $failure;
            }
        }

        $approval->state->transitionTo(Approved::class);
        $approval->approved_by = $admin->id;
        $approval->comments = $comments ?? $approval->comments;
        $approval->resolved_at = now();
        $approval->save();

        if ($linkRequest !== null) {
            $this->parentLinkRequests->notifyApproved($linkRequest->fresh(['school']));
        }

        $this->doActivityLog(
            $approval->approvable ?? $approval,
            $admin,
            ['approval_id' => $approval->id, 'action' => 'approved'],
            'approval',
            "Approval #{$approval->id} approved by ".$admin->name
        );

        return ApprovalDecision::ok('Request approved successfully.');
    }

    public function reject(Approval $approval, User $admin, ?string $comments = null): ApprovalDecision
    {
        $approval->loadMissing('approvable');

        $this->authorize($approval, $admin);

        if (! $approval->state->canTransitionTo(Rejected::class)) {
            return ApprovalDecision::fail('This approval cannot be transitioned to Rejected.');
        }

        $linkRequest = $approval->approvable instanceof ParentLinkRequest ? $approval->approvable : null;

        if (DemoBindApproval::isDemoBindRequest($linkRequest)) {
            app(DemoBindApproval::class)->reject($linkRequest, $approval, $comments, $admin->id);
            $this->doActivityLog($linkRequest, $admin, ['approval_id' => $approval->id, 'action' => 'rejected', 'demo_bind' => true], 'approval', "Approval #{$approval->id} rejected by ".$admin->name);

            return ApprovalDecision::ok('Request rejected.');
        }

        if ($linkRequest !== null) {
            $linkRequest->update(['status' => 'rejected']);
        }

        $approval->state->transitionTo(Rejected::class);
        $approval->approved_by = $admin->id;
        $approval->comments = $comments;
        $approval->resolved_at = now();
        $approval->save();

        if ($linkRequest !== null) {
            $this->parentLinkRequests->notifyRejected($linkRequest->fresh(['school']), $comments);
        }

        $this->doActivityLog(
            $approval->approvable ?? $approval,
            $admin,
            ['approval_id' => $approval->id, 'action' => 'rejected'],
            'approval',
            "Approval #{$approval->id} rejected by ".$admin->name
        );

        return ApprovalDecision::ok('Request rejected.');
    }

    /**
     * Deny by default: only the known approvable types can be actioned, and
     * each known type must belong to the caller's school (site admins
     * excepted — the moderation gates below already allow them). Aborts 403.
     */
    public function authorize(Approval $approval, User $user): void
    {
        $approval->loadMissing('approvable');

        $approvable = $approval->approvable;

        $knownTypes = [
            TeacherLeaveApplication::class,
            ParentLinkRequest::class,
            LessonPlan::class,
            Task::class,
        ];

        if ($approvable === null || ! in_array($approval->approvable_type, $knownTypes, true)) {
            abort(403);
        }

        if ((int) $user->usergroup_id !== 1
            && (int) $approvable->school_id !== (int) $user->school_id) {
            abort(403);
        }

        if ($approvable instanceof TeacherLeaveApplication
            && ! Gate::forUser($user)->allows('teacher-leave-manage', $approvable)) {
            abort(403);
        }

        if ($approvable instanceof ParentLinkRequest
            && ! Gate::forUser($user)->allows('parent-link-request-manage', $approvable)) {
            abort(403);
        }
    }

    private function approveParentLinkRequest(ParentLinkRequest $linkRequest, int $studentId): ?ApprovalDecision
    {
        $student = User::query()
            ->where('id', $studentId)
            ->where('school_id', $linkRequest->school_id)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->first();

        if ($student === null) {
            return ApprovalDecision::fail('Selected student is not active in this school.');
        }

        $result = $this->parentLinks->linkByStudentId(
            $linkRequest->phone,
            $studentId,
            $linkRequest->parent_name,
        );

        if (! $result->linked) {
            return ApprovalDecision::fail('Could not create parent link ('.$result->outcome.').');
        }

        $linkRequest->update([
            'status' => 'approved',
            'matched_student_id' => $studentId,
        ]);

        return null;
    }
}
