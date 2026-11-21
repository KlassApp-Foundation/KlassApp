<?php

namespace App\Services;

use App\Contracts\Results\GradingScale;
use App\Contracts\Results\ReleaseGate;
use App\Contracts\Results\ReportTemplates;
use App\Services\Results\ResultCell;
use App\Services\Results\ResultSheet;
use App\Services\Results\ResultStudent;
use App\Services\Results\ResultsPolicy;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use App\Models\AcademicTerm;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\SchoolDetail;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Shared report-card PDF pipeline (resolve contributing exam, rank, render).
 * Admin and teacher surfaces must call this — do not fork generatePdf().
 */
class StudentReportCardService
{
    /**
     * A whole-class PDF with this many students (or fewer) is built in the
     * request and downloaded immediately. A larger class is queued
     * (GenerateClassReportsJob) and the class page shows the status until the
     * file is ready.
     *
     * One card runs a class sheet plus a DomPDF render. Twelve stays inside a
     * normal HTTP limit; the thirteenth is prepared in the background.
     */
    public const CLASS_PDF_DIRECT_MAX = 12;

    /**
     * Available report-card templates: key => [label, Blade view].
     */
    public const TEMPLATES = [
        'formal' => ['label' => 'Standard', 'view' => 'admin.marks.report-templates.formal'],
        'warm' => ['label' => 'Warm', 'view' => 'admin.marks.report-templates.warm'],
        'progressive' => ['label' => 'New look', 'view' => 'admin.marks.report-templates.progressive'],
    ];

    /**
     * MID exam column header for report control rows (e.g. "APR MID").
     * scheduled_at is optional on exams — CT/admin create forms allow null.
     */
    public static function midExamControlColumnLabel(Exam $exam): string
    {
        $name = self::setName($exam);
        if ($exam->scheduled_at) {
            return strtoupper($exam->scheduled_at->format('M')).' '.$name;
        }

        return $name;
    }

    /**
     * Row label in the stand-alone sets table. A dated sitting keeps the
     * month name. A sitting with no date uses the school's own set name.
     */
    public static function midExamMonthRowLabel(Exam $exam): string
    {
        if ($exam->scheduled_at) {
            return strtoupper($exam->scheduled_at->format('F'));
        }

        return self::setName($exam);
    }

