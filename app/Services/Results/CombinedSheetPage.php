<?php

namespace App\Services\Results;

use App\Exports\SittingSheetExport;
use App\Models\AcademicTerm;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The one combined sheet for a sitting: filters, the ResultsService
 * sheet, and the download of that same sheet. No totals are worked
 * out here.
 */
final class CombinedSheetPage
{
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

        $terms = AcademicTerm::query()->where('school_id', $schoolId)->orderBy('id')->get();
        $examTypes = ExamType::query()->orderBy('name')->get();

        $base = [
            'classes' => $classes,
            'terms' => $terms,
            'examTypes' => $examTypes,
            'streams' => collect(),
            'subjects' => collect(),
            'students' => new LengthAwarePaginator([], 0, 6),
            'missingSubjects' => collect(),
            'subjectsCovered' => 0,
            'class' => null,
            'type' => null,
            'exam' => null,
            'termName' => null,
            'filtered' => false,
            'streamFiltered' => false,
            'marksSubtitle' => 'Select class, term and exam type to open the marks grid.',
            'filterRoute' => $filterRoute,
            'downloadRoute' => $downloadRoute,
            'showReportLink' => $showReportLink,
            'sheet' => null,
        ];

        $sectionId = $request->filled('class') ? (int) $request->input('class') : null;
        $termId = $request->filled('term') ? (int) $request->input('term') : null;
        $examTypeId = $request->filled('examType') ? (int) $request->input('examType') : null;
        $streamId = $request->filled('stream') ? (int) $request->input('stream') : null;

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

        $term = AcademicTerm::query()->where('school_id', $schoolId)->whereKey($termId)->first();
        abort_if($term === null, 404);
        $type = ExamType::query()->find($examTypeId);
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
        ));

        $subjects = collect($sheet->subjects);
        $missing = $subjects
            ->filter(fn (array $subject) => ! in_array($subject['submission'], ['Submitted', 'Approved', 'Locked'], true))
            ->values();
        $covered = $subjects
            ->filter(fn (array $subject) => in_array($subject['submission'], ['Submitted', 'Approved', 'Locked'], true))
            ->count();

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 6;
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
