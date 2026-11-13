<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\AcademicTerm;
use App\Models\MarksRelease;
use App\Models\Records\RecordSnapshot;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Services\Marks\MarksHistory;
use App\Services\Marks\ReleasedMarksLock;
use App\Services\Records\SnapshotHasher;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * One class sitting: who released it, whether the saved copy still matches,
 * and the activity that led there. Admins and head teachers only.
 */
class MarksHistoryController extends Controller
{
    private const VISIBLE_EVENTS = 6;

    /** @var list<int> */
    private const REOPEN_GROUPS = [1, 3, 4];

    /** @var list<string> */
    private const CLASS_KINDS = ['released', 'approved', 'resubmitted', 'returned', 'submitted', 'entered', 'reopened'];

    public function __construct(private readonly MarksHistory $history) {}

    public function show(Request $request, int $section, int $term, int $examType): View
    {
        return view('admin.marks.history', $this->page($request, $section, $term, $examType));
    }

    public function check(Request $request, int $section, int $term, int $examType): RedirectResponse
    {
        $page = $this->page($request, $section, $term, $examType);

        return redirect()
            ->route('admin.marks.history', $this->routeParams($section, $term, $examType, $request))
            ->with('record_check', ($page['check']['passed'] ?? false) ? 'passed' : 'failed');
    }

    public function reopen(Request $request, int $section, int $term, int $examType, ReleasedMarksLock $lock): RedirectResponse
    {
        $user = $this->user();
        abort_unless(in_array((int) $user->usergroup_id, self::REOPEN_GROUPS, true), 403);
        $scoped = $this->scoped($user, $section, $term, $examType);
        $release = $this->openRelease($user, $scoped['section'], $scoped['term'], $scoped['examType']);
        if ($release === null) {
            throw ValidationException::withMessages([
                'reason' => 'Only a released set can be reopened.',
            ]);
        }

        $lock->reopen($user, $release, (string) $request->input('reason', ''));

        return redirect()
            ->route('admin.marks.history', $this->routeParams($section, $term, $examType, $request))
            ->with('status', 'Reopened. Families keep seeing the released results until you release again.');
    }

