<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\Academics\Exam;
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

    public function resolveExam(int $schoolId, StandardLink $stdLink): ?Exam
    {
        return Exam::where('school_id', $schoolId)
            ->where('section_id', $stdLink->section_id)
            ->where('standard_id', $stdLink->standard_id)
            ->whereHas('examType', fn ($q) => $q->where('contributes_to_report_total', 1))
            ->latest()
            ->first();
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
        $studentIds = $this->studentIds($exam);
        if ($studentIds->isEmpty()) {
            return [];
        }

        $helper = app(StudentReportHelperService::class);
        $scopedExamIds = Exam::where('school_id', $schoolId)
            ->where('section_id', $exam->section_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('academic_term_id', $exam->academic_term_id)
            ->pluck('id');

        $learners = User::whereIn('id', $studentIds)
            ->with(['marks' => fn ($q) => $q->whereIn('exam_id', $scopedExamIds)->with('exam.examType')])
            ->where('usergroup_id', 6)
            ->get();

        $learners = $helper->position($learners, $exam);

        $map = [];
        foreach ($learners as $l) {
            $map[$l->id] = $l->position;
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
        ?string $templateKey = null
    ): string {
        $exam = $this->resolveExam($schoolId, $stdLink);
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
    public function subjectFillCounts(int $schoolId, StandardLink $stdLink): ?array
    {
        $exam = $this->resolveExam($schoolId, $stdLink);
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

        $midExams = $exams->filter(fn ($e) => $e->examType->code === 'MID')->sortBy('scheduled_at')->values();
        $eotExams = $exams->filter(fn ($e) => $e->examType->code !== 'MID')->values();
        $allExamColumns = $midExams->merge($eotExams);

        $controls = ['SUBJECT', 'OUT OF'];
        foreach ($midExams as $ex) {
            $controls[] = strtoupper($ex->scheduled_at->format('M')).' MID';
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

        $divisionScale = static function (int $points): string {
            if ($points <= 12) {
                return '1';
            }
            if ($points <= 24) {
                return '2';
            }
            if ($points <= 28) {
                return '3';
            }
            if ($points <= 32) {
                return '4';
            }

            return 'U';
        };

        $aggregatePoints = static function ($learner, $exam, $subjects, $gradingSystem) {
            $sum = 0;
            $counted = 0;
            foreach ($subjects as $subject) {
                if ($learner->marks->where('subject_id', $subject->id)->isEmpty()) {
                    continue;
                }
                $mark = $learner->marks->where('subject_id', $subject->id)->firstWhere('exam_id', $exam->id);
                if ($mark && $mark->marks !== null) {
                    $g = $gradingSystem->first(fn ($gs) => $gs->min_score <= (float) $mark->marks && $gs->max_score >= (float) $mark->marks);
                    if ($g && $g->points !== null) {
                        $sum += (int) $g->points;
                        $counted++;
                    }
                }
            }

            return $counted ? ['points' => $sum, 'counted' => $counted] : null;
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
            $points = $aggregatePoints($learner, $midExam, $subjects, $gradingSystem);

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
                'division' => $points ? $divisionScale($points['points']) : '-',
            ];
        }

        $firstEot = $eotExams->first();
        $eotPoints = $firstEot ? $aggregatePoints($learner, $firstEot, $subjects, $gradingSystem) : null;
        $eotDivision = $eotPoints ? $divisionScale($eotPoints['points']) : '-';

        $streamPos = null;
        $streamTotal = null;
        if ($streamName && $firstEot) {
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
            $ranked = [];
            foreach ($streamRows as $rankedSid) {
                $ranked[$rankedSid] = (int) round(\App\Models\Academics\Marks::where('student_id', $rankedSid)->where('exam_id', $firstEot->id)->sum('marks'));
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

        $total = $learner->marks
            ? $learner->marks->filter(fn ($m) => $m->exam?->examType?->contributes_to_report_total)->sum('marks')
            : 0;
        $examinedSubjectCount = $subjects->filter(fn ($s) => $learner->marks->where('subject_id', $s->id)->isNotEmpty())->count();
        $grade = $helper->grade($learner, $exam);
        $teacherComment = $standard
            ? $svc->commentFor((int) $total, $standard->name, $learner->id, $exam->id)
            : '';
        $headTeacherComment = $standard
            ? $svc->headTeacherCommentFor((int) $total, $standard->name, $learner->id, $exam->id, $teacherComment)
            : '';

        $school = \App\Models\School::find($schoolId);
        $view = self::TEMPLATES[$templateKey ?? $school->report_template ?? ''] ?? self::TEMPLATES['formal'];
        $logoPath = $this->resolveLogoPath($schoolId);

        $pdf = Pdf::loadView($view['view'], [
            'subjects' => $subjects, 'learner' => $learner, 'controls' => $controls,
            'class_name' => Section::find($stdLink->section_id)->name,
            'grading_system' => $gradingSystem,
            'fees' => collect(), 'nextTerm' => AcademicTerm::where('school_id', $schoolId)->where('starts_on', '>', now())->first(),
            'totalLearners' => $totalLearners, 'myPos' => $myPos,
            'allExamColumns' => $allExamColumns, 'midExams' => $midExams, 'eotExams' => $eotExams,
            'midCount' => $midExams->count(), 'eotCount' => $eotExams->count(),
            'stdLink' => $stdLink,
            'total' => $total, 'grade' => $grade, 'examinedSubjectCount' => $examinedSubjectCount,
            'school' => $school,
            'isNursery' => $isNursery, 'nurseryAssessments' => collect(),
            'teacherComment' => $teacherComment,
            'headTeacherComment' => $headTeacherComment,
            'showAgg' => $showAgg,
            'midStats' => $midStats,
            'eotDivision' => $eotDivision,
            'streamName' => $streamName,
            'streamPos' => $streamPos,
            'streamTotal' => $streamTotal,
            'logoPath' => $logoPath,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
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
        $meta = \App\Models\SchoolDetail::where('school_id', $schoolId)
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
