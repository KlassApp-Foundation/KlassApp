<?php

namespace App\Services\Onboarding;

use App\Models\AcademicTerm;
use App\Models\School;
use App\Models\Section;
use App\Models\Teacherlink;
use App\Models\User;
use Carbon\Carbon;
use Throwable;

/**
 * Everyday questions for a school that has already finished checklist setup.
 * Each line is built from that school's own rows.
 */
class ChecklistAssistantPrompts
{
    /**
     * @return array{welcome: string, questions: list<array{id: string, text: string, answer: string}>}
     */
    public static function forSchool(?School $school): array
    {
        $name = trim((string) ($school->name ?? ''));
        $welcome = ($name !== '' ? $name : 'The school').' is set up. Ask me about the school.';
        if ($school === null) {
            return ['welcome' => $welcome, 'questions' => []];
        }

        $questions = [];
        $learners = self::learners($school);
        if ($learners !== null) {
            $questions[] = $learners;
        }
        $teacher = self::teacher($school);
        if ($teacher !== null) {
            $questions[] = $teacher;
        }
        $term = self::term($school);
        if ($term !== null) {
            $questions[] = $term;
        }

        return ['welcome' => $welcome, 'questions' => $questions];
    }

    /**
     * @return array{id: string, text: string, answer: string}|null
     */
    public static function find(?School $school, string $id): ?array
    {
        foreach (self::forSchool($school)['questions'] as $question) {
            if ($question['id'] === $id) {
                return $question;
            }
        }

        return null;
    }

    public static function reply(?School $school, string $text): ?string
    {
        $asked = mb_strtolower(trim($text));
        if ($asked === '' || $school === null) {
            return null;
        }

        foreach (self::forSchool($school)['questions'] as $question) {
            if (mb_strtolower($question['text']) === $asked) {
                return $question['answer'];
            }
        }

        return null;
    }

    /**
     * @return array{id: string, text: string, answer: string}|null
     */
    private static function learners(School $school): ?array
    {
        $sections = Section::query()
            ->where('school_id', $school->id)
            ->whereIn('status', [1, '1'])
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name']);
        if ($sections->isEmpty()) {
            return null;
        }

        $rows = User::query()
            ->where('users.school_id', $school->id)
            ->where('users.usergroup_id', 6)
            ->where('users.status', 'active')
            ->join('student_academics', 'student_academics.user_id', '=', 'users.id')
            ->where('student_academics.school_id', $school->id)
            ->join('standards_link', 'standards_link.id', '=', 'student_academics.standardLink_id')
            ->where('standards_link.school_id', $school->id)
            ->whereIn('standards_link.section_id', $sections->pluck('id'))
            ->whereNull('standards_link.deleted_at')
            ->groupBy('standards_link.section_id')
            ->selectRaw('standards_link.section_id as section_id, count(distinct users.id) as learners')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row->section_id] = (int) $row->learners;
        }

        $best = null;
        $bestCount = 0;
        foreach ($sections as $section) {
            $count = $counts[(int) $section->id] ?? 0;
            if ($count > $bestCount && trim((string) $section->name) !== '') {
                $best = $section;
                $bestCount = $count;
            }
        }
        if ($best === null) {
            return null;
        }

        $label = trim((string) $best->name);
        $noun = $bestCount === 1 ? 'learner' : 'learners';

        return [
            'id' => 'learners',
            'text' => 'How many learners are in '.$label.'?',
            'answer' => $label.' has '.$bestCount.' '.$noun.'.',
        ];
    }

    /**
     * @return array{id: string, text: string, answer: string}|null
     */
    private static function teacher(School $school): ?array
    {
        $links = Teacherlink::query()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->limit(40)
            ->get();

        foreach ($links as $link) {
            $teacher = $link->teacher;
            $subject = $link->subject;
            $section = $link->standardLink?->section;
            if ($teacher === null || $subject === null || $section === null) {
                continue;
            }
            if ((int) $teacher->school_id !== (int) $school->id || $teacher->status !== 'active' || (int) $teacher->usergroup_id !== 5) {
                continue;
            }
            if ((int) $subject->school_id !== (int) $school->id || (int) $section->school_id !== (int) $school->id) {
                continue;
            }
            $teacherName = trim((string) $teacher->name);
            $subjectName = trim((string) $subject->name);
            $className = trim((string) $section->name);
            if ($teacherName === '' || $subjectName === '' || $className === '') {
                continue;
            }

            return [
                'id' => 'teacher',
                'text' => 'Who teaches '.$subjectName.' in '.$className.'?',
                'answer' => $teacherName.' teaches '.$subjectName.' in '.$className.'.',
            ];
        }

        return null;
    }

    /**
     * @return array{id: string, text: string, answer: string}|null
     */
    private static function term(School $school): ?array
    {
        $term = AcademicTerm::query()
            ->where('school_id', $school->id)
            ->whereNotNull('ends_on')
            ->orderByRaw("CASE WHEN status = 'current' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
        if ($term === null) {
            return null;
        }

        $label = trim((string) $term->name);
        $raw = $term->getRawOriginal('ends_on');
        if ($label === '' || $raw === null || $raw === '') {
            return null;
        }

        try {
            $date = Carbon::parse((string) $raw)->format('j F Y');
        } catch (Throwable) {
            return null;
        }

        return [
            'id' => 'term',
            'text' => 'When does '.$label.' end?',
            'answer' => $label.' ends on '.$date.'.',
        ];
    }
}
