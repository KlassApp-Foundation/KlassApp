<?php

namespace App\Services\Teacher;

use App\Helpers\SiteHelper;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\Attendance;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Teacherlink;
use App\Models\User;
use App\Support\AttendanceDays;
use App\Support\DashboardGreeting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * School-scoped teacher home. Parents and students are not involved.
 */
final class TeacherHomeBoard
{
    /**
     * @return array<string, mixed>
     */
    public function forTeacher(User $teacher): array
    {
        $schoolId = (int) $teacher->school_id;
        $year = $schoolId > 0 ? SiteHelper::getAcademicYear($schoolId) : null;
        $yearId = $year ? (int) $year->id : 0;
        $links = $this->classes($teacher, $schoolId, $yearId);
        $head = $links->first(fn (StandardLink $link) => (int) $link->class_teacher_id === (int) $teacher->id) ?: $links->first();
        $studentIds = $this->studentIds($schoolId, $yearId, $links->pluck('id')->all());
        $attention = $this->attention($teacher, $schoolId, $links);
        $greeting = DashboardGreeting::for($teacher, 'Teacher');

        return [
            'greeting' => $greeting['phrase'].', '.$greeting['name'],
            'dateline' => now()->timezone('Africa/Kampala')->format('l j F'),
            'role_line' => $head && (int) $head->class_teacher_id === (int) $teacher->id
                ? 'class head, '.$this->className($head)
                : 'teacher',
            'head_class' => $head ? $this->className($head) : null,
            'class_count' => $links->count(),
            'class_names' => $links->map(fn (StandardLink $link) => $this->className($link))->unique()->values()->all(),
            'student_count' => count($studentIds),
            'to_mark' => $attention['to_mark'],
            'returned' => $attention['returned'],
            'rows' => $attention['rows'],
            'subjects' => $head ? $this->subjectAverages($schoolId, $head) : [],
            'week' => $head ? $this->week($schoolId, $yearId, (int) $head->id) : [],
            'week_total' => $head ? count($this->studentIds($schoolId, $yearId, [(int) $head->id])) : 0,
            'streams' => $this->streams($schoolId, $yearId, $links),
            'empty' => $links->isEmpty(),
            'attendance_url' => route('teacher.attendance.create'),
            'marks_url' => route('teacher.exam.marks'),
        ];
    }

    /**
     * @return Collection<int, StandardLink>
     */
    private function classes(User $teacher, int $schoolId, int $yearId): Collection
    {
        if ($schoolId <= 0 || $yearId <= 0) {
            return collect();
        }

        $taught = Teacherlink::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacher->id)
            ->where('academic_year_id', $yearId)
            ->pluck('standardLink_id');

