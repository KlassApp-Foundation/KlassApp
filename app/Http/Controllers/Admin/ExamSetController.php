<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\ExamSet;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Teacherlink;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Services\Academics\ExamSetService;
use App\Services\ExamAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * One page that creates a term's exam set: term, exam types, and the classes
 * (or the whole school). Classes are chips grouped by level (standards.order);
 * a class's streams sit behind a <details> so the page stays short until the
 * admin asks for them. Admins see every class; a class teacher sees only the
 * classes they head, and only those are accepted on submit.
 */
class ExamSetController extends Controller
{
    public function __construct(
        private ExamSetService $sets,
        private ExamAuthorization $authorization,
    ) {
    }

    public function create(Request $request)
    {
        [$user, $school, $year, $isTeacher] = $this->context();
        $classes = $this->classes($user, $school, $year, $isTeacher);

        return view('admin.exams.exam-set-create', [
            'school' => $school,
            'year' => $year,
            'terms' => AcademicTerm::query()
                ->where('school_id', $school->id)
                ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
                ->orderBy('starts_on')
                ->get(),
            'types' => ExamType::visibleToSchool($school->id)->orderBy('id')->get(),
            'defaultTypeIds' => $this->defaultTypeIds($school),
            'classGroups' => $this->classGroups($school, $year, $classes),
            'isTeacher' => $isTeacher,
            'layout' => $isTeacher ? 'layouts.teacher.layout' : 'layouts.admin.layout',
            'action' => $isTeacher ? route('teacher.exam-sets.store') : route('admin.exam-sets.store'),
            'selectedTermId' => (int) $request->get('term', $this->currentTermId($school)),
        ]);
    }

    public function store(Request $request)
    {
        [$user, $school, $year, $isTeacher] = $this->context();

        $data = $request->validate([
            'academic_term_id' => ['required', 'integer'],
            'type_ids' => ['required', 'array', 'min:1'],
            'type_ids.*' => ['integer'],
            'whole_school' => ['nullable', 'boolean'],
            'standard_ids' => ['nullable', 'array'],
            'standard_ids.*' => ['integer'],
            'stream_ids' => ['nullable', 'array'],
            'stream_ids.*' => ['integer'],
        ]);

        $term = AcademicTerm::query()
            ->where('school_id', $school->id)
            ->findOrFail((int) $data['academic_term_id']);

        // Only types this school can see: shared rows or its own.
        $typeIds = ExamType::visibleToSchool($school->id)
            ->whereIn('id', array_map('intval', $data['type_ids']))
            ->pluck('id')
            ->all();

        if ($typeIds === []) {
            return back()->withInput()->withErrors(['type_ids' => 'Choose at least one exam type.']);
        }

        $classes = $this->classes($user, $school, $year, $isTeacher);
        $allowed = $classes->pluck('id')->map(fn ($id) => (int) $id)->all();
        $wholeSchool = ! $isTeacher && (bool) ($data['whole_school'] ?? false);
        $classIds = $wholeSchool
            ? $this->sets->baseSectionIds($school)
            : array_values(array_intersect(array_map('intval', $data['standard_ids'] ?? []), $allowed));

        if (! $wholeSchool && $classIds === []) {
            return back()->withInput()->withErrors(['standard_ids' => 'Choose at least one class, or the whole school.']);
        }

        // A stream gets its own exam only when the admin expanded the class
        // and picked it explicitly; a class teacher may only pick streams of
        // the classes they head.
        $streamIds = array_values(array_intersect(
            array_map('intval', $data['stream_ids'] ?? []),
            $this->streamsFor($school, $classes)->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ));

        $result = $this->sets->create($school, $term, $typeIds, array_values(array_unique([...$classIds, ...$streamIds])), $user);

        $message = $result['created'].' exam'.($result['created'] === 1 ? '' : 's').' created';
        if ($result['skipped'] > 0) {
            $message .= ', '.$result['skipped'].' already existed';
        }
        $message .= ' for '.$term->name.'.';

        return redirect()
            ->route($isTeacher ? 'teacher.exam-sets.show' : 'admin.exam-sets.show', ['batch' => $result['set_id']])
            ->with('successmessage', $message);
    }

    public function index()
    {
        return $this->render(null);
    }

    public function show(int $batch)
    {
        [$user, $school, , $isTeacher] = $this->context();

        return $this->render($this->visibleSet($school, $user, $isTeacher, $batch));
    }

