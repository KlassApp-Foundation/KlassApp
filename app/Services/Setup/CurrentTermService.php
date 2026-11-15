<?php

namespace App\Services\Setup;

use App\Models\AcademicTerm;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Makes one term the school's current term. A term's status is one of last, current or next, and a
 * school has one current term, so the term that was current is moved to last (or next when it starts
 * after the chosen term). Every lookup is scoped by school_id: a term of another school is reported as
 * not found, exactly like a term that does not exist.
 */
final class CurrentTermService
{
    /**
     * What would change, without changing anything.
     *
     * @return array{found: bool, already_current: bool, term: ?AcademicTerm, demote: list<array{id: int, name: string, to: string}>}
     */
    public function plan(int $schoolId, int $termId): array
    {
        $term = AcademicTerm::query()->where('school_id', $schoolId)->whereKey($termId)->first();
        if ($term === null) {
            return ['found' => false, 'already_current' => false, 'term' => null, 'demote' => []];
        }

        $demote = [];
        $others = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->where('status', 'current')
            ->whereKeyNot($term->id)
            ->get();
        foreach ($others as $other) {
            $startsAfter = $term->starts_on !== null && $other->starts_on !== null && $other->starts_on->gt($term->starts_on);
            $demote[] = ['id' => (int) $other->id, 'name' => (string) $other->name, 'to' => $startsAfter ? 'next' : 'last'];
        }

        return [
            'found' => true,
            'already_current' => (string) $term->status === 'current' && $demote === [],
            'term' => $term,
            'demote' => $demote,
        ];
    }

    /**
     * Apply the change and write one log line. Returns the plan that was applied, or null when the
     * term is not this school's.
     *
     * @return array{found: bool, already_current: bool, term: ?AcademicTerm, demote: list<array{id: int, name: string, to: string}>}|null
     */
    public function apply(int $schoolId, int $termId, ?int $actorId, string $via): ?array
    {
        $plan = $this->plan($schoolId, $termId);
        if (! $plan['found']) {
            return null;
        }
        if ($plan['already_current']) {
            return $plan;
        }

        DB::transaction(function () use ($plan, $schoolId): void {
            foreach ($plan['demote'] as $row) {
                AcademicTerm::query()->where('school_id', $schoolId)->whereKey($row['id'])->update(['status' => $row['to']]);
            }
            AcademicTerm::query()->where('school_id', $schoolId)->whereKey($plan['term']->id)->update(['status' => 'current']);
        });

        Log::info('setup.current-term', [
            'school_id' => $schoolId,
            'term_id' => (int) $plan['term']->id,
            'term' => (string) $plan['term']->name,
            'demoted' => $plan['demote'],
            'actor_id' => $actorId,
            'via' => $via,
        ]);

        $school = School::query()->find($schoolId);
        if ($school !== null) {
            $log = activity()->performedOn($plan['term'])->useLog('setup')->withProperties([
                'school_id' => $schoolId, 'term_id' => (int) $plan['term']->id, 'demoted' => $plan['demote'], 'via' => $via,
            ]);
            if ($actorId !== null && ($actor = \App\Models\User::query()->where('school_id', $schoolId)->find($actorId)) !== null) {
                $log->causedBy($actor);
            }
            $log->log('Made "'.$plan['term']->name.'" the current term');
        }

        return $plan;
    }
}
