<?php

namespace App\Services\WhatsApp;

use App\Helpers\WhatsAppPhoneHelper;
use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Models\User;
use App\Services\WhatsAppBusinessService;
use App\States\Approval\Pending;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ParentLinkRequestService
{
    public function __construct(
        protected WhatsAppBusinessService $whatsapp,
    ) {}

    /**
     * Persist a WhatsApp Flow submission and enqueue it for school admin review.
     *
     * One submission = one child at one school. Parents with children at
     * different schools submit the Flow once per child — but only after any
     * existing pending request for this phone is resolved (approved/rejected).
     *
     * If a pending request already exists for the phone, returns that row
     * unchanged (`wasRecentlyCreated` will be false) instead of creating a duplicate.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createFromFlowSubmission(
        string $phone,
        array $payload,
        ?string $flowToken = null,
        string $senderName = 'Parent',
    ): ParentLinkRequest {
        $phone = WhatsAppPhoneHelper::normalise($phone);

        $existingPending = $this->findPendingForPhone($phone);
        if ($existingPending !== null) {
            Log::info('Parent link request duplicate suppressed — pending already exists', [
                'parent_link_request_id' => $existingPending->id,
                'phone' => $phone,
            ]);

            return $existingPending->loadMissing(['school', 'suggestedStudent']);
        }

        $parentName = trim((string) ($payload['parent_name'] ?? $senderName));
        $childName = trim((string) ($payload['child_name'] ?? ''));
        $childClass = trim((string) ($payload['child_class'] ?? ''));
        $schoolName = trim((string) ($payload['school_name'] ?? ''));

        return DB::transaction(function () use ($phone, $parentName, $childName, $childClass, $schoolName, $flowToken) {
            $resolvedSchool = $this->resolveSchoolByName($schoolName);

            $candidates = $this->findCandidateStudents(
                $childName,
                $childClass,
                $resolvedSchool?->id,
            );

            // Fallback: if school-name match was ambiguous/missing, infer from
            // a single platform-wide child candidate (previous Day 2 behaviour).
            if ($resolvedSchool === null) {
                $schoolIds = $candidates->pluck('school_id')->unique()->filter()->values();
                if ($schoolIds->count() === 1) {
                    $resolvedSchool = School::find((int) $schoolIds->first());
                }
            } else {
                // Narrow candidates to the resolved school only.
                $candidates = $candidates->where('school_id', $resolvedSchool->id)->values();
            }

            $suggestedStudentId = $candidates->count() === 1
                ? (int) $candidates->first()->id
                : null;

            $candidateIds = $candidates->pluck('id')->values()->all();
            $schoolId = $resolvedSchool?->id;

            $request = ParentLinkRequest::create([
                'school_id' => $schoolId,
                'phone' => $phone,
                'parent_name' => $parentName,
                'child_name' => $childName,
                'child_class' => $childClass,
                'school_name' => $schoolName !== '' ? $schoolName : null,
                'suggested_student_id' => $suggestedStudentId,
                'status' => 'pending',
                'flow_token' => $flowToken,
                'candidate_student_ids' => $candidateIds !== [] ? $candidateIds : null,
            ]);

            if ($schoolId !== null) {
                Approval::create([
                    'approvable_type' => ParentLinkRequest::class,
                    'approvable_id' => $request->id,
                    'state' => Pending::class,
                    'requested_by' => null,
                    'comments' => $request->summaryLine(),
                ]);
            } else {
                Log::warning('Parent link request has no single-school match — admin inbox skipped', [
                    'parent_link_request_id' => $request->id,
                    'submitted_school_name' => $schoolName,
                    'candidate_count' => count($candidateIds),
                ]);
            }

            // load() — not fresh() — so wasRecentlyCreated stays true for callers
            // that distinguish a new insert from a duplicate-pending short-circuit.
            return $request->load(['school', 'suggestedStudent']);
        });
    }

    public function findPendingForPhone(string $phone): ?ParentLinkRequest
    {
        $phone = WhatsAppPhoneHelper::normalise($phone);

        return ParentLinkRequest::query()
            ->where('phone', $phone)
            ->where('status', 'pending')
            ->latest('id')
            ->with(['school'])
            ->first();
    }

    /**
     * Latest rejected request for this phone (only when nothing is still pending).
     */
    public function findLatestRejectedForPhone(string $phone): ?ParentLinkRequest
    {
        $phone = WhatsAppPhoneHelper::normalise($phone);

        if ($this->findPendingForPhone($phone) !== null) {
            return null;
        }

        return ParentLinkRequest::query()
            ->where('phone', $phone)
            ->where('status', 'rejected')
            ->latest('id')
            ->with(['school'])
            ->first();
    }

    public function pendingStatusMessage(ParentLinkRequest $request): string
    {
        $parentName = $request->parent_name !== '' ? $request->parent_name : 'there';
        $childName = $request->child_name !== '' ? $request->child_name : 'your child';
        $schoolName = $request->schoolDisplayName();

        return "Hi {$parentName}, your request to link *{$childName}* at *{$schoolName}* "
            .'is still being reviewed by the school. '
            ."We'll let you know as soon as it's approved.";
    }

    public function rejectedStatusMessage(ParentLinkRequest $request, ?string $adminComment = null): string
    {
        $parentName = $request->parent_name !== '' ? $request->parent_name : 'there';
        $childName = $request->child_name !== '' ? $request->child_name : 'your child';
        $schoolName = $request->schoolDisplayName();

        $message = "Hi {$parentName}, the school couldn't approve your request to link "
            ."*{$childName}* at *{$schoolName}*.";

        $comment = $adminComment !== null ? trim($adminComment) : '';
        if ($comment !== '') {
            $message .= "\n\nReason: {$comment}";
        } else {
            $message .= "\n\nPlease check the child's name, class, and school details, then try again.";
        }

        $message .= "\n\nTap *Request Link* below to submit a new request.";

        return $message;
    }

    public function approvedStatusMessage(ParentLinkRequest $request): string
    {
        $parentName = $request->parent_name !== '' ? $request->parent_name : 'there';
        $childName = $request->child_name !== '' ? $request->child_name : 'your child';
        $schoolName = $request->schoolDisplayName();

        return "✅ Hi {$parentName}! You've been linked to *{$childName}* at *{$schoolName}*.\n\n"
            .'Tap *Menu* below for fees, grades, and more.';
    }

    /**
     * @return list<array{id: string, title: string}>
     */
    public function rejectedActionButtons(): array
    {
        return [
            ['id' => 'parent_link_flow', 'title' => '📋 Request Link'],
            ['id' => 'link_help', 'title' => '🔗 Link help'],
        ];
    }

    /**
     * @return list<array{id: string, title: string}>
     */
    public function approvedActionButtons(): array
    {
        return [
            ['id' => 'MENU', 'title' => '🏠 Menu'],
        ];
    }

    /**
     * Push a rejection notice to the parent over WhatsApp (best-effort).
     */
    public function notifyRejected(ParentLinkRequest $request, ?string $adminComment = null): void
    {
        try {
            $this->whatsapp->sendInteractiveButtons(
                $request->phone,
                $this->rejectedStatusMessage($request->loadMissing('school'), $adminComment),
                $this->rejectedActionButtons(),
                'parent_link_rejected',
            );
        } catch (\Throwable $e) {
            Log::warning('Parent link rejection WhatsApp notify failed', [
                'parent_link_request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Push an approval notice to the parent over WhatsApp (best-effort).
     */
    public function notifyApproved(ParentLinkRequest $request): void
    {
        try {
            $this->whatsapp->sendInteractiveButtons(
                $request->phone,
                $this->approvedStatusMessage($request->loadMissing('school')),
                $this->approvedActionButtons(),
                'parent_link_approved',
            );
        } catch (\Throwable $e) {
            Log::warning('Parent link approval WhatsApp notify failed', [
                'parent_link_request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fuzzy-match a typed school name against active schools.
     * Exact (case-insensitive) match wins; otherwise a single LIKE hit.
     * Ambiguous (0 or 2+) returns null so the caller can fall back.
     */
    public function resolveSchoolByName(string $schoolName): ?School
    {
        if ($schoolName === '') {
            return null;
        }

        $exact = School::query()
            ->where('status', 1)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($schoolName)])
            ->first();

        if ($exact !== null) {
            return $exact;
        }

        $matches = School::query()
            ->where('status', 1)
            ->where('name', 'LIKE', '%'.$schoolName.'%')
            ->limit(5)
            ->get();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        return null;
    }

    /**
     * @return Collection<int, User>
     */
    public function findCandidateStudents(string $childName, string $childClass, ?int $schoolId = null): Collection
    {
        if ($childName === '') {
            return collect();
        }

        $query = User::query()
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->where(function ($q) use ($childName) {
                $q->where('name', 'LIKE', '%'.$childName.'%')
                    ->orWhere('name', 'LIKE', '%'.str_replace(' ', '%', $childName).'%');
            })
            ->with(['studentAcademicLatest.standardLink.section', 'studentAcademicLatest.standardLink.standard', 'school']);

        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        if ($childClass !== '') {
            $normalizedClass = $this->normalizeClassToken($childClass);
            $query->whereHas('studentAcademicLatest.standardLink.section', function ($q) use ($childClass, $normalizedClass) {
                $q->where('name', 'LIKE', '%'.$childClass.'%')
                    ->orWhere('name', 'LIKE', '%'.$normalizedClass.'%');
            });
        }

        return $query->limit(10)->get();
    }

    /**
     * Admin inbox picker: name search without class filter (class mismatch is
     * a common reason Flow auto-match returns no candidates).
     *
     * Tokens of 3+ chars are OR-matched so "Mwesigye Ford" can still hit
     * roster names like "KEVIN MWESIGYE".
     *
     * @return Collection<int, User>
     */
    public function searchStudentsForAdmin(string $query, ?int $schoolId = null): Collection
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return collect();
        }

        $tokens = collect(preg_split('/\s+/', $query) ?: [])
            ->filter(fn (string $token): bool => mb_strlen($token) >= 3)
            ->unique()
            ->values();

        $builder = User::query()
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->where(function ($q) use ($query, $tokens) {
                $q->where('name', 'LIKE', '%'.$query.'%')
                    ->orWhere('name', 'LIKE', '%'.str_replace(' ', '%', $query).'%');

                foreach ($tokens as $token) {
                    $q->orWhere('name', 'LIKE', '%'.$token.'%');
                }
            })
            ->with(['studentAcademicLatest.standardLink.section', 'school']);

        if ($schoolId !== null) {
            $builder->where('school_id', $schoolId);
        }

        return $builder->orderBy('name')->limit(10)->get();
    }

    private function normalizeClassToken(string $childClass): string
    {
        return preg_replace('/\s+/', ' ', trim($childClass)) ?? $childClass;
    }
}
