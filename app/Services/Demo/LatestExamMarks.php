<?php

namespace App\Services\Demo;

use App\Contracts\Results\ReleaseGate;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The latest exam of a class, read-only.
 *
 * An "exam" here is one exam type in one term for one class: the app stores
 * one exams row per subject, so the subject rows are grouped back together.
 * The latest is the exam with marks that is scheduled last (then highest id),
 * the same order the dashboard's "Performance by class" chart uses.
 * Every query is scoped by school_id and section_id.
 */
final class LatestExamMarks
{
    /** Display order for the four demo subjects; any other subject follows by name. */
    private const SUBJECT_ORDER = ['mathematics', 'math', 'maths', 'science', 'english', 'social studies'];

    /**
     * @return array{label:string, exams:Collection, subjects:array<int, array{id:int,name:string}>, marks:Collection}|null
     */
    public static function forSection(int $schoolId, int $sectionId): ?array
    {
        $latest = self::latestExam($schoolId, $sectionId, null);

        return $latest ? self::group($schoolId, $sectionId, $latest, null) : null;
    }

    /**
     * The latest exam that has marks for one student, with that student's marks only.
     *
     * @return array{label:string, exams:Collection, subjects:array<int, array{id:int,name:string}>, marks:Collection}|null
     */
    public static function forStudent(int $schoolId, int $sectionId, int $studentId): ?array
    {
        $latest = self::latestExam($schoolId, $sectionId, $studentId);

        return $latest ? self::group($schoolId, $sectionId, $latest, $studentId) : null;
    }

    /**
     * One given exam (the exam type and term of $anchor) for one class, with
     * one student's marks when $studentId is set.
     *
     * @return array{label:string, exams:Collection, subjects:array<int, array{id:int,name:string}>, marks:Collection}
     */
    public static function forExam(int $schoolId, int $sectionId, Exam $anchor, ?int $studentId = null): array
    {
        return self::group($schoolId, $sectionId, $anchor, $studentId);
    }

    /**
     * The most recently released exam (newest release first) that has this
     * student's marks in their class. A release counts when its status is
     * "released" and its scope covers the student: the whole school, or this
     * student's class and stream. Scheduled/cancelled releases are ignored.
     */
    public static function latestReleasedExam(int $schoolId, int $sectionId, int $studentId, int $standardLinkId): ?Exam
    {
        $student = User::query()->where('school_id', $schoolId)->whereKey($studentId)->first();
        if ($student === null) {
            return null;
        }

        // Same policy as every other parent/student marks path. $standardLinkId
        // stays in the signature for existing callers; coverage comes from the
        // student's enrollments, which include that class.
        return app(ReleaseGate::class)->latestReleasedExam($student, $sectionId);
    }

    private static function latestExam(int $schoolId, int $sectionId, ?int $studentId): ?Exam
    {
        return Exam::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->whereNull('deleted_at')
            ->whereHas('marks', function ($q) use ($schoolId, $sectionId, $studentId) {
                $q->where('school_id', $schoolId)->where('section_id', $sectionId);
                if ($studentId !== null) {
                    $q->where('student_id', $studentId);
                }
            })
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->first();
    }

    private static function group(int $schoolId, int $sectionId, Exam $latest, ?int $studentId): array
    {
        $exams = Exam::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->where('academic_term_id', $latest->academic_term_id)
            ->where('exam_type_id', $latest->exam_type_id)
            ->whereNull('deleted_at')
            ->with(['subject', 'examType', 'academicTerm'])
            ->get();

        $marks = Marks::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->when($studentId !== null, fn ($q) => $q->where('student_id', $studentId))
            ->get(['id', 'student_id', 'subject_id', 'exam_id', 'marks', 'grade']);

        $withMarks = $marks->pluck('subject_id')->unique()->all();
        $subjects = $exams
            ->filter(fn (Exam $exam) => $exam->subject && in_array($exam->subject_id, $withMarks))
            ->map(fn (Exam $exam) => ['id' => (int) $exam->subject_id, 'name' => (string) $exam->subject->getRawOriginal('name')])
            ->unique('id')
            ->sort(function (array $a, array $b) {
                $ra = array_search(strtolower($a['name']), self::SUBJECT_ORDER, true);
                $rb = array_search(strtolower($b['name']), self::SUBJECT_ORDER, true);
                $ra = $ra === false ? 99 : $ra;
                $rb = $rb === false ? 99 : $rb;

                return $ra <=> $rb ?: strcmp($a['name'], $b['name']);
            })
            ->values()
            ->all();

        $type = (string) ($latest->examType?->name ?? 'Exam');
        $term = (string) ($latest->academicTerm?->name ?? '');

        return [
            'label' => trim($type . ($term !== '' ? ' · ' . $term : '')),
            'exams' => $exams,
            'subjects' => $subjects,
            'marks' => $marks,
        ];
    }
}
