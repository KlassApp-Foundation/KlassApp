<?php

namespace App\Services;

use App\Models\Academics\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Resolves natural-language names to model instances scoped by school.
 *
 * Returns one of three states:
 *   ['state' => 'resolved', 'model' => Model]  — single exact/fuzzy match
 *   ['state' => 'ambiguous', 'candidates' => [...]]  — multiple matches
 *   ['state' => 'not_found']  — no match
 */
class EntityResolver
{
    public static function resolveStudent(int $schoolId, string $name): array
    {
        return self::resolve(User::where('school_id', $schoolId)->where('usergroup_id', 6), $name);
    }

    public static function resolveExam(int $schoolId, string $name): array
    {
        // Exams don't have a name column — search by subject name + exam type name
        $exact = Exam::where('school_id', $schoolId)
            ->whereHas('subject', fn($q) => $q->where('name', $name))
            ->get();
        if ($exact->count() === 1) {
            return ['state' => 'resolved', 'model' => $exact->first()];
        }

        $fuzzy = Exam::where('school_id', $schoolId)
            ->whereHas('subject', fn($q) => $q->where('name', 'like', '%' . $name . '%'))
            ->get();
        if ($fuzzy->count() === 1) {
            return ['state' => 'resolved', 'model' => $fuzzy->first()];
        }

        if ($fuzzy->count() > 1) {
            return [
                'state' => 'ambiguous',
                'candidates' => $fuzzy->map(fn($m) => [
                    'id' => $m->id,
                    'name' => ($m->subject->name ?? '') . ' — ' . ($m->examType->name ?? ''),
                ])->toArray(),
            ];
        }

        return ['state' => 'not_found'];
    }

    protected static function resolve($query, string $name): array
    {
        // Exact match first
        $exact = (clone $query)->where('name', $name)->get();
        if ($exact->count() === 1) {
            return ['state' => 'resolved', 'model' => $exact->first()];
        }

        // Fuzzy match
        $fuzzy = (clone $query)->where('name', 'like', '%' . $name . '%')->get();
        if ($fuzzy->count() === 1) {
            return ['state' => 'resolved', 'model' => $fuzzy->first()];
        }

        if ($fuzzy->count() > 1) {
            return [
                'state' => 'ambiguous',
                'candidates' => $fuzzy->map(fn($m) => ['id' => $m->id, 'name' => $m->name])->toArray(),
            ];
        }

        return ['state' => 'not_found'];
    }
}