    private static function setName(Exam $exam): string
    {
        if ($exam->relationLoaded('examType') && $exam->examType !== null) {
            $name = trim((string) ($exam->examType->name ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        if ($exam->exists) {
            $exam->loadMissing('examType');
            $name = trim((string) ($exam->examType->name ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        return 'Exam set';
    }

    /**
     * Contributing exam for a class.
     *
     * The term is the one the caller passed, or the school's current term
     * (latest starts_on, then highest id) when the caller does not pass one.
     * The exam inside that term is a stable handle (lowest id), not the most
     * recently created exam. Staff stay on this method and are not release-gated.
     */
    public function resolveExam(int $schoolId, StandardLink $stdLink, ?int $academicTermId = null): ?Exam
    {
        $termId = $this->termIdForReport($schoolId, $academicTermId);
        if ($termId === null) {
            return null;
        }

        return $this->anchorExam($schoolId, $stdLink, $termId);
    }

    /**
     * Parent report: a requested term is used only when that term's contributing
     * sitting is released. With no request, walk terms from the latest start
     * date and return the first released one. An unreleased newer term does
     * not hide an older released term, and an unreleased request is not
     * replaced with a different term.
     */
    public function resolveReleasedExam(int $schoolId, StandardLink $stdLink, User $student, ?int $academicTermId = null): ?Exam
    {
        $gate = app(ReleaseGate::class);
        if ($academicTermId !== null) {
            $exam = $this->resolveExam($schoolId, $stdLink, $academicTermId);

            return ($exam && $gate->isReleased($student, $exam)) ? $exam : null;
        }

        $termIds = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->pluck('id');

        foreach ($termIds as $termId) {
            $exam = $this->anchorExam($schoolId, $stdLink, (int) $termId);
            if ($exam && $gate->isReleased($student, $exam)) {
                return $exam;
            }
        }

        return null;
    }

    public function studentHasContributingMark(Exam $anchor, int $studentId): bool
    {
        $examIds = $this->contributingExamIds($anchor);
        if ($examIds->isEmpty()) {
            return false;
        }

        return Marks::query()
            ->where('student_id', $studentId)
            ->whereIn('exam_id', $examIds)
            ->whereNotNull('marks')
            ->exists();
    }

    private function termIdForReport(int $schoolId, ?int $academicTermId): ?int
    {
        if ($academicTermId !== null) {
            $belongs = AcademicTerm::query()
                ->where('school_id', $schoolId)
                ->whereKey($academicTermId)
                ->exists();

            return $belongs ? $academicTermId : null;
        }

        $id = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->where('status', 'current')
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Lowest id among contributing exams in the term. created_at is not a term.
     */
    private function anchorExam(int $schoolId, StandardLink $stdLink, int $academicTermId): ?Exam
    {
        $exam = $this->examOnSection($schoolId, (int) $stdLink->section_id, (int) $stdLink->standard_id, $academicTermId);
        if ($exam !== null) {
            return $exam;
        }

        // Exam sets create one exam per class on the base section ("Primary
        // Seven"), while students sit in its streams ("Primary Seven E"). A
        // stream's report card resolves the base class's exam.
        $section = $stdLink->section;
        if ($section === null) {
            return null;
        }

        $base = app(ClassStructureService::class)->resolveBaseSection($section);
        if ((int) $base->id === (int) $section->id) {
            return null;
        }

        return $this->examOnSection($schoolId, (int) $base->id, (int) $stdLink->standard_id, $academicTermId);
    }

    private function examOnSection(int $schoolId, int $sectionId, int $standardId, int $academicTermId): ?Exam
    {
        $rows = Exam::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->where('standard_id', $standardId)
            ->where('academic_term_id', $academicTermId)
            ->whereNull('deleted_at')
            ->with('examType')
            ->orderBy('id')
            ->get();

        $contributing = $rows->first(fn ($row) => $row->examType && (bool) $row->examType->contributes_to_report_total);

        return $contributing ?? $rows->first();
    }

    /**
     * Exam-set names this class's report card prints for the current term.
     * The school's own exam type names, in the card's date order. A stream
     * with no sitting of its own uses the base class, the same way the card does.
     *
     * @return list<string>
     */
    public function examSetNames(int $schoolId, StandardLink $stdLink): array
    {
        $anchor = $this->resolveExam($schoolId, $stdLink);
        if ($anchor === null) {
            return [];
        }

        return app(StudentReportHelperService::class)
            ->exam($schoolId, $anchor)
            ->filter(fn ($exam) => $this->typeIncluded($schoolId, $exam))
            ->sortBy(fn ($exam): array => [
                $exam->scheduled_at?->getTimestamp() ?? PHP_INT_MAX,
                (int) $exam->id,
            ])
            ->map(fn ($exam): string => trim((string) ($exam->examType?->name ?? '')))
            ->filter(fn (string $name): bool => $name !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, int>
     */
    private function contributingExamIds(Exam $anchor): Collection
    {
        return Exam::query()
            ->where('school_id', $anchor->school_id)
            ->where('section_id', $anchor->section_id)
            ->where('academic_year_id', $anchor->academic_year_id)
            ->where('academic_term_id', $anchor->academic_term_id)
            ->whereHas('examType', fn ($q) => $q->where('contributes_to_report_total', 1))
            ->pluck('id');
    }

    public function studentIds(Exam $exam): Collection
    {
        return \App\Models\Academics\Marks::where('exam_id', $exam->id)
            ->distinct('student_id')
            ->orderBy('student_id')
            ->pluck('student_id');
    }

    public function computePositionMap(Exam $exam, int $schoolId, ?array $onlyExamTypeIds = null): array
    {
        $sheet = $this->classSheet($schoolId, (int) $exam->section_id, (int) $exam->academic_term_id, (int) $exam->standard_id, null, $onlyExamTypeIds);
        if ($sheet === null) {
            return [];
        }

        $map = [];
        foreach ($sheet->students as $student) {
            if ($student->classPosition !== null) {
                $map[$student->studentId] = $student->classPosition;
            }
        }

        return $map;
    }

    /**
     * Build a single student's report PDF using the same inputs as the admin
     * preview/download routes. Pass null $templateKey to inherit the school setting.
     *
     * @throws \RuntimeException when the class has no exam set in the term
     */
    public function pdfForStudent(
        int $schoolId,
        StandardLink $stdLink,
        User $learner,
        ?string $templateKey = null,
        ?int $academicTermId = null,
        ?array $onlyExamTypeIds = null,
    ): string {
        $exam = $this->resolveExam($schoolId, $stdLink, $academicTermId);
        if ($exam && $onlyExamTypeIds !== null && ! in_array((int) $exam->exam_type_id, array_map(intval(...), $onlyExamTypeIds), true)) {
            $replacement = Exam::query()
                ->where('school_id', $schoolId)
                ->where('section_id', (int) $exam->section_id)
                ->where('academic_term_id', (int) $exam->academic_term_id)
                ->whereIn('exam_type_id', $onlyExamTypeIds)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->first();
            if ($replacement !== null) {
                $exam = $replacement;
            }
        }
        if (! $exam) {
            throw new \RuntimeException('No exam set found for this class.');
        }

        $allStudentIds = $this->studentIds($exam);
        $helper = app(StudentReportHelperService::class);
        $svc = new ReportCardCommentService;
        $positionMap = $this->computePositionMap($exam, $schoolId, $onlyExamTypeIds);
        $myPos = $positionMap[$learner->id] ?? 0;

        return $this->generatePdf(
            $learner->id,
            $exam,
            $stdLink,
            $schoolId,
            $helper,
            $svc,
            $allStudentIds->count(),
            $myPos,
            $templateKey,
            false,
            false,
            $onlyExamTypeIds,
        );
    }

    /**
     * Active learners enrolled in this class. A missing mark in one subject
     * does not drop the learner: that cell prints Missing and counts as 0.
     * Ordered by name, then id.
     *
     * @return Collection<int, User>
     */
    public function reportLearners(int $schoolId, StandardLink $stdLink, ?int $academicTermId = null): Collection
    {
        $exam = $this->resolveExam($schoolId, $stdLink, $academicTermId);
        if ($exam === null) {
            return collect();
        }

        $enrolled = \App\Models\StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->where('standardLink_id', $stdLink->id)
            ->whereNull('deleted_at')
            ->pluck('user_id');

        // An exam on the class holds every stream's marks. A stream whose
        // section is not that exam's section is limited to its own learners.
        // A learner enrolled here with no mark on the pinned exam still gets
        // a card: the missing subject prints Missing and counts as 0.
        $ids = $this->studentIds($exam);
        if ((int) $exam->section_id !== (int) $stdLink->section_id) {
            $ids = $ids->intersect($enrolled);
        }
        $ids = $ids->merge($enrolled)->unique()->values();

        return User::query()
            ->whereIn('id', $ids)
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * While a class PDF is being built, each card is rendered to HTML and the
     * class sheet is built once. The finished file is one Dompdf document, so
     * the fonts are embedded once for the class instead of once per page.
     */
    private bool $collectingClassCards = false;

    /** @var list<string> */
    private array $classCardHtml = [];

    /** @var array<string, ResultSheet|null>|null */
    private ?array $classSheetCache = null;

    /**
     * One PDF, one report card per student. Each card uses the same view and
     * data as the single-student download, including that student's own class
     * and stream fees. The pages share one font embed.
     *
     * @param  Collection<int, User>  $learners
     */
    public function mergedPdfForLearners(
        int $schoolId,
        StandardLink $stdLink,
        Collection $learners,
        ?int $academicTermId = null
    ): string {
        if ($learners->isEmpty()) {
            throw new \RuntimeException('No learners in this class.');
        }

        $this->collectingClassCards = true;
        $this->classCardHtml = [];
        $this->classSheetCache = [];
        try {
            foreach ($learners as $learner) {
                $this->pdfForStudent($schoolId, $stdLink, $learner, null, $academicTermId);
            }
            $cards = $this->classCardHtml;
        } finally {
            $this->collectingClassCards = false;
            $this->classCardHtml = [];
            $this->classSheetCache = null;
        }

        return $this->pdfForCards($cards);
    }

    /**
     * @param  list<string>  $documents
     */
    private function pdfForCards(array $documents): string
    {
        $html = count($documents) === 1
            ? $documents[0]
            : $this->classDocument($documents);
        unset($documents);

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * One HTML document. Each card keeps the markup the single-student view
     * rendered. A later card starts on the next page. The card templates
     * themselves are unchanged.
     *
     * @param  list<string>  $documents
     */
    private function classDocument(array $documents): string
    {
        $head = '';
        if (preg_match('/<head\b[^>]*>(.*)<\/head>/is', $documents[0], $match) === 1) {
            $head = $match[1];
        }

        $sheets = '';
        foreach ($documents as $index => $document) {
            $body = $document;
            if (preg_match('/<body\b[^>]*>(.*)<\/body>/is', $document, $match) === 1) {
                $body = $match[1];
            }
            $break = $index === 0 ? '' : 'page-break-before:always;';
            $sheets .= '<div class="report-card-sheet" style="position:relative;'.$break.'">'.$body.'</div>';
        }

        return '<!DOCTYPE html><html lang="en"><head>'.$head.'</head><body>'.$sheets.'</body></html>';
    }

    public function classPdfFilename(StandardLink $stdLink): string
    {
        $stdLink->loadMissing('section');
        $className = trim((string) ($stdLink->section->name ?? 'class'));
        $stream = trim((string) ($stdLink->stream ?? ''));
        $label = $stream !== '' ? $className.'_'.$stream : $className;
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $label) ?: 'class';

        return 'report_cards_'.$safe.'.pdf';
    }

    /**
     * Subject-fill readiness for a class against the resolved contributing exam's term:
     * subjects with at least one mark row for any student vs total subjects on the section.
     *
     * @return array{filled: int, total: int}|null null when no contributing exam
     */
    public function subjectFillCounts(int $schoolId, StandardLink $stdLink, ?int $academicTermId = null): ?array
    {
        $exam = $this->resolveExam($schoolId, $stdLink, $academicTermId);
        if (! $exam) {
            return null;
        }

        $subjects = \App\Models\Subject::where('school_id', $schoolId)
            ->where('section_id', $stdLink->section_id)
            ->get(['id']);

        $total = $subjects->count();
        if ($total === 0) {
            return ['filled' => 0, 'total' => 0];
        }

        $scopedExamIds = Exam::where('school_id', $schoolId)
            ->where('section_id', $exam->section_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('academic_term_id', $exam->academic_term_id)
            ->pluck('id');

        $filledSubjectIds = \App\Models\Academics\Marks::whereIn('exam_id', $scopedExamIds)
            ->whereNotNull('marks')
            ->distinct()
            ->pluck('subject_id');

        $filled = $subjects->whereIn('id', $filledSubjectIds)->count();

        return ['filled' => $filled, 'total' => $total];
    }

    /**
     * The school's report composition can hide an exam type's column from
     * report cards entirely (schools.report_composition include flag).
     */
    private function typeIncluded(int $schoolId, $exam): bool
    {
        $typeId = (int) ($exam->examType?->id ?? 0);
        if ($typeId === 0) {
            return false;
        }
        $composition = \App\Models\School::query()->find($schoolId)?->report_composition;
        if (! is_array($composition)) {
            return true;
        }
        $settings = $composition[(string) $typeId] ?? $composition[$typeId] ?? [];

        return ($settings['include'] ?? true) !== false;
    }

    public function generatePdf(
        int $sid,
        Exam $exam,
        StandardLink $stdLink,
        int $schoolId,
        $helper,
        ReportCardCommentService $svc,
        int $totalLearners,
        int $myPos = 0,
        ?string $templateKey = null,
        bool $sample = false,
        bool $printBlackAndWhite = false,
        ?array $onlyExamTypeIds = null,
    ): string {
        $learner = User::find($sid);
        $learner = $helper->learner($schoolId, $learner, $exam);
        $subjects = $helper->subjects($schoolId, $stdLink->section_id, $learner, $exam);
        $subjects = \App\Models\Subject::sortByReportOrder($subjects);
        $exams = $helper->exam($schoolId, $exam);
        $exams = $exams->filter(fn ($e) => $this->typeIncluded($schoolId, $e));
        if ($onlyExamTypeIds !== null) {
            $allowed = array_map(intval(...), $onlyExamTypeIds);
            $exams = $exams->filter(fn ($row) => in_array((int) $row->exam_type_id, $allowed, true))->values();
        }
        $countMissingAsZero = app(\App\Services\Reports\ReportScoring::class)->countsMissingAsZero(
            $schoolId,
            (int) $stdLink->section_id,
            $exam->academic_term_id ? (int) $exam->academic_term_id : null,
        );
        $exams = $this->examsOnStartedSets($schoolId, $exams, $countMissingAsZero);

        $school = School::find($schoolId);
        $preferences = is_array($school?->exam_type_preferences) ? $school->exam_type_preferences : null;
        $contribution = app(\App\Services\Results\ExamTypeContribution::class);
        $contributing = $exams
            ->filter(fn ($row) => $row->examType && $contribution->contributes($row->examType, $preferences))
            ->sortBy(fn ($row) => (int) $row->exam_type_id)
            ->values();
        $allExamColumns = $exams->sortBy(fn ($row) => [
            $row->scheduled_at?->getTimestamp() ?? PHP_INT_MAX,
            (int) $row->id,
        ])->values();

        $controls = ['SUBJECT', 'OUT OF'];
        $namedTypes = [];
        foreach ($allExamColumns as $row) {
            $typeId = (int) $row->exam_type_id;
            if ($typeId < 1 || isset($namedTypes[$typeId]) || $row->examType === null) {
                continue;
            }
            $namedTypes[$typeId] = true;
            $controls[] = (string) $row->examType->name;
        }
        $controls = array_merge($controls, ['DIVISION', 'TEACHER', 'REMARK']);

        $standard = $learner->studentAcademicLatest?->standardLink?->standard;
        $isNursery = $standard && \App\Helpers\GradingHelper::levelTypeForStandard($standard) === 'nursery';
        $isALevel = $standard && \App\Helpers\GradingHelper::levelTypeForStandard($standard) === 'a-level';

        $standardName = $standard?->name ?? '';

        // Use grading_style if set; fall back to legacy name-based logic when NULL.
        if ($standard && $standard->grading_style !== null) {
            $showAgg = $standard->grading_style === 'aggregate';
        } else {
            $showAgg = ! $isNursery && ! in_array($standardName, ['primary_lower'], true);
        }

        $appliedRules = app(\App\Services\Reports\ProgressiveRulesStamp::class)->applied(
            $schoolId,
            (int) $stdLink->section_id,
            $exam->academic_term_id ? (int) $exam->academic_term_id : null,
            (int) $stdLink->standard_id,
        );
        $gradingSystem = $appliedRules['mode'] === \App\Services\Reports\ProgressiveRulesStamp::MODE_STORED
            ? $this->storedGradingRows($schoolId, (int) $stdLink->standard_id, $appliedRules['rules']['bands'] ?? [])
            : \App\Models\Academics\SchoolGradingSystem::where('school_id', $schoolId)
                ->where('standard_id', $stdLink->standard_id)
                ->orderBy('min_score', 'desc')
                ->get();

        $policy = $this->resultsPolicy(
            $schoolId,
            (int) $stdLink->standard_id,
            (int) $stdLink->section_id,
            $exam->academic_term_id ? (int) $exam->academic_term_id : 0,
        );
        $division = $policy->divisionRule;
        $divisionLabel = static function (?array $points) use ($division): string {
            if ($points === null) {
                return '-';
            }

            return $division->division($points['points'], $points['counted']) ?? '-';
        };

        // Stream = a label on a StandardLink (e.g. "EAST"/"WEST"/"A"/"B").
        // It is only meaningful WITHIN one section (class): sibling stream
        // links share the same section_id, and the same label is reused
        // across different grade levels (e.g. "EAST" on P.1, P.2 and P.3).
        // Every stream-scoped query below must therefore filter by
        // section_id (and standard_id) IN ADDITION to stream, or it will
        // pool unrelated classes that reuse the same label.
        $streamName = $learner->studentAcademicLatest?->standardLink?->stream ?? null;

        $eotMarkBySubject = $this->chosenMarksBySubject($learner->marks, $contributing);
        $eotPoints = $this->sumPoints($eotMarkBySubject, $schoolId, (int) $stdLink->standard_id);
        $eotDivision = $divisionLabel($eotPoints);

        // A level is graded on points, not on a division; an O-level scale has
        // no points at all, so a division there would print a misleading '-'.
        $showDivision = $showAgg && ! $isALevel && $eotDivision !== '-';
        $showPoints = $isALevel;

        $streamPos = null;
        $streamTotal = null;
        if ($streamName && $contributing->isNotEmpty()) {
            $streamRows = DB::table('student_academics')
                ->join('standards_link', 'standards_link.id', '=', 'student_academics.standardLink_id')
                ->where('standards_link.school_id', $schoolId)
                ->where('standards_link.standard_id', $stdLink->standard_id)
                ->where('standards_link.section_id', $stdLink->section_id)
                ->where('standards_link.stream', $streamName)
                ->where('student_academics.academic_year_id', $exam->academic_year_id)
                ->whereNull('student_academics.deleted_at')
                ->pluck('student_academics.user_id')
                ->all();
            $examIds = $contributing->pluck('id');
            $streamMarks = Marks::query()
                ->whereIn('student_id', $streamRows)
                ->whereIn('exam_id', $examIds)
                ->whereNotNull('marks')
                ->get()
                ->groupBy('student_id');
            $ranked = [];
            foreach ($streamRows as $rankedSid) {
                $chosen = $this->chosenMarksBySubject($streamMarks->get($rankedSid, collect()), $contributing);
                $ranked[$rankedSid] = array_sum(array_map(
                    fn ($mark) => ResultsPolicy::roundTo((float) $mark->marks),
                    $chosen
                ));
            }
            arsort($ranked);
            $streamPos = 0;
            foreach (array_keys($ranked) as $i => $rankedSid) {
                if ($rankedSid === $learner->id) {
                    $streamPos = $i + 1;
                    break;
                }
            }
            $streamTotal = count($ranked);
        }

        $total = array_sum(array_map(
            fn ($mark) => ResultsPolicy::roundTo((float) $mark->marks),
            $eotMarkBySubject
        ));
        $midSittings = [];
        $sheet = $this->classSheet($schoolId, (int) $stdLink->section_id, (int) $exam->academic_term_id, (int) $stdLink->standard_id, (int) $learner->id, $onlyExamTypeIds);
        $resultRow = $sheet?->student((int) $learner->id);
        $withheldSets = $sheet instanceof ResultSheet ? $sheet->unreleasedWeightedSets : [];
        if ($resultRow !== null && $withheldSets === []) {
            $total = (float) ($resultRow->total ?? 0);
            $eotPoints = $resultRow->aggregate === null
                ? null
                : ['points' => $resultRow->aggregate, 'counted' => $resultRow->subjectsCounted];
            $eotDivision = $resultRow->division ?? '-';
            $myPos = $resultRow->classPosition ?? 0;
            $totalLearners = $resultRow->classOutOf;
            $streamPos = $resultRow->streamPosition;
            $streamTotal = $resultRow->streamOutOf > 0 ? $resultRow->streamOutOf : null;
            $midSittings = $this->sittingColumns($sheet, (int) $learner->id, false);
        }
        if ($withheldSets !== []) {
            $total = null;
            $eotPoints = null;
            if ($sheet instanceof ResultSheet) {
                $midSittings = $this->sittingColumns($sheet, (int) $learner->id, false);
            }
        }
        $monthlySets = $midSittings;
        $termSets = $sheet instanceof ResultSheet
            ? $this->sittingColumns($sheet, (int) $learner->id, true)
            : [];
        $setColumns = $sheet instanceof ResultSheet
            ? $this->sittingColumns($sheet, (int) $learner->id, null)
            : [];
        // When the school's report composition weights mid-term types (which
        // have contributes_to_report_total = 0) and the anchor type (End paper)
        // is NOT one of those weighted types, the displayed per-subject marks
        // already come from the weighted composite via the $eotCell path in the
        // template. Rename the contributing column 'Combined' so its header
        // matches what is actually printed.
        if ($termSets !== [] && $withheldSets === []) {
            $reportComposition = is_array($school?->report_composition) ? $school->report_composition : [];
            if ($reportComposition !== []) {
                $weightedTypeIds = array_map('intval', array_keys(array_filter(
                    $reportComposition,
                    fn (array $s) => isset($s['weight']) && is_numeric($s['weight']) && (float) $s['weight'] > 0,
                )));
                $termSets = array_map(function (array $set) use ($weightedTypeIds): array {
                    if (! in_array($set['typeId'], $weightedTypeIds, true)) {
                        $set['label'] = 'Combined';
                    }

                    return $set;
                }, $termSets);
            }
        }
        $standardId = (int) ($standard->id ?? $stdLink->standard_id);
        $progressiveRules = app(\App\Services\Reports\ProgressiveRules::class);
        if ($appliedRules['mode'] === \App\Services\Reports\ProgressiveRulesStamp::MODE_STORED
            && is_array($appliedRules['rules']['division_ranges'] ?? null)
            && $appliedRules['rules']['division_ranges'] !== []) {
            $divisionRanges = $appliedRules['rules']['division_ranges'];
        } elseif ($appliedRules['mode'] === \App\Services\Reports\ProgressiveRulesStamp::MODE_HISTORICAL) {
            $divisionRanges = \App\Services\Reports\ProgressiveRules::defaultDivisionRanges();
        } else {
            $divisionRanges = $progressiveRules->rangesFor($schoolId, $standardId);
            if ($divisionRanges === []) {
                $divisionRanges = \App\Services\Reports\ProgressiveRules::defaultDivisionRanges();
            }
        }
        $rankingBasis = $policy->rankingBasis;
        $incompleteSets = $sheet instanceof ResultSheet
            ? $this->incompleteSetNames($sheet, (int) $learner->id)
            : [];
        $noCombinedMark = $setColumns !== [] && $termSets === [];
        $withheldSentence = app(ResultsService::class)->withheldSentence($withheldSets);
        $showDivision = $showAgg && ! $isALevel && $eotDivision !== '-';
        $eotCells = $resultRow?->cells ?? [];
        $examinedSubjectCount = $subjects->filter(fn ($s) => $learner->marks->where('subject_id', $s->id)->isNotEmpty())->count();
        if ($countMissingAsZero && $resultRow !== null && $resultRow->subjectsCounted > $examinedSubjectCount) {
            $examinedSubjectCount = $resultRow->subjectsCounted;
        }
        $grade = $helper->grade($learner, $exam);
        $teacherComment = $standard
            ? $svc->commentFor((int) $total, $standard->name, $learner->id, $exam->id)
            : '';
        $headTeacherComment = $standard
            ? $svc->headTeacherCommentFor((int) $total, $standard->name, $learner->id, $exam->id, $teacherComment)
            : '';

        $view = app(ReportTemplates::class)->resolve($templateKey, $school?->report_template);
        if (($view['view'] ?? '') === 'admin.marks.report-templates.progressive') {
            $fontDir = storage_path('fonts');
            if (! is_dir($fontDir)) {
                mkdir($fontDir, 0755, true);
            }
        }
        $composition = app(\App\Services\ReportCards\EndOfTermCompositionLine::class);
        $anchorType = $exam->examType;
        $countsOnCombined = $anchorType !== null && $composition->countsToward(
            $anchorType,
            $composition->compositionFor($schoolId, (int) $exam->id),
        );
        $compositionLine = $composition->lineFor($schoolId, $countsOnCombined, (int) $exam->id);
        $reportSectionIds = array_values(array_unique(array_filter([
            (int) ($stdLink->section_id ?? 0),
            (int) ($exam->section_id ?? 0),
        ])));
        foreach (Section::query()->where('school_id', $schoolId)->whereIn('id', $reportSectionIds === [] ? [0] : $reportSectionIds)->get() as $reportSection) {
            $reportSectionIds[] = (int) app(\App\Services\ClassStructureService::class)->resolveBaseSection($reportSection)->id;
        }
        $ownReports = app(\App\Services\ReportCards\EndOfTermCompositionLine::class)->ownReports(
            $schoolId,
            $reportSectionIds,
            (int) $exam->academic_term_id,
        );
        $startedTypeIds = Exam::query()
            ->where('school_id', $schoolId)
            ->whereIn('section_id', $reportSectionIds === [] ? [0] : $reportSectionIds)
            ->where('academic_term_id', (int) $exam->academic_term_id)
            ->whereNull('deleted_at')
            ->whereIn('id', Marks::query()
                ->where('school_id', $schoolId)
                ->where(function ($query): void {
                    $query->whereNotNull('marks')->orWhere('absent', true);
                })
                ->select('exam_id'))
            ->pluck('exam_type_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $ownReports = array_values(array_filter(
            $ownReports,
            fn (array $own): bool => in_array((int) ($own['id'] ?? 0), $startedTypeIds, true),
        ));
        $logoPath = $this->resolveLogoPath($schoolId);
        $schoolIdentity = $this->resolveSchoolIdentity($school);

        $data = [
            'subjects' => $subjects, 'learner' => $learner, 'controls' => $controls,
            'class_name' => Section::find($stdLink->section_id)->name,
            'grading_system' => $gradingSystem,
            'fees' => $learner instanceof User
                ? app(StudentFeeResolver::class)->forStudent(
                    $learner,
                    $schoolId,
                    $exam->academic_term_id ? (int) $exam->academic_term_id : null,
                )
                : collect(),
            'nextTerm' => AcademicTerm::where('school_id', $schoolId)->where('starts_on', '>', now())->first(),
            'totalLearners' => $totalLearners, 'myPos' => $myPos,
            'allExamColumns' => $allExamColumns,
            'midCount' => count($monthlySets), 'eotCount' => count($termSets),
            'stdLink' => $stdLink,
            'total' => $total, 'grade' => $grade, 'examinedSubjectCount' => $examinedSubjectCount,
            'eotAgg' => $withheldSets !== [] ? null : ($eotPoints['points'] ?? ($grade['agg'] ?? null)),
            'school' => $school,
            'schoolIdentity' => $schoolIdentity,
            'isNursery' => $isNursery,
            'nurseryAssessments' => $isNursery
                ? \App\Models\Academics\NurseryAssessment::query()
                    ->where('exam_id', $exam->id)
                    ->where('student_id', (int) $learner->id)
                    ->get()
                    ->keyBy('domain')
                : collect(),
            'isALevel' => $isALevel,
            'showDivision' => $showDivision,
            'showPoints' => $showPoints,
            'teacherComment' => $teacherComment,
            'headTeacherComment' => $headTeacherComment,
            'showAgg' => $showAgg,
            'eotDivision' => $eotDivision,
            'eotMarkBySubject' => $eotMarkBySubject,
            'streamName' => $streamName,
            'streamPos' => $streamPos,
            'streamTotal' => $streamTotal,
            'midSittings' => $midSittings,
            'logoPath' => $logoPath,
            'countMissingAsZero' => $countMissingAsZero,
            'eotCells' => $eotCells,
            'monthlySets' => $monthlySets,
            'termSets' => $termSets,
            'setColumns' => $setColumns,
            'divisionRanges' => $divisionRanges,
            'rankingBasis' => $rankingBasis,
            'incompleteSets' => $incompleteSets,
            'noCombinedMark' => $noCombinedMark,
            'withheldSets' => $withheldSets,
            'withheldSentence' => $withheldSentence,
            'sampleForApproval' => $sample,
            'printBlackAndWhite' => $printBlackAndWhite,
            'compositionLine' => $compositionLine,
            'ownReports' => $ownReports,
        ];
        if ($this->collectingClassCards) {
            $this->classCardHtml[] = view($view['view'], $data)->render();

            return '';
        }

        $pdf = Pdf::loadView($view['view'], $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * Counting sets where this learner has a missing mark or an absence.
     * A dashed subject still counts here when the underlying mark is missing.
     *
     * @return list<string>
     */
    public function incompleteSetNames(ResultSheet $sheet, int $studentId): array
    {
        $names = [];
        foreach ($sheet->sittings as $sitting) {
            if (! $sitting->contributesToReport) {
                continue;
            }
            $student = $sitting->student($studentId);
            if ($student === null) {
                continue;
            }
            foreach ($student->cells as $cell) {
                if ($cell->namesIncompleteSet()) {
                    $names[] = $sitting->name;
                    break;
                }
            }
        }

        return $names;
    }

    /**
     * Counting-set names, keyed by learner id, for the class report list.
     *
     * @return array<int, list<string>>
     */
    public function incompleteSetsForClass(int $schoolId, StandardLink $stdLink, ?int $academicTermId = null): array
    {
        $exam = $this->resolveExam($schoolId, $stdLink, $academicTermId);
        if ($exam === null) {
            return [];
        }
        $sheet = $this->classSheet(
            $schoolId,
            (int) $stdLink->section_id,
            (int) $exam->academic_term_id,
            (int) $stdLink->standard_id,
        );
        if (! $sheet instanceof ResultSheet) {
            return [];
        }
        $out = [];
        foreach ($sheet->students as $student) {
            $names = $this->incompleteSetNames($sheet, $student->studentId);
            if ($names !== []) {
                $out[$student->studentId] = $names;
            }
        }

        return $out;
    }

    private function classSheet(int $schoolId, int $sectionId, int $academicTermId, int $standardId, ?int $studentId = null, ?array $onlyExamTypeIds = null): ?ResultSheet
    {
        if ($sectionId < 1 || $academicTermId < 1) {
            return null;
        }

        $cacheKey = null;
        if ($this->classSheetCache !== null) {
            $cacheKey = $schoolId.'|'.$sectionId.'|'.$academicTermId.'|'.$standardId.'|'.json_encode($onlyExamTypeIds);
            if (array_key_exists($cacheKey, $this->classSheetCache)) {
                return $this->classSheetCache[$cacheKey];
            }
            $studentId = null;
        }

        $sheet = app(ResultsService::class)->sheet(
            new ResultsQuery(
                schoolId: $schoolId,
                sectionId: $sectionId,
                academicTermId: $academicTermId,
                studentId: $studentId,
                examTypeIds: $onlyExamTypeIds,
            ),
            $this->resultsPolicy($schoolId, $standardId, $sectionId, $academicTermId),
        );
        if ($cacheKey !== null) {
            $this->classSheetCache[$cacheKey] = $sheet;
        }

        return $sheet;
    }

    /**
     * Same basis the class list already used: points when the school has
     * them, otherwise the mark total. A package replaces the scale and the
     * division rule. This choice does not add a curriculum.
     *
     * An unreleased card counts a blank as zero. A release stamped with the
     * older rule keeps that card on the older maths.
     */
    private function resultsPolicy(int $schoolId, int $standardId, int $sectionId, int $academicTermId): ResultsPolicy
    {
        $scoring = app(\App\Services\Reports\ReportScoring::class);

        return $scoring->policyForTerm($schoolId, $standardId, $sectionId, $academicTermId);
    }

    /**
     * Grade rows copied onto the release, so the printed grade uses the
     * bands from release day.
     *
     * @param  list<array<string, mixed>>  $bands
     * @return \Illuminate\Support\Collection<int, \App\Models\Academics\SchoolGradingSystem>
     */
    private function storedGradingRows(int $schoolId, int $standardId, array $bands): \Illuminate\Support\Collection
    {
        return collect($bands)->map(function (array $band) use ($schoolId, $standardId) {
            return new \App\Models\Academics\SchoolGradingSystem([
                'school_id' => $schoolId,
                'standard_id' => $standardId,
                'grade' => $band['grade'] ?? '',
                'points' => $band['points'] ?? null,
                'min_score' => $band['min_score'] ?? null,
                'max_score' => $band['max_score'] ?? null,
                'remark' => $band['remark'] ?? null,
            ]);
        });
    }

    /**
     * Drop an exam set that nobody in the class has a mark or an absence for.
     * A subject inside a set that has started stays, so its blank can show.
     *
     * @param  Collection<int, Exam>  $exams
     * @return Collection<int, Exam>
     */
    private function examsOnStartedSets(int $schoolId, Collection $exams, bool $countMissingAsZero): Collection
    {
        if (! $countMissingAsZero || $exams->isEmpty()) {
            return $exams;
        }

        $marks = Marks::query()
            ->where('school_id', $schoolId)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get();
        $active = \App\Services\Results\CountedExamTypes::typeIds($exams, $marks);

        return $exams
            ->filter(fn ($row) => isset($active[(int) $row->exam_type_id]))
            ->values();
    }

    /**
     * One column per exam set, in the sheet's date order.
     * The heading is the set's own name.
     *
     * @return list<array{label: string, cells: array<int, \App\Services\Results\ResultCell>, total: ?float, aggregate: ?int, pos: ?int, of: int, division: string}>
     */
    private function sittingColumns(ResultSheet $sheet, int $studentId, ?bool $contributing): array
    {
        $rows = [];
        foreach ($sheet->sittings as $sitting) {
            if ($contributing !== null && $sitting->contributesToReport !== $contributing) {
                continue;
            }
            $student = $sitting->student($studentId);
            if (! $student instanceof ResultStudent) {
                continue;
            }
            $rows[] = [
                'typeId' => $sitting->examTypeId,
                'label' => $sitting->name,
                'cells' => $student->cells,
                'total' => $student->total,
                'aggregate' => $student->aggregate,
                'pos' => $student->classPosition,
                'of' => $student->classOutOf,
                'division' => $student->division ?? '-',
            ];
        }

        return $rows;
    }

    /**
     * One contributing mark per subject. Several rows for the same subject
     * keep the latest scheduled_at, then the highest exam id.
     *
     * @param  Collection<int, Marks>  $marks
     * @param  Collection<int, Exam>  $exams
     * @return array<int, Marks>
     */
    private function chosenMarksBySubject(Collection $marks, Collection $exams): array
    {
        $byId = $exams->keyBy('id');
        $chosen = [];
        foreach ($marks as $mark) {
            if ($mark->marks === null || ! $byId->has($mark->exam_id)) {
                continue;
            }
            $subjectId = (int) $mark->subject_id;
            $current = $chosen[$subjectId] ?? null;
            if ($current === null || $this->markIsLater($mark, $current, $byId)) {
                $chosen[$subjectId] = $mark;
            }
        }

        return $chosen;
    }

    /**
     * Marks the card prints. A numeric mark is the chosen row. An absence
     * prints ABS. A subject on this report with no mark prints Missing.
     * The total comes from the results sheet, which counts both as 0.
     *
     * @param  array<int, Marks>  $chosen
     * @param  Collection<int, Marks>  $marks
     * @param  Collection<int, Exam>  $exams
     * @return array<int, Marks>
     */
    private function displayMarksBySubject(array $chosen, Collection $marks, Collection $exams): array
    {
        $byId = $exams->keyBy('id');
        $display = $chosen;
        foreach ($marks as $mark) {
            if (! $byId->has($mark->exam_id) || ($mark->marks !== null && ! $mark->isAbsent())) {
                continue;
            }
            $subjectId = (int) $mark->subject_id;
            if (! isset($display[$subjectId])) {
                $display[$subjectId] = $mark;
            }
        }

        foreach ($exams as $exam) {
            $subjectId = (int) $exam->subject_id;
            if ($subjectId < 1 || isset($display[$subjectId])) {
                continue;
            }
            $display[$subjectId] = new Marks([
                'subject_id' => $subjectId,
                'exam_id' => (int) $exam->id,
                'school_id' => (int) $exam->school_id,
                'marks' => null,
                'absent' => false,
            ]);
        }

        return $display;
    }

    private function markIsLater(Marks $candidate, Marks $current, Collection $exams): bool
    {
        $candidateAt = $exams[$candidate->exam_id]->scheduled_at;
        $currentAt = $exams[$current->exam_id]->scheduled_at;
        $candidateTs = $candidateAt ? $candidateAt->getTimestamp() : 0;
        $currentTs = $currentAt ? $currentAt->getTimestamp() : 0;
        if ($candidateTs !== $currentTs) {
            return $candidateTs > $currentTs;
        }

        return (int) $candidate->exam_id > (int) $current->exam_id;
    }

    /**
     * @param  array<int, Marks>  $marksBySubject
     * @return array{points: int, counted: int}|null
     */
    private function sumPoints(array $marksBySubject, int $schoolId, int $standardId): ?array
    {
        $scale = app(GradingScale::class);
        $sum = 0;
        $counted = 0;
        foreach ($marksBySubject as $mark) {
            $points = $scale->pointsFor($schoolId, $standardId, (float) $mark->marks);
            if ($points === null) {
                continue;
            }
            $sum += $points;
            $counted++;
        }

        return $counted ? ['points' => $sum, 'counted' => $counted] : null;
    }

    /**
     * Per-school header/footer identity for report cards.
     * Never hardcode another school's name, address, phones, or motto.
     *
     * @return array{
     *     name: string,
     *     category_subtitle: ?string,
     *     address: ?string,
     *     phones: list<string>,
     *     phones_line: ?string,
     *     uneb_center_number: ?string,
     *     motto: ?string,
     *     footer_line: string
     * }
     */
    public function resolveSchoolIdentity(?School $school): array
    {
        if (! $school) {
            return [
                'name' => '',
                'category_subtitle' => null,
                'address' => null,
                'phones' => [],
                'phones_line' => null,
                'uneb_center_number' => null,
                'motto' => null,
                'footer_line' => '',
            ];
        }

        $details = SchoolDetail::where('school_id', $school->id)
            ->whereIn('meta_key', ['moto', 'landline_no'])
            ->pluck('meta_value', 'meta_key');

        $categorySubtitles = [
            'nursery' => 'Nursery',
            'primary' => 'Primary',
            'primary_nursery' => 'Nursery And Primary',
            'o_level' => 'O-Level',
            'o_a_level' => 'O-Level And A-Level',
        ];
        $categoryKey = $school->school_category;
        $categorySubtitle = ($categoryKey && isset($categorySubtitles[$categoryKey]))
            ? '('.$categorySubtitles[$categoryKey].')'
            : null;

        $address = $this->nonEmptyMeta($school->address);

        $phones = [];
        foreach ([$school->phone, $details['landline_no'] ?? null] as $raw) {
            foreach (preg_split('/\s*\/\s*/', (string) $raw) ?: [] as $part) {
                $phone = $this->nonEmptyMeta(trim($part));
                if ($phone !== null && ! in_array($phone, $phones, true)) {
                    $phones[] = $phone;
                }
            }
        }
        $phonesLine = $phones === [] ? null : 'Tel: '.implode(' / ', $phones);

        $uneb = $this->nonEmptyMeta($school->uneb_center_number);
        // Column first, legacy meta as the transition fallback (see School::mottoText()).
        $motto = $this->nonEmptyMeta($school->mottoText());

        $footer = $school->name;
        if ($uneb !== null) {
            $footer .= ', UNEB Center No. '.$uneb;
        }
        if ($phonesLine !== null) {
            $footer .= ' '.$phonesLine;
        }

        return [
            'name' => $school->name,
            'category_subtitle' => $categorySubtitle,
            'address' => $address,
            'phones' => $phones,
            'phones_line' => $phonesLine,
            'uneb_center_number' => $uneb,
            'motto' => $motto,
            'footer_line' => $footer,
        ];
    }

    private function nonEmptyMeta(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        if ($trimmed === '' || $trimmed === '-') {
            return null;
        }

        return $trimmed;
    }

    /**
     * Resolve the school's uploaded logo to a local filesystem path for
     * DomPDF (which needs a real path, not a public URL). Uses the same
     * SchoolDetail (meta_key=school_logo) record every other part of the
     * app reads via Auth::user()->SchoolLogoPath — not a hardcoded asset.
     * Returns null (report card renders without a logo) if none uploaded
     * or the stored file is missing from disk.
     */
    public function resolveLogoPath(int $schoolId): ?string
    {
        // SchoolObserver seeds every school with a placeholder school_logo
        // row (meta_value='-') at creation; the real upload flow always
        // updateOrCreate()s a single row per school+meta_key, but order by
        // most-recent and explicitly skip the '-' sentinel defensively.
        $meta = SchoolDetail::where('school_id', $schoolId)
            ->where('meta_key', 'school_logo')
            ->latest('id')
            ->first();

        if (! $meta || ! $meta->meta_value || $meta->meta_value === '-') {
            return null;
        }

        $path = Storage::disk('public')->path($meta->meta_value);

        return file_exists($path) ? $path : null;
    }
}
