<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\ExamType;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use App\Services\Academics\ExamSetService;
use App\Services\ExamAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * One page that creates a term's exam set: term, exam types, and the classes
 * (or the whole school). Admins see every class; a class teacher sees only the
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
            'sections' => $this->sections($user, $school, $year, $isTeacher),
            'isTeacher' => $isTeacher,
            'layout' => $isTeacher ? 'layouts.teacher.layout' : 'layouts.admin.layout',
            'action' => $isTeacher ? route('teacher.exam-sets.store') : route('admin.exam-sets.store'),
            'selectedTermId' => (int) $request->get('term', 0),
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
            'section_ids' => ['nullable', 'array'],
            'section_ids.*' => ['integer'],
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

        $allowed = $this->sections($user, $school, $year, $isTeacher)->pluck('id')->all();
        $wholeSchool = ! $isTeacher && (bool) ($data['whole_school'] ?? false);
        $sectionIds = $wholeSchool
            ? null
            : array_values(array_intersect(array_map('intval', $data['section_ids'] ?? []), $allowed));

        if (! $wholeSchool && $sectionIds === []) {
            return back()->withInput()->withErrors(['section_ids' => 'Choose at least one class, or the whole school.']);
        }

        $result = $this->sets->create($school, $term, $typeIds, $sectionIds, $user);

        $message = $result['created'].' exam'.($result['created'] === 1 ? '' : 's').' created';
        if ($result['skipped'] > 0) {
            $message .= ', '.$result['skipped'].' already existed';
        }
        $message .= ' for '.$term->name.'.';

        return redirect()
            ->route($isTeacher ? 'teacher.exam.marks' : 'admin.marks')
            ->with('successmessage', $message);
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

    private function sections(User $user, School $school, ?AcademicYear $year, bool $isTeacher)
    {
        $query = Section::query()
            ->where('school_id', $school->id)
            ->where('status', 1);

        if ($isTeacher) {
            $ids = $year ? $this->authorization->sectionIdsForClassTeacher($user, (int) $school->id, (int) $year->id) : [];
            $query->whereIn('id', $ids);
        }

        return $query->orderBy('name')->get();
    }

    /** @return list<int> */
    private function defaultTypeIds(School $school): array
    {
        $types = ExamType::visibleToSchool($school->id)->orderBy('id')->get();
        $prefs = $school->exam_type_preferences;

        if (is_array($prefs) && $prefs !== []) {
            $ids = $types->filter(fn ($type) => (bool) ($prefs[(string) $type->id] ?? $prefs[$type->id] ?? false))
                ->pluck('id')
                ->all();

            if ($ids !== []) {
                return $ids;
            }
        }

        return $types->pluck('id')->all();
    }
}
