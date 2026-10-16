<?php

namespace App\Services\WhatsApp;

use App\Helpers\WhatsAppPhoneHelper;
use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\WhatsAppUser;
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
 *                first. KlassApp number, form, and demo BIND all land here.
 *                Only a demo BIND carries the time left before
 *                demo:expire-chats closes it.
 *   linkedNow()  numbers linked to a student of this school. A demo session
 *                includes the time left. A real-school link does not expire.
 *   unlink()     ends a demo session (the same columns as demo:expire-chats)
 *                or clears a real link (user_id and verified_at). Sends nothing.
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

        $requests = $query->get();
        $students = $this->studentsFor($schoolId, $requests
            ->flatMap(fn (ParentLinkRequest $request) => array_merge(
                array_map('intval', (array) ($request->candidate_student_ids ?? [])),
                [(int) $request->suggested_student_id],
            ))
            ->filter()
            ->unique()
            ->values()
            ->all());

        return $requests->map(function (ParentLinkRequest $request) use ($students) {
            $source = $request->sourceKey();
            $demoBind = $source === 'demo';
            $expiresAt = $demoBind && $request->created_at ? DemoFollowAlong::expiresAt($request->created_at) : null;
            $candidateIds = array_values(array_unique(array_filter(array_map('intval', (array) ($request->candidate_student_ids ?? [])))));
            $candidateRows = collect($candidateIds)
                ->map(fn (int $id) => $students->get($id))
                ->filter()
                ->map(fn (User $student) => $this->studentCard($student))
                ->values()
                ->all();
            $suggested = $students->get((int) $request->suggested_student_id);
            $matched = $suggested ?? ($candidateRows !== [] && count($candidateRows) === 1 ? $students->get($candidateRows[0]['id']) : null);
            $className = (string) ($matched ? $this->classParts($matched)['class'] : '');
            $stream = (string) ($matched ? $this->classParts($matched)['stream'] : '');
            $childName = $matched ? (string) $matched->name : (string) $request->child_name;
            $exact = $source === 'form'
                && $matched
                && count($candidateRows) === 1
                && $this->sameText((string) $request->child_name, (string) $matched->name)
                && $this->sameText((string) $request->child_class, trim($className.' '.$stream));
            $studentId = null;
            if ($source !== 'form' && $matched) {
                $studentId = (int) $matched->id;
            } elseif ($source === 'form' && count($candidateRows) === 1) {
                $studentId = (int) $candidateRows[0]['id'];
            }

            return [
                'id' => (int) $request->id,
                'approval_id' => (int) $request->pendingApproval->id,
                'phone' => (string) $request->phone,
                'phone_display' => WhatsAppPhoneHelper::readable((string) $request->phone),
                'has_name' => $request->cardParentName() !== '',
                'parent_name' => $request->cardParentName() !== '' ? $request->cardParentName() : WhatsAppPhoneHelper::readable((string) $request->phone),
                'profile_name' => (string) ($request->whatsapp_profile_name ?? ''),
                'child_name' => $childName !== '' ? $childName : 'Student not named',
                'class_name' => $className,
                'stream' => $stream,
                'child_class' => trim($className.' '.$stream) !== '' ? trim($className.' '.$stream) : (string) $request->child_class,
                'requested_at' => $request->created_at,
                'expires_at' => $expiresAt,
                'time_left' => $expiresAt ? self::timeLeft($expiresAt) : null,
                'source' => $source,
                'source_label' => $request->sourceLabel(),
                'demo_bind' => $demoBind,
                'student_id' => $studentId,
                'candidate_ids' => array_map(fn (array $row) => $row['id'], $candidateRows),
                'candidates' => $candidateRows,
                'needs_check' => $source === 'form' && ! $exact,
                'typed_child' => (string) $request->child_name,
                'typed_school' => (string) ($request->school_name ?? ''),
                'typed_class' => (string) $request->child_class,
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
        $demo = $this->isDemoSchool($schoolId) ? $this->demoSessions($schoolId) : collect();
        $real = $this->realLinks($schoolId, $demo->pluck('id')->all());

        return $demo->concat($real)->sortByDesc(fn (array $row) => $row['linked_at']?->getTimestamp() ?? 0)->values();
    }

    /**
     * WhatsApp numbers that really reach a parent of this school: a parent
     * account's number that went through a link (verified) and is opted in.
     * The seeded demo parents' fictional numbers are never verified, so they
     * are not counted; a demo BIND counts while its session runs.
     */
    public function linkedNumberCount(int $schoolId): int
    {
        return $this->linkedQuery($schoolId)->count();
    }

    /** The parent's real WhatsApp link, if one is opted in and verified. */
    public function linkedRowForParent(int $schoolId, int $parentId): ?WhatsAppUser
    {
        return $this->linkedQuery($schoolId)->where('user_id', $parentId)->orderByDesc('verified_at')->first();
    }

    private function linkedQuery(int $schoolId)
    {
        return WhatsAppUser::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('user_id')
            ->whereNotNull('verified_at')
            ->where('opted_in', true)
            ->whereHas('user', fn ($q) => $q->where('usergroup_id', 7)->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)->orWhereNull('school_id');
            }))
            ->where(fn ($q) => $q->whereNull('demo_student_id')
                ->orWhere('demo_linked_at', '>', DemoFollowAlong::bindCutoff()));
    }

    /** @param  list<int>  $parentIds */
    public function linkedRowsForParents(int $schoolId, array $parentIds): \Illuminate\Support\Collection
    {
        if ($parentIds === []) {
            return collect();
        }

        return $this->linkedQuery($schoolId)
            ->whereIn('user_id', $parentIds)
            ->get(['user_id', 'whatsapp_profile_name']);
    }

    /**
     * Unlink one number of this school. A running demo session is cleared the
     * same way as demo:expire-chats. A real link only drops user_id and
     * verified_at, which is what the WhatsApp parents page already does.
     */
    public function unlink(int $whatsAppUserId, User $admin): bool
    {
        $schoolId = (int) $admin->school_id;
        if ((int) $admin->usergroup_id !== 3 || $schoolId < 1) {
            return false;
        }

        $row = WhatsAppUser::query()->where('school_id', $schoolId)->whereKey($whatsAppUserId)->first();
        if (! $row) {
            return false;
        }

        $demoSession = $row->demo_student_id
            && $row->demo_linked_at
            && $row->demo_linked_at->gt(DemoFollowAlong::bindCutoff());
        if ($demoSession) {
            if (! $this->isDemoSchool($schoolId)) {
                return false;
            }
            $row->update(DemoFollowAlong::unlinkAttributes());
            $this->doActivityLog($row, $admin, ['action' => 'demo_unlink'], 'whatsapp-unlink', 'Unlinked a demo WhatsApp number');
            Log::info('[demo-bind] number unlinked by the school', ['whatsapp_user_id' => $row->id, 'school_id' => $schoolId]);

            return true;
        }

        if (! $row->user_id) {
            return false;
        }

        $row->update(['user_id' => null, 'verified_at' => null]);
        $this->doActivityLog($row, $admin, ['action' => 'unlink'], 'whatsapp-unlink', 'Unlinked a WhatsApp number');

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

    /** @return Collection<int, array<string, mixed>> */
    private function demoSessions(int $schoolId): Collection
    {
        $rows = WhatsAppUser::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('demo_student_id')
            ->whereNotNull('demo_linked_at')
            ->where('demo_linked_at', '>', DemoFollowAlong::bindCutoff())
            ->orderByDesc('demo_linked_at')
            ->orderByDesc('id')
            ->get();
        $students = $this->studentsFor($schoolId, $rows->pluck('demo_student_id')->filter()->unique()->all());

        return $rows->map(function (WhatsAppUser $row) use ($students) {
            $student = $students->get((int) $row->demo_student_id);
            $parts = $student ? $this->classParts($student) : ['class' => '', 'stream' => ''];
            $expiresAt = DemoFollowAlong::expiresAt($row->demo_linked_at);
            $profile = trim((string) ($row->whatsapp_profile_name ?: $row->demo_name ?: ''));

            return [
                'id' => (int) $row->id,
                'phone' => (string) $row->phone,
                'phone_display' => WhatsAppPhoneHelper::readable((string) $row->phone),
                'name' => $profile !== '' && ParentLinkRequest::profileNameOrNull($profile) !== null
                    ? $profile
                    : WhatsAppPhoneHelper::readable((string) $row->phone),
                'profile_name' => (string) ($row->whatsapp_profile_name ?? ''),
                'student_name' => (string) ($student?->name ?? 'Student'),
                'class_name' => $parts['class'],
                'stream' => $parts['stream'],
                'student_class' => trim($parts['class'].' '.$parts['stream']),
                'linked_at' => $row->demo_linked_at,
                'expires_at' => $expiresAt,
                'time_left' => self::timeLeft($expiresAt),
                'demo' => true,
            ];
        });
    }

    /**
     * @param  array<int, int>  $exceptIds  demo-session rows already listed
     * @return Collection<int, array<string, mixed>>
     */
    private function realLinks(int $schoolId, array $exceptIds): Collection
    {
        $rows = WhatsAppUser::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('user_id')
            ->whereNotNull('verified_at')
            ->where('opted_in', true)
            ->whereHas('user', fn ($q) => $q->where('usergroup_id', 7))
            ->when($exceptIds !== [], fn ($q) => $q->whereNotIn('id', $exceptIds))
            ->where(fn ($q) => $q->whereNull('demo_student_id')
                ->orWhereNull('demo_linked_at')
                ->orWhere('demo_linked_at', '<=', DemoFollowAlong::bindCutoff()))
            ->with('user')
            ->orderByDesc('verified_at')
            ->orderByDesc('id')
            ->get();

        $links = StudentParentLink::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->whereIn('parent_id', $rows->pluck('user_id')->filter()->unique())
            ->orderByDesc('id')
            ->get()
            ->unique('parent_id');
        $students = $this->studentsFor($schoolId, $links->pluck('student_id')->filter()->unique()->all());
        $byParent = $links->keyBy('parent_id');

        return $rows->map(function (WhatsAppUser $row) use ($students, $byParent) {
            $link = $byParent->get($row->user_id);
            $student = $link ? $students->get((int) $link->student_id) : null;
            $parts = $student ? $this->classParts($student) : ['class' => '', 'stream' => ''];
            $profile = trim((string) ($row->whatsapp_profile_name ?? ''));
            $name = $profile !== '' ? $profile : trim((string) ($row->user?->name ?? ''));

            return [
                'id' => (int) $row->id,
                'phone' => (string) $row->phone,
                'phone_display' => WhatsAppPhoneHelper::readable((string) $row->phone),
                'name' => $name !== '' && ParentLinkRequest::profileNameOrNull($name) !== null
                    ? $name
                    : WhatsAppPhoneHelper::readable((string) $row->phone),
                'profile_name' => $profile,
                'student_name' => (string) ($student?->name ?? 'Student'),
                'class_name' => $parts['class'],
                'stream' => $parts['stream'],
                'student_class' => trim($parts['class'].' '.$parts['stream']),
                'linked_at' => $row->verified_at,
                'expires_at' => null,
                'time_left' => null,
                'demo' => false,
            ];
        });
    }

    private function isDemoSchool(int $schoolId): bool
    {
        return $schoolId > 0 && (int) School::query()->whereKey($schoolId)->value('is_demo') === 1;
    }

    /**
     * @param  array<int, int>  $studentIds
     * @return Collection<int, User>
     */
    private function studentsFor(int $schoolId, array $studentIds): Collection
    {
        $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
        if ($studentIds === []) {
            return collect();
        }

        return User::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $studentIds)
            ->with('studentAcademicLatest.standardLink.section')
            ->get()
            ->keyBy('id');
    }

    /** @return array{id:int, name:string, class:string, stream:string} */
    private function studentCard(User $student): array
    {
        $parts = $this->classParts($student);

        return [
            'id' => (int) $student->id,
            'name' => (string) $student->name,
            'class' => $parts['class'],
            'stream' => $parts['stream'],
        ];
    }

    /** @return array{class:string, stream:string} */
    private function classParts(User $student): array
    {
        $link = $student->studentAcademicLatest?->standardLink;

        return [
            'class' => trim((string) ($link?->section?->name ?? '')),
            'stream' => trim((string) ($link?->stream ?? '')),
        ];
    }

    private function sameText(string $a, string $b): bool
    {
        $norm = fn (string $value) => mb_strtolower(preg_replace('/\s+/', ' ', trim($value)) ?? '');

        return $norm($a) !== '' && $norm($a) === $norm($b);
    }
}
