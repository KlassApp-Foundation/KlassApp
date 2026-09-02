<?php

namespace App\Services\WhatsApp;

use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Models\User;
use App\States\Approval\Pending;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ParentLinkRequestService
{
    /**
     * Persist a WhatsApp Flow submission and enqueue it for school admin review.
     *
     * One submission = one child at one school. Parents with children at
     * different schools submit the Flow once per child.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createFromFlowSubmission(
        string $phone,
        array $payload,
        ?string $flowToken = null,
        string $senderName = 'Parent',
    ): ParentLinkRequest {
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

            return $request->fresh(['school', 'suggestedStudent']);
        });
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

    private function normalizeClassToken(string $childClass): string
    {
        return preg_replace('/\s+/', ' ', trim($childClass)) ?? $childClass;
    }
}
