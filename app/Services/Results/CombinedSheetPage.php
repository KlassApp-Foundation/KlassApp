<?php

namespace App\Services\Results;

use App\Exports\SittingSheetExport;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The one combined sheet for a sitting: filters, the ResultsService
 * sheet, and the download of that same sheet. No totals are worked
 * out here.
 */
final class CombinedSheetPage
{
    private const PAGE_SIZE = 20;

    /** @var list<string> */
    private const EXAM_SET_NAMES = ['beginning of term', 'mid term', 'end of term'];

    public function __construct(private readonly ResultsService $results)
    {
    }

    /**
     * Classes this teacher may open: a class they hold, a stream they
     * hold, or a class where they have an exam.
     *
     * @return list<int>
     */
    public function sectionIdsFor(User $teacher): array
    {
        $schoolId = (int) $teacher->school_id;

        $fromSection = Section::query()
            ->where('school_id', $schoolId)
            ->where('class_teacher_id', $teacher->id)
            ->pluck('id');

        $fromLink = StandardLink::query()
            ->where('school_id', $schoolId)
            ->where('class_teacher_id', $teacher->id)
            ->pluck('section_id');

        $fromExam = Exam::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacher->id)
            ->pluck('section_id');

        return $fromSection
            ->merge($fromLink)
            ->merge($fromExam)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param list<int>|null $allowedSectionIds null means every class in the school
     * @return array<string, mixed>
     */
    public function viewData(
        int $schoolId,
        Request $request,
        ?array $allowedSectionIds = null,
        string $filterRoute = 'admin.marks.filter',
        string $downloadRoute = 'admin.marksheet.download',
        bool $showReportLink = true,
    ): array {
        $classes = Section::query()
            ->where('school_id', $schoolId)
            ->when(
                $allowedSectionIds !== null,
                fn ($query) => $query->whereIn('id', $allowedSectionIds === [] ? [0] : $allowedSectionIds)
            )
            ->orderBy('name')
            ->get();

        $years = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->orderByRaw('start_date is null')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $yearId = $request->filled('year') ? (int) $request->input('year') : null;
        $year = null;
        if ($yearId !== null) {
            $year = $years->firstWhere('id', $yearId);
            abort_if($year === null, 404);
        }

        $terms = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->when($year !== null, fn ($query) => $query->where('academic_year_id', $year->id))
            ->orderByRaw('starts_on is null')
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get();

        $examTypes = ExamType::query()
            ->visibleToSchool($schoolId)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
        [$examSetTabs, $otherExamTypes] = $this->examSets($examTypes, $schoolId);

        $sectionId = $request->filled('class') ? (int) $request->input('class') : null;
        $termId = $request->filled('term') ? (int) $request->input('term') : null;
        $examTypeId = $request->filled('examType') ? (int) $request->input('examType') : null;
        $streamId = $request->filled('stream') ? (int) $request->input('stream') : null;

        $requestedTerm = null;
        if ($termId !== null) {
            $requestedTerm = AcademicTerm::query()->where('school_id', $schoolId)->whereKey($termId)->first();
        }
        $selectedYearId = $year?->id;
        if ($selectedYearId === null && $requestedTerm !== null) {
            $selectedYearId = (int) $requestedTerm->academic_year_id;
        }

        $base = [
            'classes' => $classes,
            'years' => $years,
            'terms' => $terms,
            'examTypes' => $examTypes,
            'examSetTabs' => $examSetTabs,
            'otherExamTypes' => $otherExamTypes,
            'selectedYearId' => $selectedYearId,
            'streams' => collect(),
            'subjects' => collect(),
            'students' => new LengthAwarePaginator([], 0, self::PAGE_SIZE),
            'missingSubjects' => collect(),
            'subjectsCovered' => 0,
            'class' => null,
            'type' => null,
            'exam' => null,
            'termName' => null,
            'filtered' => false,
            'streamFiltered' => false,
            'marksError' => null,
            'marksSubtitle' => 'Select class, term and exam type to open the marks grid.',
            'filterRoute' => $filterRoute,
            'downloadRoute' => $downloadRoute,
            'releaseRoute' => $filterRoute === 'teacher.marks.sheet'
                ? 'teacher.marks.releases.create'
                : 'admin.marks.releases.create',
            'showReportLink' => $showReportLink,
            'sheet' => null,
        ];

        $section = null;
        if ($sectionId !== null) {
            $section = Section::query()->where('school_id', $schoolId)->whereKey($sectionId)->first();
            abort_if($section === null, 404);
            if ($allowedSectionIds !== null && ! in_array($section->id, $allowedSectionIds, true)) {
                abort(403);
            }
            $base['class'] = $section;
            $base['streams'] = StandardLink::query()
                ->where('school_id', $schoolId)
                ->where('section_id', $section->id)
                ->orderBy('stream')
                ->orderBy('id')
                ->get();
        }

        if ($sectionId === null || $termId === null || $examTypeId === null) {
            return $base;
        }

        $term = $requestedTerm;
        abort_if($term === null, 404);
        if ($year !== null && (int) $term->academic_year_id !== (int) $year->id) {
            $base['marksError'] = 'That term is not in the chosen year.';

            return $base;
        }
        $type = ExamType::query()->visibleToSchool($schoolId)->whereKey($examTypeId)->first();
        abort_if($type === null, 404);

        if ($streamId !== null) {
            $stream = StandardLink::query()
                ->where('school_id', $schoolId)
                ->where('section_id', $section->id)
                ->whereKey($streamId)
                ->first();
            abort_if($stream === null, 404);
        }

        $sheet = $this->results->sheet(new ResultsQuery(
            schoolId: $schoolId,
            sectionId: (int) $section->id,
            academicTermId: (int) $term->id,
            standardLinkId: $streamId,
            examTypeId: (int) $type->id,
            countApprovedSubjects: true,
        ));

        $subjects = collect($sheet->subjects);
        $missing = $subjects
            ->filter(fn (array $subject) => ! in_array($subject['submission'], ['Submitted', 'Approved', 'Locked'], true))
            ->values();
        $covered = $subjects
            ->filter(fn (array $subject) => in_array($subject['submission'], ['Submitted', 'Approved', 'Locked'], true))
            ->count();

        $page = max(1, (int) $request->input('page', 1));
        $perPage = self::PAGE_SIZE;
        $students = new LengthAwarePaginator(
            array_slice($sheet->students, ($page - 1) * $perPage, $perPage),
            count($sheet->students),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $exam = Exam::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $section->id)
            ->where('academic_term_id', $term->id)
            ->where('exam_type_id', $type->id)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first();

        $streamName = null;
        if ($streamId !== null) {
            $streamName = (string) ($base['streams']->firstWhere('id', $streamId)?->stream ?? '');
        }

        $marksSubtitle = trim(implode(' · ', array_filter([
            $section->name,
            $streamName !== null && $streamName !== '' ? $streamName : null,
            $term->name,
            $type->name,
            $students->total().' students',
        ])));

        return array_merge($base, [
            'subjects' => $subjects,
            'students' => $students,
            'missingSubjects' => $missing,
            'subjectsCovered' => $covered,
            'class' => $section,
            'type' => $type,
            'exam' => $exam,
            'termName' => $term->name,
            'filtered' => true,
            'streamFiltered' => $streamId !== null,
            'marksSubtitle' => $marksSubtitle !== '' ? $marksSubtitle : 'Marks grid',
            'sheet' => $sheet,
        ]);
    }

