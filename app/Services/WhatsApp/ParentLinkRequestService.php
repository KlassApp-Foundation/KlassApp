<?php

namespace App\Services\WhatsApp;

use App\Models\Approval;
use App\Models\ParentLinkRequest;
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

        return DB::transaction(function () use ($phone, $parentName, $childName, $childClass, $flowToken) {
            $candidates = $this->findCandidateStudents($childName, $childClass);
            $schoolIds = $candidates->pluck('school_id')->unique()->filter()->values();
            $suggestedStudentId = null;
            $candidateIds = $candidates->pluck('id')->values()->all();

            if ($candidates->count() === 1) {
                $suggestedStudentId = (int) $candidates->first()->id;
            }

            $schoolId = $schoolIds->count() === 1 ? (int) $schoolIds->first() : null;

            if ($schoolId === null && $suggestedStudentId !== null) {
                $schoolId = (int) $candidates->first()->school_id;
            }

            $request = ParentLinkRequest::create([
                'school_id' => $schoolId,
                'phone' => $phone,
                'parent_name' => $parentName,
                'child_name' => $childName,
                'child_class' => $childClass,
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
                    'candidate_count' => count($candidateIds),
                ]);
            }

            return $request->fresh(['school', 'suggestedStudent']);
        });
    }

    /**
     * @return Collection<int, User>
     */
    public function findCandidateStudents(string $childName, string $childClass): Collection
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