    /**
     * @return array<string, mixed>
     */
    private function page(Request $request, int $sectionId, int $termId, int $examTypeId): array
    {
        $user = $this->user();
        $scoped = $this->scoped($user, $sectionId, $termId, $examTypeId);
        $section = $scoped['section'];
        $term = $scoped['term'];
        $examType = $scoped['examType'];
        $schoolId = (int) $user->school_id;

        $exams = $this->history->exams($schoolId, (int) $section->id, (int) $term->id, (int) $examType->id);
        $releases = $this->history->coveringReleases($schoolId, (int) $section->id, (int) $term->id, (int) $examType->id);
        $locked = $releases->first(fn (MarksRelease $release) => $release->status === MarksRelease::STATUS_RELEASED);
        $release = $locked ?? $releases->first();
        $tz = $this->timezone($release);

        $marks = Marks::query()
            ->where('school_id', $schoolId)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get();
        $studentIds = $marks->pluck('student_id')->map(fn ($id) => (int) $id)->unique()->values();
        $students = User::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $studentIds)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $events = $this->history->events($schoolId, (int) $section->id, (int) $term->id, (int) $examType->id);
        $subjectIds = collect($events)->pluck('subject_id')
            ->merge($marks->pluck('subject_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $subjects = Subject::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjectFilter = (int) $request->query('subject', 0);
        if ($subjectFilter > 0 && ! $subjects->contains('id', $subjectFilter)) {
            $subjectFilter = 0;
        }

        $classEvents = array_values(array_filter($events, function (array $event) use ($subjectFilter): bool {
            if (! in_array($event['kind'], self::CLASS_KINDS, true)) {
                return false;
            }
            if ($subjectFilter === 0 || $event['subject_id'] === null) {
                return true;
            }

            return (int) $event['subject_id'] === $subjectFilter;
        }));

        $learnerId = (int) $request->query('learner', 0);
        if (! $students->has($learnerId)) {
            $learnerId = $this->defaultLearner($events, $students);
        }
        $learner = $learnerId > 0 ? $students->get($learnerId) : null;
        $learnerSubject = $this->learnerSubject($subjectFilter, $events, $marks, $learner);
        $learnerMark = $this->learnerMark($marks, $learner, $learnerSubject);
        $trail = $learner === null ? [] : array_values(array_filter($events, function (array $event) use ($learner, $learnerSubject): bool {
            if (! in_array($event['kind'], ['changed', 'entered'], true)) {
                return false;
            }
            if ((int) $event['student_id'] !== (int) $learner->id) {
                return false;
            }
            if ($learnerSubject === null) {
                return true;
            }

            return (int) $event['subject_id'] === (int) $learnerSubject->id;
        }));

        $yearName = trim((string) ($term->academicYear?->name ?? ''));
        $meta = $this->meta($term->name, $yearName, $students->count(), $release, $tz);

        return [
            'section' => $section,
            'term' => $term,
            'examType' => $examType,
            'title' => trim($section->name.' · '.$examType->name),
            'locked' => $locked !== null,
            'meta' => $meta,
            'check' => $this->recordCheck($schoolId, (int) $term->id, $releases, $studentIds),
            'subjects' => $subjects,
            'subjectFilter' => $subjectFilter,
            'classEvents' => $this->present($classEvents, $tz),
            'visibleEvents' => $this->present(array_slice($classEvents, 0, self::VISIBLE_EVENTS), $tz),
            'earlierEvents' => $this->present(array_slice($classEvents, self::VISIBLE_EVENTS), $tz),
            'learner' => $learner,
            'learnerSubject' => $learnerSubject,
            'learnerMark' => $learnerMark,
            'trail' => $this->present($trail, $tz),
            'students' => $students,
            'canReopen' => $locked !== null && in_array((int) $user->usergroup_id, self::REOPEN_GROUPS, true),
            'reopenOpen' => $request->session()->get('errors')?->has('reason') === true,
        ];
    }

    /**
     * @return array{section: Section, term: AcademicTerm, examType: ExamType}
     */
    private function scoped(User $user, int $sectionId, int $termId, int $examTypeId): array
    {
        $schoolId = (int) $user->school_id;
        $section = Section::query()->where('school_id', $schoolId)->whereKey($sectionId)->first();
        $term = AcademicTerm::query()->where('school_id', $schoolId)->whereKey($termId)->first();
        $examType = ExamType::query()->visibleToSchool($schoolId)->whereKey($examTypeId)->first();
        abort_if($section === null || $term === null || $examType === null, 404);

        return ['section' => $section, 'term' => $term, 'examType' => $examType];
    }

    private function user(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function openRelease(User $user, Section $section, AcademicTerm $term, ExamType $examType): ?MarksRelease
    {
        return $this->history->coveringReleases((int) $user->school_id, (int) $section->id, (int) $term->id, (int) $examType->id)
            ->first(fn (MarksRelease $release) => $release->status === MarksRelease::STATUS_RELEASED);
    }

    /**
     * @param  Collection<int, int>  $studentIds
     * @param  Collection<int, MarksRelease>  $releases
     * @return array{show: bool, passed: bool, hashes: list<string>}
     */
    private function recordCheck(int $schoolId, int $termId, Collection $releases, Collection $studentIds): array
    {
        if ($releases->isEmpty() || $studentIds->isEmpty()) {
            return ['show' => false, 'passed' => false, 'hashes' => []];
        }

        $snapshots = RecordSnapshot::query()
            ->where('school_id', $schoolId)
            ->where('academic_term_id', $termId)
            ->whereIn('student_id', $studentIds->all())
            ->whereIn('release_id', $releases->pluck('id'))
            ->orderBy('id')
            ->get();

        if ($snapshots->isEmpty()) {
            return ['show' => false, 'passed' => false, 'hashes' => []];
        }

        $passed = $snapshots->every(fn (RecordSnapshot $row) => $row->contentMatches());
        $hashes = $snapshots
            ->pluck('content_hash')
            ->filter(fn ($hash) => is_string($hash) && $hash !== '')
            ->unique()
            ->map(fn (string $hash) => SnapshotHasher::grouped($hash))
            ->filter(fn (string $hash) => $hash !== '')
            ->values()
            ->all();

        return ['show' => true, 'passed' => $passed, 'hashes' => $hashes];
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @param  Collection<int, User>  $students
     */
    private function defaultLearner(array $events, Collection $students): int
    {
        foreach ($events as $event) {
            $studentId = (int) ($event['student_id'] ?? 0);
            if ($studentId > 0 && $students->has($studentId)) {
                return $studentId;
            }
        }

        $first = $students->first();

        return $first instanceof User ? (int) $first->id : 0;
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @param  Collection<int, Marks>  $marks
     */
    private function learnerSubject(int $subjectFilter, array $events, Collection $marks, ?User $learner): ?Subject
    {
        if ($learner === null) {
            return null;
        }
        $subjectId = $subjectFilter;
        if ($subjectId === 0) {
            foreach ($events as $event) {
                if ((int) ($event['student_id'] ?? 0) === (int) $learner->id && in_array($event['kind'], ['changed', 'entered'], true)) {
                    $subjectId = (int) ($event['subject_id'] ?? 0);
                    if ($subjectId > 0) {
                        break;
                    }
                }
            }
        }
        if ($subjectId === 0) {
            $mark = $marks->first(fn (Marks $row) => (int) $row->student_id === (int) $learner->id);
            $subjectId = (int) ($mark?->subject_id ?? 0);
        }
        if ($subjectId === 0) {
            return null;
        }

        return Subject::query()
            ->where('school_id', $learner->school_id)
            ->whereKey($subjectId)
            ->first();
    }

    /**
     * @param  Collection<int, Marks>  $marks
     */
    private function learnerMark(Collection $marks, ?User $learner, ?Subject $subject): ?string
    {
        if ($learner === null) {
            return null;
        }
        $row = $marks->first(function (Marks $mark) use ($learner, $subject): bool {
            if ((int) $mark->student_id !== (int) $learner->id) {
                return false;
            }

            return $subject === null || (int) $mark->subject_id === (int) $subject->id;
        });
        if ($row === null) {
            return null;
        }
        if ($row->isAbsent()) {
            return 'ABS';
        }
        if ($row->marks === null || $row->marks === '') {
            return null;
        }
        $number = (float) $row->marks;
        if (abs($number - round($number)) < 0.00001) {
            return (string) (int) round($number);
        }

        return rtrim(rtrim(sprintf('%.2F', $number), '0'), '.');
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return list<array<string, mixed>>
     */
    private function present(array $events, string $tz): array
    {
        return array_map(function (array $event) use ($tz): array {
            $at = $event['at'];
            $event['when'] = $at instanceof CarbonInterface
                ? $at->copy()->timezone($tz)->format('j M Y, H:i')
                : '';
            $who = trim((string) $event['who']);
            $role = $event['role'];
            if ($who !== '' && is_string($role) && $role !== '') {
                $event['by'] = $who.' · '.$role;
            } else {
                $event['by'] = $who !== '' ? $who : (is_string($role) ? $role : '');
            }
            $event['change'] = $this->change($event);

            return $event;
        }, $events);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function change(array $event): ?string
    {
        $new = $event['new'] ?? null;
        $old = $event['old'] ?? null;
        if (! is_string($new) || $new === '') {
            return null;
        }
        if ($event['kind'] === 'changed' && is_string($old) && $old !== '') {
            return $old.' → '.$new;
        }
        if ($event['kind'] === 'entered') {
            return $new;
        }

        return null;
    }

    private function meta(string $term, string $year, int $learners, ?MarksRelease $release, string $tz): string
    {
        $termBit = trim($term);
        if ($year !== '') {
            $termBit = $termBit === '' ? $year : $termBit.', '.$year;
        }
        $parts = [];
        if ($termBit !== '') {
            $parts[] = $termBit;
        }
        $parts[] = $learners.' '.($learners === 1 ? 'learner' : 'learners');
        if ($release?->released_at instanceof CarbonInterface) {
            $when = $release->released_at->copy()->timezone($tz);
            $clause = 'released '.$when->format('j M Y').' at '.$when->format('H:i');
            $actor = $release->created_by
                ? User::query()->where('school_id', $release->school_id)->whereKey($release->created_by)->first()
                : null;
            $name = trim((string) ($actor?->name ?? ''));
            if ($name !== '') {
                $clause .= ' by '.$name;
                $role = match ((int) ($actor?->usergroup_id ?? 0)) {
                    1, 3 => 'Admin',
                    4 => 'Head teacher',
                    5 => 'Teacher',
                    default => null,
                };
                if ($role !== null) {
                    $clause .= ' ('.$role.')';
                }
            }
            $parts[] = $clause;
        }

        return implode(' · ', $parts);
    }

    private function timezone(?MarksRelease $release): string
    {
        $tz = trim((string) ($release?->timezone ?? ''));
        if ($tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
            return $tz;
        }

        return 'Africa/Kampala';
    }

    /**
     * @return array<string, mixed>
     */
    private function routeParams(int $section, int $term, int $examType, Request $request): array
    {
        return array_filter([
            'section' => $section,
            'term' => $term,
            'examType' => $examType,
            'subject' => $request->query('subject'),
            'learner' => $request->query('learner'),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
