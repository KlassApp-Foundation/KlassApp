<?php

namespace App\Services\Toshi;

use App\Models\Academics\Marks;
use App\Models\User;

/**
 * Alumni-scoped (ug9) READ helpers for AlumniOperationsAgent.
 *
 * Identity ALWAYS comes from the authenticated user — never from an
 * LLM-supplied id. Records are filtered by `student_id = $alumni->id`; the
 * directory is school-scoped to the alumni's own school and exposes only the
 * same listing the alumni portal already shows (names only — never another
 * graduate's marks, fees or contact details).
 */
class AlumniActionService
{
    /** The alumni's own exam records. */
    public static function examRecords(User $alumni, int $limit = 20): array
    {
        $marks = Marks::with(['exam', 'subject'])
            ->where('student_id', $alumni->id)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();

        if ($marks->isEmpty()) {
            return self::result(false, 'No exam records found on your account yet.', [
                'count' => 0,
                'records' => [],
            ]);
        }

        $records = $marks->map(fn ($mark) => [
            'subject' => $mark->subject->name ?? 'Subject',
            'exam'    => $mark->exam->examType->name ?? ('Exam #'.($mark->exam_id ?? '')),
            'marks'   => $mark->marks,
            'grade'   => $mark->grade,
            'date'    => optional($mark->created_at)->toDateString(),
        ])->all();

        $lines = collect($records)->map(function ($r) {
            $grade = $r['grade'] ? " ({$r['grade']})" : '';

            return "• {$r['subject']} — {$r['exam']}: {$r['marks']}{$grade}";
        })->implode("\n");

        return self::result(true, "You have ".count($records)." exam record(s):\n{$lines}", [
            'count'   => count($records),
            'records' => $records,
        ]);
    }

    /** The alumni's own academic summary (records, subjects, graduation year). */
    public static function academicSummary(User $alumni): array
    {
        $marks = Marks::where('student_id', $alumni->id)->get();

        $lastMarkYear = Marks::where('student_id', $alumni->id)
            ->join('exams', 'marks.exam_id', '=', 'exams.id')
            ->join('academic_years', 'exams.academic_year_id', '=', 'academic_years.id')
            ->value('academic_years.name');

        $summary = [
            'exam_records'    => $marks->count(),
            'subjects_taken'  => $marks->pluck('subject_id')->unique()->count(),
            'graduation_year' => $lastMarkYear ?? '—',
        ];

        if ($summary['exam_records'] === 0) {
            return self::result(false, 'No academic records found on your account yet.', $summary);
        }

        $message = "📘 Your academic summary:\n"
            ."• Exam records: {$summary['exam_records']}\n"
            ."• Subjects taken: {$summary['subjects_taken']}\n"
            ."• Graduation year: {$summary['graduation_year']}";

        return self::result(true, $message, $summary);
    }

    /** The alumni's own profile. */
    public static function profile(User $alumni): array
    {
        $profile = $alumni->userprofile;

        $data = [
            'name'   => $alumni->name,
            'email'  => $alumni->email,
            'phone'  => $alumni->mobile_no,
            'gender' => $profile->gender ?? null,
            'lin'    => $profile->LIN ?? null,
            'school' => $alumni->school->name ?? null,
        ];

        $lines = collect($data)->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v, $k) => '• '.ucfirst($k).': '.$v)->implode("\n");

        return self::result(true, "👤 Your alumni profile:\n{$lines}", $data);
    }

    /** The alumni directory of the alumni's OWN school (names only). */
    public static function directory(User $alumni, int $limit = 20): array
    {
        $others = User::where('school_id', $alumni->school_id)
            ->where('usergroup_id', 9)
            ->where('status', 'active')
            ->where('id', '!=', $alumni->id)
            ->with('userprofile')
            ->orderBy('name')
            ->take($limit)
            ->get();

        if ($others->isEmpty()) {
            return self::result(true, 'No other alumni are registered at your school yet. The directory will populate as more graduates join.', [
                'count'  => 0,
                'alumni' => [],
            ]);
        }

        $list = $others->map(fn (User $u) => ['name' => $u->name])->all();
        $lines = collect($list)->map(fn ($r) => "• {$r['name']}")->implode("\n");

        return self::result(true, "🎓 {$others->count()} other alumni at your school:\n{$lines}", [
            'count'  => $others->count(),
            'alumni' => $list,
        ]);
    }

    /**
     * @return array{success: bool, message: string, data: array}
     */
    private static function result(bool $success, string $message, array $data = []): array
    {
        return compact('success', 'message', 'data');
    }
}