        return StandardLink::query()
            ->where('school_id', $schoolId)
            ->where(function ($query) use ($teacher, $taught) {
                $query->where('class_teacher_id', $teacher->id);
                if ($taught->isNotEmpty()) {
                    $query->orWhereIn('id', $taught);
                }
            })
            ->with(['standard', 'section'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<int>  $linkIds
     * @return list<int>
     */
    private function studentIds(int $schoolId, int $yearId, array $linkIds): array
    {
        if ($linkIds === [] || $yearId <= 0) {
            return [];
        }

        return StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->whereIn('standardLink_id', $linkIds)
            ->whereHas('user', fn ($query) => $query->where('school_id', $schoolId)->where('status', 'active'))
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  Collection<int, StandardLink>  $links
     * @return array{to_mark: int, returned: int, rows: list<array<string, mixed>>}
     */
    private function attention(User $teacher, int $schoolId, Collection $links): array
    {
        $linkIds = $links->pluck('id')->all();
        if ($linkIds === []) {
            return ['to_mark' => 0, 'returned' => 0, 'rows' => []];
        }

        $exams = Exam::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacher->id)
            ->whereIn('section_id', $links->pluck('section_id')->filter()->all())
            ->with(['subject', 'examType', 'section'])
            ->orderBy('id')
            ->get();

        $rows = [];
        $toMark = 0;
        $returned = 0;
        foreach ($exams as $exam) {
            $submission = ExamMarksSubmission::query()
                ->where('school_id', $schoolId)
                ->where('exam_id', $exam->id)
                ->first();
            $entered = (int) Marks::query()
                ->where('school_id', $schoolId)
                ->where('exam_id', $exam->id)
                ->whereNotNull('marks')
                ->distinct()
                ->count('student_id');
            $link = $links->first(fn (StandardLink $item) => (int) $item->section_id === (int) $exam->section_id);
            $expected = $link ? count($this->studentIds($schoolId, (int) $exam->academic_year_id, [(int) $link->id])) : 0;
            $title = trim(($link ? $this->className($link) : (string) $exam->section?->name).' '.($exam->subject?->name ?? '').' · '.($exam->examType?->name ?? 'Exam'));

            if ($submission && $submission->approval_status === 'rejected') {
                $returned++;
                $rows[] = [
                    'state' => 'Returned',
                    'title' => $title,
                    'detail' => $submission->rejection_reason ? '"'.$submission->rejection_reason.'"' : 'Returned for fixing.',
                    'action' => 'Fix and resubmit',
                    'url' => route('teacher.exam.marks.enter', $exam),
                ];
                continue;
            }
            if ($submission && $submission->approval_status === 'approved') {
                continue;
            }
            if ($entered === 0) {
                $toMark++;
                $rows[] = [
                    'state' => 'Not started',
                    'title' => $title,
                    'detail' => '0 of '.$expected.' entered',
                    'action' => 'Enter marks',
                    'url' => route('teacher.exam.marks.enter', $exam),
                ];
                continue;
            }
            $toMark++;
            $rows[] = [
                'state' => 'In progress',
                'title' => $title,
                'detail' => $entered.' of '.$expected.' entered',
                'action' => 'Continue',
                'url' => route('teacher.exam.marks.enter', $exam),
            ];
        }

        return ['to_mark' => $toMark, 'returned' => $returned, 'rows' => $rows];
    }

    /**
     * @return list<array{name: string, average: int|null}>
     */
    private function subjectAverages(int $schoolId, StandardLink $link): array
    {
        $marks = Marks::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $link->section_id)
            ->whereNotNull('marks')
            ->with('subject')
            ->get()
            ->groupBy(fn (Marks $mark) => $mark->subject?->name ?? 'Subject');

        $rows = [];
        foreach ($marks as $name => $group) {
            $rows[] = [
                'name' => (string) $name,
                'average' => (int) round($group->avg('marks')),
            ];
        }

        return $rows;
    }

    /**
     * Present students each weekday. Two sessions on one date count once.
     *
     * @return list<array{label: string, present: int}>
     */
    private function week(int $schoolId, int $yearId, int $linkId): array
    {
        $ids = $this->studentIds($schoolId, $yearId, [$linkId]);
        $start = Carbon::now()->startOfWeek();
        $rows = [];
        foreach (range(0, 4) as $offset) {
            $day = $start->copy()->addDays($offset);
            $present = 0.0;
            if ($ids !== []) {
                $records = Attendance::query()
                    ->where('school_id', $schoolId)
                    ->whereIn('user_id', $ids)
                    ->whereDate('date', $day->toDateString())
                    ->get(['user_id', 'date', 'status']);
                foreach ($records->groupBy('user_id') as $studentRows) {
                    $present += AttendanceDays::present($studentRows);
                }
            }
            $rows[] = [
                'label' => $day->format('D'),
                'present' => $present,
                'present_label' => AttendanceDays::mark($present),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, StandardLink>  $links
     * @return list<array{name: string, total: int, girls: int, boys: int}>
     */
    private function streams(int $schoolId, int $yearId, Collection $links): array
    {
        $rows = [];
        foreach ($links as $link) {
            $ids = $this->studentIds($schoolId, $yearId, [(int) $link->id]);
            $profiles = $ids === [] ? collect() : User::query()
                ->where('school_id', $schoolId)
                ->whereIn('id', $ids)
                ->where('status', 'active')
                ->with('userprofile')
                ->get();
            $girls = $profiles->filter(fn (User $user) => $this->isGirl($user))->count();
            $boys = $profiles->filter(fn (User $user) => $this->isBoy($user))->count();
            $rows[] = [
                'name' => $this->className($link),
                'total' => count($ids),
                'girls' => $girls,
                'boys' => $boys,
            ];
        }

        return $rows;
    }

    private function isGirl(User $user): bool
    {
        $gender = mb_strtolower(trim((string) $user->userprofile?->gender));

        return in_array($gender, ['f', 'female', 'girl', 'woman'], true);
    }

    private function isBoy(User $user): bool
    {
        $gender = mb_strtolower(trim((string) $user->userprofile?->gender));

        return in_array($gender, ['m', 'male', 'boy', 'man'], true);
    }

    private function className(StandardLink $link): string
    {
        $name = trim((string) ($link->StandardSection ?? ''));

        return $name !== '' ? $name : trim((string) ($link->section?->name ?? 'Class'));
    }
}