    public function destroyExam(int $batch, int $exam)
    {
        [$user, $school, , $isTeacher] = $this->context();
        if ($isTeacher) {
            abort(403);
        }

        $set = $this->visibleSet($school, $user, false, $batch);
        $row = Exam::query()
            ->where('school_id', $school->id)
            ->where('exam_set_id', $set->id)
            ->whereKey($exam)
            ->firstOrFail();

        $hasMarks = Marks::query()
            ->where('exam_id', $row->id)
            ->where('school_id', $school->id)
            ->whereNotNull('marks')
            ->exists();

        if ($hasMarks) {
            abort(422, 'This exam has marks and cannot be removed.');
        }

        $row->delete();

        return redirect()
            ->route('admin.exam-sets.show', ['batch' => $set->id])
            ->with('successmessage', 'Exam removed.');
    }

    private function render(?ExamSet $set)
    {
        [$user, $school, $year, $isTeacher] = $this->context();
        $sets = $this->setsFor($school, $user, $isTeacher, $year);
        $selectedYear = request()->filled('year') ? (int) request('year') : (int) ($year->id ?? 0);
        $listed = $selectedYear > 0
            ? $sets->filter(fn (ExamSet $row) => (int) $row->academic_year_id === $selectedYear)->values()
            : $sets;
        $cards = $this->setCards($school, $listed);
        $byTerm = $listed->groupBy(fn (ExamSet $row) => (string) ($row->term->name ?? 'Term'));
        $detail = $set ? $this->presentSet($school, $set) : null;

        return view('admin.exams.exam-set-show', [
            'set' => $set,
            'detail' => $detail,
            'examCount' => $detail['examCount'] ?? 0,
            'cards' => $cards,
            'byTerm' => $byTerm,
            'years' => AcademicYear::query()->where('school_id', $school->id)->orderByDesc('name')->get(),
            'selectedYear' => $selectedYear,
            'currentTermName' => (string) (AcademicTerm::query()->where('school_id', $school->id)->where('status', 'current')->value('name') ?? ''),
            'canRemove' => ! $isTeacher,
            'isTeacher' => $isTeacher,
            'layout' => $isTeacher ? 'layouts.teacher.layout' : 'layouts.admin.layout',
            'showRoute' => $isTeacher ? 'teacher.exam-sets.show' : 'admin.exam-sets.show',
            'indexRoute' => $isTeacher ? 'teacher.exam-sets.index' : 'admin.exam-sets.index',
            'createRoute' => $isTeacher ? 'teacher.exam-sets.create' : 'admin.exam-sets.create',
            'filterClass' => request()->filled('class') ? (int) request('class') : null,
            'filterType' => request()->filled('type') ? (int) request('type') : null,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ExamSet>  $sets
     * @return array<int, array{classes:int, types:list<string>, exams:int, marked:int}>
     */
    private function setCards(School $school, $sets): array
    {
        if ($sets->isEmpty()) {
            return [];
        }

        $exams = Exam::query()
            ->with('examType')
            ->where('school_id', $school->id)
            ->whereIn('exam_set_id', $sets->pluck('id'))
            ->get(['id', 'exam_set_id', 'section_id', 'exam_type_id']);
        $marked = Marks::query()
            ->where('school_id', $school->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->whereNotNull('marks')
            ->pluck('exam_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $cards = [];
        foreach ($sets as $row) {
            $mine = $exams->where('exam_set_id', $row->id);
            $cards[$row->id] = [
                'classes' => $mine->pluck('section_id')->unique()->count(),
                'types' => $mine->map(fn ($exam) => (string) ($exam->examType->name ?? 'Exam'))->unique()->values()->all(),
                'exams' => $mine->count(),
                'marked' => $mine->filter(fn ($exam) => in_array((int) $exam->id, $marked, true))->count(),
            ];
        }

        return $cards;
    }

    /** @return array<string, mixed> */
    private function presentSet(School $school, ExamSet $set): array
    {
        $exams = Exam::query()
            ->with(['section', 'examType', 'subject', 'teacher'])
            ->where('school_id', $school->id)
            ->where('exam_set_id', $set->id)
            ->orderBy('section_id')
            ->orderBy('subject_id')
            ->orderBy('exam_type_id')
            ->get();
        $marked = Marks::query()
            ->where('school_id', $school->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->whereNotNull('marks')
            ->pluck('exam_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $submissions = ExamMarksSubmission::query()
            ->where('school_id', $school->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get()
            ->keyBy(fn (ExamMarksSubmission $row) => $row->exam_id.'|'.$row->class_id.'|'.$row->subject_id);
        $linkedSubjects = Teacherlink::query()
            ->where('school_id', $school->id)
            ->whereIn('subject_id', $exams->pluck('subject_id'))
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $filterClass = request()->filled('class') ? (int) request('class') : null;
        $filterType = request()->filled('type') ? (int) request('type') : null;
        $types = [];
        $classes = [];
        $noTeacher = 0;
        $seenNoTeacher = [];

        foreach ($exams as $exam) {
            $typeId = (int) $exam->exam_type_id;
            $types[$typeId] = (string) ($exam->examType->name ?? 'Exam');
            if ($filterClass !== null && (int) $exam->section_id !== $filterClass) {
                continue;
            }
            if ($filterType !== null && $typeId !== $filterType) {
                continue;
            }
            $classId = (int) $exam->section_id;
            $subjectId = (int) $exam->subject_id;
            $hasLink = in_array($subjectId, $linkedSubjects, true);
            if (! $hasLink && ! isset($seenNoTeacher[$subjectId])) {
                $seenNoTeacher[$subjectId] = true;
                $noTeacher++;
            }
            $submission = $submissions->get($exam->id.'|'.$exam->section_id.'|'.$exam->subject_id);
            $hasMarks = in_array((int) $exam->id, $marked, true);
            $label = $submission ? $submission->reviewLabel() : ($hasMarks ? 'In progress' : 'No marks');
            if ($label === 'Not started' && ! $hasMarks) {
                $label = 'No marks';
            }
            $classes[$classId]['name'] = (string) ($exam->section->name ?? 'Class');
            $classes[$classId]['count'] = ($classes[$classId]['count'] ?? 0) + 1;
            $classes[$classId]['rows'][$subjectId]['subject'] = (string) ($exam->subject->name ?? 'Subject');
            $classes[$classId]['rows'][$subjectId]['teacher'] = $hasLink ? (string) ($exam->teacher->name ?? 'Unassigned') : '';
            $classes[$classId]['rows'][$subjectId]['cells'][$typeId] = [
                'id' => (int) $exam->id,
                'has_marks' => $hasMarks,
                'label' => $label,
                'teacher' => (string) ($exam->teacher->name ?? 'Unassigned'),
            ];
        }

        return [
            'examCount' => $exams->count(),
            'markedCount' => count(array_unique($marked)),
            'noTeacher' => $noTeacher,
            'classCount' => $exams->pluck('section_id')->unique()->count(),
            'types' => $types,
            'classOptions' => $exams->mapWithKeys(fn (Exam $exam) => [(int) $exam->section_id => (string) ($exam->section->name ?? 'Class')])->all(),
            'classes' => $classes,
            'creator' => (string) ($set->creator?->name ?? ''),
        ];
    }

    private function visibleSet(School $school, User $user, bool $isTeacher, int $batch): ExamSet
    {
        $year = AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('status', 1)
            ->first();
        $visible = $this->setsFor($school, $user, $isTeacher, $year)->firstWhere('id', $batch);
        if (! $visible) {
            abort(404);
        }

        return $visible;
    }

    private function setsFor(School $school, User $user, bool $isTeacher, ?AcademicYear $year)
    {
        $query = ExamSet::query()
            ->with(['term', 'creator'])
            ->withCount('exams')
            ->where('school_id', $school->id)
            ->orderByDesc('id');

        if ($isTeacher) {
            $sectionIds = $year
                ? $this->authorization->sectionIdsForClassTeacher($user, (int) $school->id, (int) $year->id)
                : [];
            $query->where(function ($q) use ($user, $sectionIds) {
                $q->where('created_by', $user->id);
                if ($sectionIds !== []) {
                    $q->orWhereHas('exams', fn ($exams) => $exams->where('school_id', $user->school_id)->whereIn('section_id', $sectionIds));
                }
            });
        }

        return $query->get();
    }

    /** @return array{0: User, 1: School, 2: ?AcademicYear, 3: bool} */
    private function context(): array
    {
        $user = Auth::user();
        $school = School::findOrFail((int) $user->school_id);
        $year = AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('status', 1)
            ->first();

        return [$user, $school, $year, (int) $user->usergroup_id === 5];
    }

    /** @return Collection<int, Section> */
    private function classes(User $user, School $school, ?AcademicYear $year, bool $isTeacher)
    {
        $base = $this->sets->baseSections($school);

        if (! $isTeacher) {
            return $base;
        }

        $sectionIds = $year ? $this->authorization->sectionIdsForClassTeacher($user, (int) $school->id, (int) $year->id) : [];

        return $base->filter(fn ($section) => in_array($section->id, $sectionIds, true))->values();
    }

    /**
     * Classes grouped by the standard they belong to this year, ordered by
     * standards.order (nursery before primary before o-level). Each class
     * carries its own streams, so the blade can put them behind one <details>.
     *
     * @param  Collection<int, Section>  $classes
     * @return list<array{key: string, label: string, classes: list<array{id: int, name: string, streams: list<array{id: int, name: string, label: string}>}>}>
     */
    private function classGroups(School $school, ?AcademicYear $year, Collection $classes): array
    {
        if ($classes->isEmpty()) {
            return [];
        }

        // standards_link is where a section's level lives; its id keeps the
        // class order the school set up (Primary One before Primary Two).
        $links = StandardLink::query()
            ->where('school_id', $school->id)
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->whereIn('section_id', $classes->pluck('id')->map(fn ($id) => (int) $id)->all())
            ->orderBy('id')
            ->get(['id', 'section_id', 'standard_id']);

        $standardOf = [];
        $linkOrder = [];
        foreach ($links as $link) {
            $sectionId = (int) $link->section_id;
            if (isset($standardOf[$sectionId])) {
                continue;
            }
            $standardOf[$sectionId] = (int) $link->standard_id;
            $linkOrder[$sectionId] = (int) $link->id;
        }

        $standards = Standard::query()
            ->where('school_id', $school->id)
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $streamsByBase = $this->streamsFor($school, $classes)->groupBy(
            fn (Section $stream) => $this->baseIdFor((string) $stream->name, $classes)
        );

        /** @var array<string, array{key: string, label: string, order: int, classes: list<array>}> $groups */
        $groups = [];

        foreach ($classes as $class) {
            $classId = (int) $class->id;
            $standard = $standards->get($standardOf[$classId] ?? 0);
            $key = $standard ? 'level-'.$standard->id : 'other';
            $order = $standard ? (int) ($standard->order ?? 0) : PHP_INT_MAX;

            $groups[$key] ??= [
                'key' => $key,
                'label' => $standard ? ucfirst((string) $standard->name) : 'Other classes',
                'order' => $order,
                'classes' => [],
            ];

            $groups[$key]['classes'][] = [
                'id' => $classId,
                'name' => (string) $class->name,
                'sort' => $linkOrder[$classId] ?? PHP_INT_MAX,
                'streams' => $streamsByBase->get($classId, collect())
                    ->map(fn (Section $stream) => [
                        'id' => (int) $stream->id,
                        'name' => (string) $stream->name,
                        'label' => $this->streamLabel((string) $class->name, (string) $stream->name),
                    ])
                    ->values()
                    ->all(),
            ];
        }

        $groups = array_values($groups);
        usort($groups, fn ($a, $b) => [$a['order'], $a['classes'][0]['sort']] <=> [$b['order'], $b['classes'][0]['sort']]);

        return array_map(function (array $group) {
            usort($group['classes'], fn ($a, $b) => [$a['sort'], $a['id']] <=> [$b['sort'], $b['id']]);
            $group['classes'] = array_map(function (array $class) {
                unset($class['sort']);

                return $class;
            }, $group['classes']);

            return $group;
        }, $groups);
    }

    /**
     * The streams of the given classes: sections named "{class} {label}".
     *
     * @param  Collection<int, Section>  $classes
     * @return Collection<int, Section>
     */
    private function streamsFor(School $school, Collection $classes)
    {
        if ($classes->isEmpty()) {
            return collect();
        }

        return Section::query()
            ->where('school_id', $school->id)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn (Section $section) => $this->baseIdFor((string) $section->name, $classes) !== null)
            ->values();
    }

    /** The class a section belongs to, by name prefix; null when it is a class itself. */
    private function baseIdFor(string $sectionName, Collection $classes): ?int
    {
        $match = null;
        $matchLength = 0;

        foreach ($classes as $class) {
            $prefix = trim((string) $class->name).' ';
            if ($sectionName !== trim((string) $class->name) && str_starts_with($sectionName, $prefix) && strlen($prefix) > $matchLength) {
                $match = (int) $class->id;
                $matchLength = strlen($prefix);
            }
        }

        return $match;
    }

    private function streamLabel(string $className, string $streamName): string
    {
        $prefix = trim($className).' ';

        return str_starts_with($streamName, $prefix) ? trim(substr($streamName, strlen($prefix))) : $streamName;
    }

    private function currentTermId(School $school): int
    {
        $current = AcademicTerm::query()
            ->where('school_id', $school->id)
            ->where('status', 'current')
            ->orderBy('starts_on')
            ->value('id');

        return (int) ($current ?? AcademicTerm::query()->where('school_id', $school->id)->orderBy('starts_on')->value('id'));
    }

    /**
     * Types checked by default: the school's own toggles when it has any,
     * otherwise every visible type.
     *
     * @return list<int>
     */
    private function defaultTypeIds(School $school): array
    {
        $types = ExamType::visibleToSchool($school->id)->orderBy('id')->get();
        $prefs = $school->exam_type_preferences;

        if (is_array($prefs) && $prefs !== []) {
            return $types->filter(function (ExamType $type) use ($prefs) {
                $value = $prefs[(string) $type->id] ?? $prefs[$type->id] ?? null;

                return $value !== null && filter_var($value, FILTER_VALIDATE_BOOLEAN);
            })->pluck('id')->all();
        }

        return $types->pluck('id')->all();
    }
}
