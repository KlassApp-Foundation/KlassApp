<?php

namespace App\Services\Demo;

use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\DemoSchoolCommsGuard;
use App\Services\WhatsAppBusinessService;
use App\States\Approval\Approved;
use App\States\Approval\Pending;
use App\States\Approval\Rejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * "BIND <code>" with an admin approval step (DEMO_BIND_REQUIRES_APPROVAL).
 *
 * BIND in a demo school does not link the number straight away. It reuses
 * the existing parent link request queue: one parent_link_requests row
 * (flow_token = REQUEST_TOKEN marks it as a demo bind) with the student as
 * the only candidate, and one Pending approval, so the request shows on
 * the admin Approvals page (/admin/approvals) like a WhatsApp Flow request.
 *
 *   approve  links the number to the student (the same binding BIND makes
 *            without approval), starts the DEMO_BIND_TTL_MINUTES clock at
 *            the approval, and sends the confirmation to that one number.
 *   reject   sends a polite reply to that one number. Nothing is linked.
 *   expire   demo:expire-chats closes a request still pending after the
 *            TTL (approval Rejected with an "Expired" comment). Nothing is sent.
 *
 * Only is_demo schools are ever touched; every query is scoped by school_id.
 */
final class DemoBindApproval
{
    public const REQUEST_TOKEN = 'demo-bind';

    /**
     * parent_link_requests.status is an enum (pending, approved, rejected),
     * so an expired or replaced request is stored as rejected; the approval
     * comment says which.
     */
    public const STATUS_CLOSED = 'rejected';

    public function __construct(private WhatsAppBusinessService $api)
    {
    }

    public static function enabled(): bool
    {
        return (bool) config('klassapp.demo.bind_requires_approval', false);
    }

    public static function isDemoBindRequest(?ParentLinkRequest $request): bool
    {
        return $request !== null
            && $request->flow_token === self::REQUEST_TOKEN
            && $request->school_id !== null
            && (int) School::query()->whereKey($request->school_id)->value('is_demo') === 1;
    }

    /** The open demo bind request for this number, if any (demo schools only). */
    public static function pendingFor(string $phone): ?ParentLinkRequest
    {
        return ParentLinkRequest::query()
            ->where('phone', $phone)
            ->where('status', 'pending')
            ->where('flow_token', self::REQUEST_TOKEN)
            ->whereIn('school_id', School::query()->select('id')->where('is_demo', 1))
            ->latest('id')
            ->first();
    }

    /**
     * Record a pending request for $student and tell the sender it is
     * waiting. A second BIND while one is pending replaces the older request.
     */
    public function request(string $phone, User $student, School $school, string $senderName): ParentLinkRequest
    {
        $request = DB::transaction(function () use ($phone, $student, $school, $senderName) {
            $previous = self::pendingFor($phone);
            if ($previous) {
                $this->close($previous, self::STATUS_CLOSED, 'Replaced by a newer BIND from the same number.');
            }

            $academic = \App\Models\StudentAcademic::query()
                ->where('school_id', $school->id)
                ->where('user_id', $student->id)
                ->with('standardLink.section')
                ->orderByDesc('id')
                ->first();
            $class = trim(($academic?->standardLink?->section?->name ?? '') . ' ' . ($academic?->standardLink?->stream ?? ''));

            $request = ParentLinkRequest::create([
                'school_id' => (int) $school->id,
                'phone' => $phone,
                'parent_name' => mb_substr(trim($senderName) !== '' ? trim($senderName) : 'Demo guest', 0, 120),
                'child_name' => (string) $student->name,
                'child_class' => $class,
                'school_name' => (string) $school->name,
                'suggested_student_id' => (int) $student->id,
                'status' => 'pending',
                'flow_token' => self::REQUEST_TOKEN,
                'candidate_student_ids' => [(int) $student->id],
            ]);

            Approval::create([
                'approvable_type' => ParentLinkRequest::class,
                'approvable_id' => $request->id,
                'state' => Pending::class,
                'requested_by' => null,
                'comments' => 'Demo BIND: ' . $request->summaryLine(),
            ]);

            return $request;
        });

        $this->api->sendText($phone, $this->pendingText($request), 'demo_bind_pending');

        return $request;
    }

