<?php

namespace App\Services;

use App\Models\Academics\Exam;
use App\Models\StudentAcademic;
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
    /**
     * Resolve a student by their internal KlassApp ID (numeric) or
     * registration number / klassapp_student_id (string).
     *
     * This is a stronger disambiguator than name matching — for use when
     * the user explicitly provides an identifier after an ambiguous result.
     * Scoped to school, never returns 'ambiguous'.
     */
    public static function resolveStudentByIdentifier(int $schoolId, string $identifier): array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return ['state' => 'not_found'];
        }

        // 1. Try exact User ID (numeric)
        if (ctype_digit($identifier)) {
            $user = User::where('school_id', $schoolId)
                ->where('id', (int) $identifier)
                ->where('usergroup_id', 6)
                ->first();
            if ($user) {
                return ['state' => 'resolved', 'model' => $user];
            }
        }

        // 2. Try klassapp_student_id on StudentAcademic
        $acad = StudentAcademic::where('school_id', $schoolId)
            ->where('klassapp_student_id', $identifier)
            ->first();
        if ($acad) {
            $user = User::where('school_id', $schoolId)
                ->where('id', $acad->user_id)
                ->where('usergroup_id', 6)
                ->first();
            if ($user) {
                return ['state' => 'resolved', 'model' => $user];
            }
        }

        // 3. Try email
        $user = User::where('school_id', $schoolId)
            ->where('email', $identifier)
            ->where('usergroup_id', 6)
            ->first();
        if ($user) {
            return ['state' => 'resolved', 'model' => $user];
        }

        return ['state' => 'not_found'];
    }

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

    /**
     * Returns a label like "Primary 4-A" for a student, or null if
     * the student has no current academic record or standardLink.
     */
    public static function classLabelForUser(int $schoolId, User $user): ?string
    {
        $sa = StudentAcademic::where('school_id', $schoolId)
            ->where('user_id', $user->id)
            ->whereHas('standardLink', fn($q) => $q->where('status', 1))
            ->first();

        if (!$sa || !$sa->standardLink) {
            return null;
        }

        $standard = $sa->standardLink->standard;
        $section = $sa->standardLink->section;
        $standardName = $standard?->name ?? '';
        $sectionName = $section?->name ?? '';

        if ($standardName && $sectionName) {
            return "{$standardName}-{$sectionName}";
        }
        if ($standardName) {
            return $standardName;
        }

        return null;
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
                'candidates' => $fuzzy->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'class' => self::classLabelForUser($m->school_id, $m),
                ])->toArray(),
            ];
        }

        return ['state' => 'not_found'];
    }
}
