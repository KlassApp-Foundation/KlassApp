<?php

namespace App\Services;

use App\Contracts\Results\DivisionRule;
use App\Contracts\Results\GradingScale;
use App\Contracts\Results\ReleaseGate;
use App\Contracts\Results\ReportTemplates;
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
     * Available report-card templates: key => [label, Blade view].
     */
    public const TEMPLATES = [
        'formal' => ['label' => 'Formal', 'view' => 'admin.marks.report-templates.formal'],
        'warm' => ['label' => 'Warm', 'view' => 'admin.marks.report-templates.warm'],
    ];

    /**
     * MID exam column header for report control rows (e.g. "APR MID").
     * scheduled_at is optional on exams — CT/admin create forms allow null.
     */
    public static function midExamControlColumnLabel(Exam $exam): string
    {
        if ($exam->scheduled_at) {
            return strtoupper($exam->scheduled_at->format('M')).' MID';
        }

        return 'MID';
    }

    /**
     * MID exam row label in monthly results tables (full month name).
     */
    public static function midExamMonthRowLabel(Exam $exam): string
    {
        if ($exam->scheduled_at) {
            return strtoupper($exam->scheduled_at->format('F'));
        }

        return 'MID TERM';
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
        return Exam::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $stdLink->section_id)
            ->where('standard_id', $stdLink->standard_id)
            ->where('academic_term_id', $academicTermId)
            ->whereHas('examType', fn ($q) => $q->where('contributes_to_report_total', 1))
            ->orderBy('id')
            ->first();
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

    public function computePositionMap(Exam $exam, int $schoolId): array
    {
        $sheet = $this->classSheet($schoolId, (int) $exam->section_id, (int) $exam->academic_term_id, (int) $exam->standard_id);
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
     * @throws \RuntimeException when no contributing exam exists for the class
     */
    public function pdfForStudent(
        int $schoolId,
        StandardLink $stdLink,
        User $learner,
        ?string $templateKey = null,
        ?int $academicTermId = null
    ): string {
        $exam = $this->resolveExam($schoolId, $stdLink, $academicTermId);
        if (! $exam) {
            throw new \RuntimeException('No EOT exam found for this class.');
        }

        $allStudentIds = $this->studentIds($exam);
        $helper = app(StudentReportHelperService::class);
        $svc = new ReportCardCommentService;
        $positionMap = $this->computePositionMap($exam, $schoolId);
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
            $templateKey
        );
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
        ?string $templateKey = null
    ): string {
        $learner = User::find($sid);
        $learner = $helper->learner($schoolId, $learner, $exam);
        $subjects = $helper->subjects($schoolId, $stdLink->section_id, $learner, $exam);
        $subjects = \App\Models\Subject::sortByReportOrder($subjects);
        $exams = $helper->exam($schoolId, $exam);
        $exams = $exams->filter(fn ($e) => $this->typeIncluded($schoolId, $e));

        $midExams = $exams->filter(fn ($e) => $e->examType->code === 'MID')->sortBy('scheduled_at')->values();
        $eotExams = $exams->filter(fn ($e) => $e->examType->code !== 'MID')->values();
        $allExamColumns = $midExams->merge($eotExams);

        $controls = ['SUBJECT', 'OUT OF'];
        foreach ($midExams as $ex) {
            $controls[] = self::midExamControlColumnLabel($ex);
        }
        foreach ($eotExams as $ex) {
            $controls[] = 'EOT';
        }
        $controls = array_merge($controls, ['DIVISION', 'TEACHER', 'REMARK']);

        $standard = $learner->studentAcademicLatest?->standardLink?->standard;
        $isNursery = $standard && \App\Helpers\GradingHelper::levelTypeForStandard($standard) === 'nursery';

        $standardName = $standard?->name ?? '';

        // Use grading_style if set; fall back to legacy name-based logic when NULL.
        if ($standard && $standard->grading_style !== null) {
            $showAgg = $standard->grading_style === 'aggregate';
        } else {
            $showAgg = ! $isNursery && ! in_array($standardName, ['primary_lower'], true);
        }

        $gradingSystem = \App\Models\Academics\SchoolGradingSystem::where('school_id', $schoolId)
            ->where('standard_id', $stdLink->standard_id)
            ->orderBy('min_score', 'desc')
            ->get();

        $division = app(DivisionRule::class);
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

        $midStats = [];
        foreach ($midExams as $midExam) {
            $nonNullMarks = $learner->marks->filter(fn ($m) => $m->exam_id === $midExam->id && $m->marks !== null);
            $total = $nonNullMarks->isNotEmpty() ? (int) round($nonNullMarks->sum('marks')) : null;
            $points = $this->sumPoints(
                $this->chosenMarksBySubject($learner->marks, collect([$midExam])),
                $schoolId,
                (int) $stdLink->standard_id
            );

            // Monthly position is class-wide: rank against every student who
            // sat this section's MID exam for the month. A stream is a subset
            // of the class, so it must never be the monthly pool, and stream
            // labels are reused across grade levels, so pooling by stream
            // would inflate the denominator (e.g. 236 across P.1-P.3 instead
            // of this class's 96).
            $poolExamIds = collect([$midExam->id]);

            $studentIds = \App\Models\Academics\Marks::whereIn('exam_id', $poolExamIds)->whereNotNull('marks')->distinct('student_id')->pluck('student_id')->all();
            $ranked = [];
            foreach ($studentIds as $rankedSid) {
                $ranked[$rankedSid] = (int) round(\App\Models\Academics\Marks::where('student_id', $rankedSid)->whereIn('exam_id', $poolExamIds)->whereNotNull('marks')->sum('marks'));
            }
            arsort($ranked);
            $pos = 0;
            foreach (array_keys($ranked) as $i => $rankedSid) {
                if ($rankedSid === $learner->id) {
                    $pos = $i + 1;
                    break;
                }
            }
            $midStats[$midExam->id] = [
                'total' => $total,
                'pos' => $pos,
                'of' => count($ranked),
                'division' => $divisionLabel($points),
            ];
        }

        $contributing = $eotExams
            ->filter(fn ($row) => (int) $row->examType->contributes_to_report_total === 1)
            ->values();
        $eotMarkBySubject = $this->chosenMarksBySubject($learner->marks, $contributing);
        $eotPoints = $this->sumPoints($eotMarkBySubject, $schoolId, (int) $stdLink->standard_id);
        $eotDivision = $divisionLabel($eotPoints);

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
                $ranked[$rankedSid] = (int) round(array_sum(array_map(
                    fn ($mark) => (float) $mark->marks,
                    $chosen
                )));
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

        $total = array_sum(array_map(fn ($mark) => (float) $mark->marks, $eotMarkBySubject));
        $midSittings = [];
        $sheet = $this->classSheet($schoolId, (int) $stdLink->section_id, (int) $exam->academic_term_id, (int) $stdLink->standard_id, (int) $learner->id);
        $resultRow = $sheet?->student((int) $learner->id);
        if ($resultRow !== null) {
            $total = (float) ($resultRow->total ?? 0);
            $eotPoints = $resultRow->aggregate === null
                ? null
                : ['points' => $resultRow->aggregate, 'counted' => $resultRow->subjectsCounted];
            $eotDivision = $resultRow->division ?? '-';
            $myPos = $resultRow->classPosition ?? 0;
            $totalLearners = $resultRow->classOutOf;
            $streamPos = $resultRow->streamPosition;
            $streamTotal = $resultRow->streamOutOf > 0 ? $resultRow->streamOutOf : null;
            $midSittings = $this->midSittings($sheet, (int) $learner->id, $midExams);
        }
        $examinedSubjectCount = $subjects->filter(fn ($s) => $learner->marks->where('subject_id', $s->id)->isNotEmpty())->count();
        $grade = $helper->grade($learner, $exam);
        $teacherComment = $standard
            ? $svc->commentFor((int) $total, $standard->name, $learner->id, $exam->id)
            : '';
        $headTeacherComment = $standard
            ? $svc->headTeacherCommentFor((int) $total, $standard->name, $learner->id, $exam->id, $teacherComment)
            : '';

        $school = School::find($schoolId);
        $view = app(ReportTemplates::class)->resolve($templateKey, $school?->report_template);
        $logoPath = $this->resolveLogoPath($schoolId);
        $schoolIdentity = $this->resolveSchoolIdentity($school);

        $pdf = Pdf::loadView($view['view'], [
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
            'allExamColumns' => $allExamColumns, 'midExams' => $midExams, 'eotExams' => $eotExams,
            'midCount' => $midExams->count(), 'eotCount' => $eotExams->count(),
            'stdLink' => $stdLink,
            'total' => $total, 'grade' => $grade, 'examinedSubjectCount' => $examinedSubjectCount,
            'eotAgg' => $eotPoints['points'] ?? ($grade['agg'] ?? null),
            'school' => $school,
            'schoolIdentity' => $schoolIdentity,
            'isNursery' => $isNursery, 'nurseryAssessments' => collect(),
            'teacherComment' => $teacherComment,
            'headTeacherComment' => $headTeacherComment,
            'showAgg' => $showAgg,
            'midStats' => $midStats,
            'eotDivision' => $eotDivision,
            'eotMarkBySubject' => $eotMarkBySubject,
            'streamName' => $streamName,
            'streamPos' => $streamPos,
            'streamTotal' => $streamTotal,
            'midSittings' => $midSittings,
            'logoPath' => $logoPath,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    private function classSheet(int $schoolId, int $sectionId, int $academicTermId, int $standardId, ?int $studentId = null): ?ResultSheet
    {
        if ($sectionId < 1 || $academicTermId < 1) {
            return null;
        }

        return app(ResultsService::class)->sheet(
            new ResultsQuery(
                schoolId: $schoolId,
                sectionId: $sectionId,
                academicTermId: $academicTermId,
                studentId: $studentId,
            ),
            $this->resultsPolicy($schoolId, $standardId),
        );
    }

    /**
     * Same basis the class list already used: points when the school has
     * them, otherwise the mark total. A package replaces the scale and the
     * division rule. This choice does not add a curriculum.
     */
    private function resultsPolicy(int $schoolId, int $standardId): ResultsPolicy
    {
        $usesPoints = \App\Models\Academics\SchoolGradingSystem::query()
            ->where('school_id', $schoolId)
            ->where('standard_id', $standardId)
            ->get()
            ->contains(fn ($row) => \App\Helpers\GradingHelper::effectivePoints($row) !== null);

        return new ResultsPolicy(
            app(GradingScale::class),
            app(DivisionRule::class),
            $usesPoints ? ResultsPolicy::BASIS_AGGREGATE : ResultsPolicy::BASIS_TOTAL,
            higherIsBetter: ! $usesPoints,
        );
    }

    /**
     * One mid-term row per sitting, with every subject in that sitting.
     *
     * @param  Collection<int, Exam>  $midExams
     * @return list<array{label: string, cells: array<int, \App\Services\Results\ResultCell>, total: ?float, pos: ?int, of: int, division: string}>
     */
    private function midSittings(ResultSheet $sheet, int $studentId, Collection $midExams): array
    {
        $rows = [];
        foreach ($sheet->sittings as $sitting) {
            if ($sitting->contributesToReport) {
                continue;
            }
            $student = $sitting->student($studentId);
            if (! $student instanceof ResultStudent) {
                continue;
            }
            $sample = $midExams->first(fn ($exam) => (int) $exam->exam_type_id === $sitting->examTypeId);
            $rows[] = [
                'label' => $sample ? self::midExamMonthRowLabel($sample) : $sitting->name,
                'cells' => $student->cells,
                'total' => $student->total,
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
