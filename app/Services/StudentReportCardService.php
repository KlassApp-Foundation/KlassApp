<?php

namespace App\Services;

use App\Helpers\GradingHelper;
use App\Models\Academics\Exam;
use App\Models\Academics\NurseryAssessment;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Promotion;
use App\Models\School;
use App\Models\Section;
use App\Models\Teacherlink;
use App\Models\User;
use App\Services\ToshiActionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Canonical per-student report-card PDF generation.
 *
 * Shared by Admin DownloadStudentReport, WhatsAppController@report, and Toshi tools.
 * Always renders admin.marks.student-report (not the legacy WhatsApp-only blade).
 */
class StudentReportCardService
{
    public function __construct(
        private StudentReportHelperService $helper
    ) {}

    /**
     * Build view data for admin.marks.student-report.
     *
     * @return array{success: bool, message?: string, data?: array<string, mixed>}
     */
    public function buildViewData(int $schoolId, User $learner, int $sectionId, Exam $exam, ?School $school = null): array
    {
        if ((int) $exam->school_id !== (int) $schoolId) {
            return ['success' => false, 'message' => 'Exam does not belong to this school.'];
        }

        if ((int) $learner->school_id !== (int) $schoolId || (int) $learner->usergroup_id !== 6) {
            return ['success' => false, 'message' => 'Student not found in this school.'];
        }

        $exam->loadMissing(['marks', 'examType', 'academicTerm']);

        $learner = $this->helper->learner($schoolId, $learner, $exam);
        if (! $learner) {
            return ['success' => false, 'message' => 'This student has no marks recorded for this exam yet.'];
        }

        $subjects = $this->helper->subjects($schoolId, $sectionId, $learner, $exam);
        $exams = $this->helper->exam($schoolId, $exam);

        $controls = ['SUBJECT', 'OUT OF'];
        $cals = ['AVG'];
        $uniqueExamTypes = $exams->pluck('examType')->unique()->count();
        $marksFromSubject = [];
        foreach ($exams as $ex) {
            $code = strtoupper($ex->examType->code ?? '');
            if ($code !== '' && ! in_array($code, $controls, true)) {
                $controls[] = $code;
                $marksFromSubject[] = $ex;
                if ($uniqueExamTypes > 1) {
                    $controls = array_merge($controls, $cals);
                }
            }
        }
        $controls = array_merge($controls, ['DIVISION', 'TEACHER', 'REMARK']);

        $isNursery = false;
        $nurseryAssessments = collect();
        $standard = $learner->studentAcademicLatest?->standardLink?->standard;
        if ($standard) {
            $levelType = GradingHelper::levelTypeForStandard($standard);
            $isNursery = ($levelType === 'nursery');
            if ($isNursery) {
                $nurseryAssessments = NurseryAssessment::where('student_id', $learner->id)
                    ->where('academic_term_id', $exam->academic_term_id)
                    ->get()
                    ->keyBy('domain');
            }
        }

        $marks = collect();
        $total = 0;
        $examsDone = 0;
        $grade = null;

        if (! $isNursery) {
            $marks = $exam->marks->where('student_id', $learner->id);
            $total = $learner->marks ? $learner->marks->sum('marks') : 0;
            $examsDone = $this->helper->examsDone($schoolId, $exam);
            $grade = $this->helper->grade($learner, $exam);
        }

        $promotion = Promotion::where('school_id', $schoolId)
            ->where('user_id', $learner->id)
            ->where('current_section_id', $exam->section_id)
            ->value('comments');

        $class_name = Section::find($sectionId)?->name ?? '';
        $grading_system = SchoolGradingSystem::where('school_id', $schoolId)->get();

        $adminStub = new User;
        $adminStub->school_id = $schoolId;
        $fees = $this->helper->fees($adminStub, $sectionId);

        $learnersQuery = $this->helper->totalStudentsInClass($schoolId, $sectionId);
        $totalLearners = $learnersQuery->count();
        $learners = $this->helper->position(
            $this->helper->totalMarks($learnersQuery->get())->sortByDesc('total')->values()
        );

        $nextTerm = AcademicTerm::where('school_id', $schoolId)
            ->where('starts_on', '>', now())
            ->first();

        $school = $school ?? School::find($schoolId);
        $myPos = $learners->where('id', $learner->id)->value('position');

        $academicYearName = $this->resolveAcademicYearName($learner, $exam);

        return [
            'success' => true,
            'data' => compact(
                'subjects',
                'learner',
                'controls',
                'class_name',
                'grading_system',
                'fees',
                'nextTerm',
                'totalLearners',
                'myPos',
                'exams',
                'marks',
                'examsDone',
                'marksFromSubject',
                'total',
                'uniqueExamTypes',
                'grade',
                'promotion',
                'school',
                'isNursery',
                'nurseryAssessments',
                'academicYearName'
            ),
        ];
    }