    public function pendingText(ParentLinkRequest $request): string
    {
        return "📨 Request sent. You asked to follow *{$request->child_name}*"
            . ($request->child_class ? " in *{$request->child_class}*" : '')
            . " at {$request->school_name}.\n\n"
            . "Waiting for the school to approve. We will message you here as soon as they do.";
    }

    /**
     * Approve: link the number (the TTL starts now) and confirm to that one
     * number. Returns false when the request is not a pending demo bind.
     */
    public function approve(ParentLinkRequest $request, Approval $approval, int $studentId, ?int $adminId): bool
    {
        if (! self::isDemoBindRequest($request) || $request->status !== 'pending') {
            return false;
        }

        $school = School::query()->whereKey($request->school_id)->where('is_demo', 1)->first();
        $student = $school ? User::query()
            ->whereKey($studentId)
            ->where('school_id', $school->id)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->first() : null;
        if (! $school || ! $student) {
            return false;
        }

        $replies = app(DemoBindReplies::class);
        if ($replies->numberLinkedToRealSchool($request->phone)) {
            Log::info('[demo-bind] approval refused: number is linked to a school that is not a demo school', [
                'parent_link_request_id' => $request->id,
            ]);

            return false;
        }

        $row = $replies->link($request->phone, $student, $school, (string) $request->parent_name);
        if (! $row) {
            return false;
        }

        $request->update(['status' => 'approved', 'matched_student_id' => (int) $student->id]);
        $approval->state->transitionTo(Approved::class);
        $approval->approved_by = $adminId;
        $approval->resolved_at = now();
        $approval->save();

        $text = $replies->boundText($student, $school);
        DemoSchoolCommsGuard::duringBoundDemoRelease(
            $request->phone,
            fn () => $this->api->sendText($request->phone, $text, 'demo_bind_approved', $row->user_id ? (int) $row->user_id : null),
        );

        return true;
    }

    /** Reject: polite reply to that one number. Nothing is linked. */
    public function reject(ParentLinkRequest $request, Approval $approval, ?string $comment, ?int $adminId): bool
    {
        if (! self::isDemoBindRequest($request) || $request->status !== 'pending') {
            return false;
        }

        // Reply while the request is still pending: the local demo-only send
        // guard lets a pending demo number through, a closed one it refuses.
        $text = "Thank you for trying the KlassApp demo. The school did not approve this request to follow *{$request->child_name}*, so this number is not linked.\n\n"
            . 'You can pick another student on ' . DemoFollowAlong::pageUrl() . ' and send BIND with their code.';
        DemoSchoolCommsGuard::duringBoundDemoRelease(
            $request->phone,
            fn () => $this->api->sendText($request->phone, $text, 'demo_bind_rejected'),
        );

        $request->update(['status' => 'rejected']);
        $approval->state->transitionTo(Rejected::class);
        $approval->approved_by = $adminId;
        $approval->comments = $comment;
        $approval->resolved_at = now();
        $approval->save();


        return true;
    }

    /**
     * Close every demo bind request of this demo school still pending at or
     * before $cutoff. Returns how many were closed. Sends nothing.
     */
    public function expirePending(School $school, \DateTimeInterface $cutoff, bool $dryRun = false): int
    {
        if ((int) $school->is_demo !== 1) {
            return 0;
        }

        $stale = ParentLinkRequest::query()
            ->where('school_id', $school->id)
            ->where('status', 'pending')
            ->where('flow_token', self::REQUEST_TOKEN)
            ->where('created_at', '<=', $cutoff)
            ->get();

        if (! $dryRun) {
            foreach ($stale as $request) {
                $this->close($request, self::STATUS_CLOSED, 'Expired: not approved within ' . DemoFollowAlong::bindTtlMinutes() . ' minutes.');
            }
        }

        return $stale->count();
    }

    private function close(ParentLinkRequest $request, string $status, string $comment): void
    {
        $request->update(['status' => $status]);
        $approval = Approval::query()
            ->where('approvable_type', ParentLinkRequest::class)
            ->where('approvable_id', $request->id)
            ->where('state', Pending::class)
            ->first();
        if ($approval) {
            $approval->state->transitionTo(Rejected::class);
            $approval->comments = $comment;
            $approval->resolved_at = now();
            $approval->save();
        }
        Log::info('[demo-bind] request closed', ['parent_link_request_id' => $request->id, 'status' => $status]);
    }
}