    /**
     * The three term sittings, in workbook order. Any other type stays
     * in the extra control. A school's own type wins over a shared name.
     *
     * @param Collection<int, ExamType> $types
     * @return array{0: list<ExamType>, 1: Collection<int, ExamType>}
     */
    private function examSets(Collection $types, int $schoolId): array
    {
        $tabs = [];
        foreach (self::EXAM_SET_NAMES as $name) {
            $tabs[$name] = null;
        }
        $others = collect();
        foreach ($types as $type) {
            $key = mb_strtolower(trim((string) $type->name));
            if (! array_key_exists($key, $tabs)) {
                $others->push($type);
                continue;
            }
            $current = $tabs[$key];
            if (! $current instanceof ExamType || $this->preferExamType($type, $current, $schoolId)) {
                if ($current instanceof ExamType) {
                    $others->push($current);
                }
                $tabs[$key] = $type;
                continue;
            }
            $others->push($type);
        }

        return [array_values(array_filter($tabs)), $others->values()];
    }

    private function preferExamType(ExamType $candidate, ExamType $current, int $schoolId): bool
    {
        $candidateOwn = (int) $candidate->school_id === $schoolId;
        $currentOwn = (int) $current->school_id === $schoolId;
        if ($candidateOwn !== $currentOwn) {
            return $candidateOwn;
        }

        return (int) $candidate->id < (int) $current->id;
    }

    /**
     * @param list<int>|null $allowedSectionIds
     */
    public function download(int $schoolId, Request $request, ?array $allowedSectionIds = null): BinaryFileResponse
    {
        $data = $this->viewData($schoolId, $request, $allowedSectionIds);
        $sheet = $data['sheet'] ?? null;
        abort_if(! ($data['filtered'] ?? false) || ! $sheet instanceof ResultSheet, 404);

        $className = str_replace(' ', '_', (string) ($data['class']?->name ?? 'class'));

        return Excel::download(
            new SittingSheetExport($sheet, (bool) $data['streamFiltered']),
            'combined_sheet_'.$className.'.xlsx'
        );
    }
}
