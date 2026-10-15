<?php

namespace App\Services\WhatsApp;

use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\Demo\DemoBindApproval;
use App\Services\Demo\DemoFollowAlong;
use App\States\Approval\Pending;
use App\Traits\LogActivity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Read side of the WhatsApp link request cards (WhatsApp page and the
 * admin dashboard card), plus Unlink. Every query is scoped to one school.
 *
 *   pending()    the school's parent link requests still waiting for a
 *                decision (status pending with a Pending approval), newest
 *                first. A demo BIND request carries the time left before
 *                demo:expire-chats closes it.
 *   linkedNow()  numbers following a demo student right now (an approved or
 *                direct BIND whose DEMO_BIND_TTL_MINUTES session is running),
 *                with the time left in the session.
 *   unlink()     ends one such session with the same column changes as
 *                demo:expire-chats and demo:nightly-reset. Sends nothing.
 *
 * Approve and reject are not here: they go through ApprovalDecisionService,
 * the same code the Approvals page calls.
 */
class LinkRequestBoard
{
    use LogActivity;

    /** @return Collection<int, array<string, mixed>> */
    public function pending(int $schoolId, ?int $limit = null): Collection
    {
        $query = $this->pendingQuery($schoolId)->with('pendingApproval')->orderByDesc('created_at')->orderByDesc('id');
        if ($limit !== null) {
            $query->limit($limit);
        }

        $demoSchool = $this->isDemoSchool($schoolId);

        return $query->get()->map(function (ParentLinkRequest $request) use ($demoSchool) {
            $demoBind = $demoSchool && $request->flow_token === DemoBindApproval::REQUEST_TOKEN;
            $expiresAt = $demoBind && $request->created_at ? DemoFollowAlong::expiresAt($request->created_at) : null;
            $candidates = array_values(array_filter(array_map('intval', (array) ($request->candidate_student_ids ?? []))));

            return [
                'id' => (int) $request->id,
                'approval_id' => (int) $request->pendingApproval->id,
                'phone' => (string) $request->phone,
                'parent_name' => (string) $request->parent_name,
                'child_name' => (string) $request->child_name,
                'child_class' => (string) $request->child_class,
                'requested_at' => $request->created_at,
                'expires_at' => $expiresAt,
                'time_left' => $expiresAt ? self::timeLeft($expiresAt) : null,
                'demo_bind' => $demoBind,
                // A demo BIND names exactly one student (the code). A WhatsApp
                // Flow request needs the admin to pick the student on the
                // Approvals page, so the card does not guess one.
                'student_id' => $demoBind ? ((int) ($request->suggested_student_id ?: ($candidates[0] ?? 0)) ?: null) : null,
            ];
        });
    }

    public function pendingCount(int $schoolId): int
    {
        return $this->pendingQuery($schoolId)->count();
    }

    /** @return Collection<int, array<string, mixed>> */
    public function linkedNow(int $schoolId): Collection
    {
        if (! $this->isDemoSchool($schoolId)) {
            return collect();
        }

        $rows = $this->linkedNowQuery($schoolId)->orderByDesc('demo_linked_at')->orderByDesc('id')->get();
        $students = User::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $rows->pluck('demo_student_id')->filter()->unique())
            ->get(['id', 'name'])
            ->keyBy('id');
        $classes = $this->classNames($schoolId, $students->keys()->all());

        return $rows->map(function (WhatsAppUser $row) use ($students, $classes) {
            $expiresAt = DemoFollowAlong::expiresAt($row->demo_linked_at);

            return [
                'id' => (int) $row->id,
                'phone' => (string) $row->phone,
                'name' => (string) ($row->demo_name ?? ''),
                'student_name' => (string) ($students->get($row->demo_student_id)?->name ?? 'Student'),
                'student_class' => (string) ($classes[$row->demo_student_id] ?? ''),
                'linked_at' => $row->demo_linked_at,
                'expires_at' => $expiresAt,
                'time_left' => self::timeLeft($expiresAt),
            ];
        });
    }

    /**
     * WhatsApp numbers that really reach a parent of this school: a parent
     * account's number that went through a link (verified) and is opted in.
     * The seeded demo parents' fictional numbers are never verified, so they
     * are not counted; a demo BIND counts while its session runs.
     */
    public function linkedNumberCount(int $schoolId): int
    {
        return WhatsAppUser::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('user_id')
            ->whereNotNull('verified_at')
            ->where('opted_in', true)
            ->whereHas('user', fn ($q) => $q->where('school_id', $schoolId)->where('usergroup_id', 7))
            ->where(fn ($q) => $q->whereNull('demo_student_id')
                ->orWhere('demo_linked_at', '>', DemoFollowAlong::bindCutoff()))
            ->count();
    }

    /**
     * End one running demo session of this school (the admin's Unlink).
     * False when the number is not following a student of this demo school.
     */
    public function unlink(int $whatsAppUserId, User $admin): bool
    {
        $schoolId = (int) $admin->school_id;
        if ((int) $admin->usergroup_id !== 3 || ! $this->isDemoSchool($schoolId)) {
            return false;
        }

        $row = WhatsAppUser::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('demo_student_id')
            ->whereKey($whatsAppUserId)
            ->first();
        if (! $row) {
            return false;
        }

        $row->update(DemoFollowAlong::unlinkAttributes());
        $this->doActivityLog($row, $admin, ['action' => 'demo_unlink'], 'whatsapp-unlink', 'Unlinked a demo WhatsApp number');
        Log::info('[demo-bind] number unlinked by the school', ['whatsapp_user_id' => $row->id, 'school_id' => $schoolId]);

        return true;
    }

    /** The Pending approval of one of this school's link requests, if any. */
    public function pendingApproval(int $schoolId, int $requestId): ?Approval
    {
        $request = $this->pendingQuery($schoolId)->whereKey($requestId)->first();

        return $request?->pendingApproval()->first();
    }

    /** "12 min left", "Less than a minute left", or "Expiring now". */
    public static function timeLeft(Carbon $expiresAt): string
    {
        $seconds = now()->diffInSeconds($expiresAt, false);
        if ($seconds <= 0) {
            return 'Expiring now';
        }
        if ($seconds < 60) {
            return 'Less than a minute left';
        }

        return (int) ceil($seconds / 60).' min left';
    }

    private function pendingQuery(int $schoolId)
    {
        return ParentLinkRequest::query()
            ->where('school_id', $schoolId)
            ->where('status', 'pending')
            ->whereHas('approvals', fn ($q) => $q->where('state', Pending::class));
    }

    private function linkedNowQuery(int $schoolId)
    {
        return WhatsAppUser::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('demo_student_id')
            ->whereNotNull('demo_linked_at')
            ->where('demo_linked_at', '>', DemoFollowAlong::bindCutoff());
    }

    private function isDemoSchool(int $schoolId): bool
    {
        return $schoolId > 0 && (int) School::query()->whereKey($schoolId)->value('is_demo') === 1;
    }

    /**
     * @param  array<int, int>  $studentIds
     * @return array<int, string>  student id => "P.7 A"
     */
    private function classNames(int $schoolId, array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        return StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->whereIn('user_id', $studentIds)
            ->with('standardLink.section')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn ($academic) => [(int) $academic->user_id => trim(($academic->standardLink?->section?->name ?? '').' '.($academic->standardLink?->stream ?? ''))])
            ->all();
    }
}