    public function renderPdf(int $schoolId, User $learner, int $sectionId, Exam $exam, ?School $school = null): array
    {
        $built = $this->buildViewData($schoolId, $learner, $sectionId, $exam, $school);
        if (! $built['success']) {
            return $built;
        }

        $this->ensureDomPdfDirs();

        /** @var DomPdf $pdf */
        $pdf = Pdf::loadView('admin.marks.student-report', $built['data']);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
            'isJavascriptEnabled' => true,
            'tempDir' => storage_path('app/dompdf'),
            'fontDir' => storage_path('app/dompdf/fonts'),
            'fontCache' => storage_path('app/dompdf/fonts'),
        ]);

        return [
            'success' => true,
            'pdf' => $pdf,
            'filename' => $this->filenameFor($built['data']['learner']),
            'data' => $built['data'],
        ];
    }

    /**
     * HTTP download response (admin portal).
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function download(int $schoolId, User $learner, int $sectionId, Exam $exam, ?School $school = null)
    {
        $result = $this->renderPdf($schoolId, $learner, $sectionId, $exam, $school);
        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return $result['pdf']->download($result['filename']);
    }

    /**
     * Save PDF under public storage for WhatsApp / Toshi URL delivery.
     *
     * @return array{success: bool, message?: string, path?: string, url?: string, filename?: string, academic_year?: string}
     */
    public function saveToPublic(int $schoolId, User $learner, int $sectionId, Exam $exam, ?School $school = null): array
    {
        $result = $this->renderPdf($schoolId, $learner, $sectionId, $exam, $school);
        if (! $result['success']) {
            return ['success' => false, 'message' => $result['message']];
        }

        $filename = 'report_'.$learner->id.'_'.time().'.pdf';
        $dir = storage_path('app/public/reports');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir.'/'.$filename;
        $result['pdf']->save($path);

        $academicYear = $result['data']['academicYearName'] ?? null;

        return [
            'success' => true,
            'path' => $path,
            'url' => url('storage/reports/'.$filename),
            'filename' => $filename,
            'download_filename' => $result['filename'],
            'academic_year' => $academicYear,
            'message' => 'Report card generated for '.($learner->name ?? 'student')
                .($academicYear ? ' ('.$academicYear.')' : '')
                .'. Download: '.url('storage/reports/'.$filename),
        ];
    }

    /**
     * School-admin / deputy: student must be in actor's effective school.
     *
     * @return array{success: bool, message: string, url?: string, filename?: string}
     */
    public function generateForSchoolStaff(User $actor, int $studentId, int $examId): array
    {
        $schoolId = ToshiActionService::getEffectiveSchoolId($actor);
        if (! $schoolId) {
            return ['success' => false, 'message' => 'No school assigned.'];
        }

        $exam = Exam::with(['examType', 'marks'])->find($examId);
        if (! $exam || (int) $exam->school_id !== (int) $schoolId) {
            return ['success' => false, 'message' => 'Exam not found in your school.'];
        }

        $learner = User::where('id', $studentId)
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->first();
        if (! $learner) {
            return ['success' => false, 'message' => 'Student not found in your school.'];
        }

        $sectionId = (int) ($exam->section_id ?: $this->sectionIdForStudent($learner));
        if (! $sectionId) {
            return ['success' => false, 'message' => 'Could not resolve student class/section.'];
        }

        return $this->saveToPublic((int) $schoolId, $learner, $sectionId, $exam, $actor->school);
    }

    /**
     * Teacher: assigned class only (class teacher OR Teacherlink on student's standardLink).
     *
     * @return array{success: bool, message: string, url?: string, filename?: string}
     */
    public function generateForTeacher(User $teacher, int $studentId, int $examId): array
    {
        if ((int) $teacher->usergroup_id !== 5 || ! $teacher->school_id) {
            return ['success' => false, 'message' => 'Teacher authorization required.'];
        }

        $exam = Exam::with(['examType', 'marks'])->find($examId);
        if (! $exam || (int) $exam->school_id !== (int) $teacher->school_id) {
            return ['success' => false, 'message' => 'Exam not found in your school.'];
        }

        $learner = User::with(['studentAcademicLatest.standardLink'])
            ->where('id', $studentId)
            ->where('school_id', $teacher->school_id)
            ->where('usergroup_id', 6)
            ->first();
        if (! $learner) {
            return ['success' => false, 'message' => 'Student not found in your school.'];
        }

        if (! $this->teacherCanAccessStudent($teacher, $learner)) {
            return ['success' => false, 'message' => 'You can only generate report cards for students in your assigned class.'];
        }

        $sectionId = (int) ($exam->section_id ?: $this->sectionIdForStudent($learner));
        if (! $sectionId) {
            return ['success' => false, 'message' => 'Could not resolve student class/section.'];
        }

        return $this->saveToPublic((int) $teacher->school_id, $learner, $sectionId, $exam, $teacher->school);
    }

    /**
     * WhatsApp parent-pull path: after parent/approval checks, generate via the same PDF pipeline.
     *
     * @return array{success: bool, message?: string, path?: string, url?: string, filename?: string, partial?: bool}
     */
    public function generateForWhatsAppParent(User $student, AcademicTerm $term, School $school, bool $partial = false): array
    {
        $exam = Exam::where('school_id', $school->id)
            ->where('academic_term_id', $term->id)
            ->whereHas('marks', fn ($q) => $q->where('student_id', $student->id))
            ->with(['examType', 'marks' => fn ($q) => $q->where('student_id', $student->id)])
            ->first();

        if (! $exam) {
            return ['success' => false, 'message' => 'No exam marks found for this term.'];
        }

        $sectionId = (int) ($exam->section_id ?: $this->sectionIdForStudent($student));
        if (! $sectionId) {
            return ['success' => false, 'message' => 'Could not resolve student class/section.'];
        }

        $saved = $this->saveToPublic((int) $school->id, $student, $sectionId, $exam, $school);
        if ($saved['success']) {
            $saved['partial'] = $partial;
        }

        return $saved;
    }

    /**
     * Mirror homework/assignment review assignment: class teacher OR any Teacherlink on the class.
     */
    public function teacherCanAccessStudent(User $teacher, User $student): bool
    {
        if ((int) $teacher->school_id !== (int) $student->school_id) {
            return false;
        }

        $student->loadMissing('studentAcademicLatest.standardLink');
        $link = $student->studentAcademicLatest?->standardLink;
        if ($link === null) {
            return false;
        }

        if ((int) $link->class_teacher_id === (int) $teacher->id) {
            return true;
        }

        return Teacherlink::where('teacher_id', $teacher->id)
            ->where('standardLink_id', $link->id)
            ->exists();
    }

    private function sectionIdForStudent(User $student): ?int
    {
        $student->loadMissing('studentAcademicLatest.standardLink');

        return $student->studentAcademicLatest?->standardLink?->section_id;
    }

    private function resolveAcademicYearName(User $learner, Exam $exam): string
    {
        $yearId = $exam->academic_year_id
            ?? $learner->marks->first()?->exam?->academic_year_id;

        if ($yearId) {
            $name = AcademicYear::where('id', $yearId)->value('name');
            if ($name) {
                return (string) $name;
            }
        }

        $term = $learner->marks->first()?->exam?->academicTerm;
        if ($term && $term->academic_year_id) {
            $name = AcademicYear::where('id', $term->academic_year_id)->value('name');
            if ($name) {
                return (string) $name;
            }
        }

        return '—';
    }

    private function filenameFor(User $learner): string
    {
        $first = $learner->userprofile->firstname ?? 'student';
        $last = $learner->userprofile->lastname ?? 'unknown';

        return str_replace(' ', '_', $first.'_'.$last).'_report_card.pdf';
    }

    private function ensureDomPdfDirs(): void
    {
        if (! is_dir(storage_path('app/dompdf/fonts'))) {
            mkdir(storage_path('app/dompdf/fonts'), 0775, true);
        }
    }
}
