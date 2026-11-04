<?php

namespace App\Services\StudentProfile;

use App\Helpers\GradingHelper;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\User;
use App\Services\Marks\ExamMarkWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Add marks for one student from the profile, as drafts.
 *
 * It is the same writer the teacher's marks form uses (ExamMarkWriter::save), so a draft here is
 * exactly a draft entered in the grid: it does not touch exams.status, does not create or move
 * a submission, does not release anything and does not message anyone. The subject still goes
 * through Submit, Approve and Release. A subject that is already submitted or locked is not
 * writable from here (it would be a correction), and is reported back instead of changed.
 */
class StudentMarksDraftService
{
    use AuthorizesStudentProfileActions;

    public const MAX_MARK = 100.0;

    public function __construct(
        private StudentEnrollment $enrollment,
        private ExamMarkWriter $writer,
    ) {}

    /**
     * Sittings (an exam type in a term) the student's class has exams for this year.
     *
     * @return list<array{key: string, exam_type_id: int, term_id: int, label: string}>
     */
    public function sittings(User $student): array
    {
        $sectionId = $this->sectionId($student);
        if ($sectionId === null) {
            return [];
        }

        return $this->classExams($student, $sectionId)
            ->groupBy(fn (Exam $e) => $e->exam_type_id.':'.$e->academic_term_id)
            ->map(fn ($group) => [
                'key' => $group->first()->exam_type_id.':'.$group->first()->academic_term_id,
                'exam_type_id' => (int) $group->first()->exam_type_id,
                'term_id' => (int) $group->first()->academic_term_id,
                'label' => $this->sittingLabel($group->first()),
            ])
            ->sortByDesc('term_id')
            ->values()
            ->all();
    }

    /**
     * One row per subject of the sitting, with the student's draft mark if there is one.
     *
     * @return list<array{exam_id: int, subject: string, status: string, mark: ?string, editable: bool}>
     */
    public function subjects(User $student, int $examTypeId, int $termId): array
    {
        $sectionId = $this->sectionId($student);
        if ($sectionId === null) {
            return [];
        }

        $exams = $this->classExams($student, $sectionId)
            ->filter(fn (Exam $e) => (int) $e->exam_type_id === $examTypeId && (int) $e->academic_term_id === $termId)
            ->values();
        $marks = Marks::query()
            ->where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->pluck('marks', 'exam_id');

        return $exams->map(function (Exam $exam) use ($marks) {
            $submitted = $this->writer->requiresCorrection($exam) || $this->isLocked($exam);
            $teacher = $exam->teacher?->display_name ?: $exam->teacher?->name;

            return [
                'exam_id' => (int) $exam->id,
                'subject' => $this->sentenceCase((string) ($exam->subject?->name ?: 'Subject')),
                'status' => $submitted ? 'Submitted' : ($teacher ? 'In progress · '.$teacher : 'No teacher yet'),
                'mark' => $marks->has($exam->id) ? $this->markText((float) $marks[$exam->id]) : null,
                'editable' => ! $submitted,
            ];
        })->all();
    }

    /**
     * @param  array<int|string, mixed>  $marks  exam id => mark ("" is left alone)
     * @return array{saved: int, skipped: list<array{subject: string, reason: string}>}
     *
     * @throws ValidationException
     */
    public function save(User $actor, User $student, int $examTypeId, int $termId, array $marks): array
    {
        $this->authorizeProfileAction($actor, $student);

        $sectionId = $this->sectionId($student);
        if ($sectionId === null) {
            throw ValidationException::withMessages(['marks' => 'This student is not in a class this year, so marks cannot be added.']);
        }

        $exams = $this->classExams($student, $sectionId)
            ->filter(fn (Exam $e) => (int) $e->exam_type_id === $examTypeId && (int) $e->academic_term_id === $termId)
            ->keyBy('id');
        if ($exams->isEmpty()) {
            throw ValidationException::withMessages(['exam' => 'That exam is not set for this student\'s class.']);
        }

        $clean = [];
        $errors = [];
        foreach ($marks as $examId => $raw) {
            $raw = is_string($raw) ? trim($raw) : $raw;
            if ($raw === null || $raw === '') {
                continue;
            }
            $exam = $exams->get((int) $examId);
            if (! $exam) {
                $errors['marks'] = 'One of the subjects is not part of this exam.';

                continue;
            }
            $subject = $this->sentenceCase((string) ($exam->subject?->name ?: 'Subject'));
            if (! is_numeric($raw)) {
                $errors['marks.'.$examId] = $subject.': enter a number from 0 to 100.';

                continue;
            }
            $value = round((float) $raw, 2);
            if ($value < 0 || $value > self::MAX_MARK) {
                $errors['marks.'.$examId] = $subject.': marks are out of '.(int) self::MAX_MARK.'.';

                continue;
            }
            $clean[(int) $examId] = $value;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
        if ($clean === []) {
            throw ValidationException::withMessages(['marks' => 'Enter at least one mark.']);
        }

        $saved = 0;
        $skipped = [];

        DB::transaction(function () use ($actor, $student, $exams, $clean, &$saved, &$skipped) {
            foreach ($clean as $examId => $value) {
                $exam = $exams->get($examId);
                $subject = $this->sentenceCase((string) ($exam->subject?->name ?: 'Subject'));

                if ($this->isLocked($exam) || $this->writer->requiresCorrection($exam)) {
                    $skipped[] = ['subject' => $subject, 'reason' => 'Already submitted. Return it from Approvals to change it.'];

                    continue;
                }
                if ($this->isNursery($exam)) {
                    $skipped[] = ['subject' => $subject, 'reason' => 'This class is assessed with ratings, not marks.'];

                    continue;
                }

                $this->writer->save($actor, $exam, (int) $student->id, $value);
                $saved++;
            }

            if ($saved > 0) {
                activity()
                    ->performedOn($student)
                    ->causedBy($actor)
                    ->withProperties([
                        'school_id' => (int) $student->school_id,
                        'student_id' => (int) $student->id,
                        'saved' => $saved,
                        'skipped' => count($skipped),
                        'source' => 'student_profile',
                    ])
                    ->useLog('marks')
                    ->log('marks.draft_added');
            }
        });

        return ['saved' => $saved, 'skipped' => $skipped];
    }

    private function sectionId(User $student): ?int
    {
        $id = $this->enrollment->current($student)?->standardLink?->section_id;

        return $id ? (int) $id : null;
    }

    /** @return \Illuminate\Support\Collection<int, Exam> */
    private function classExams(User $student, int $sectionId)
    {
        $year = $this->enrollment->year((int) $student->school_id);
        if (! $year) {
            return collect();
        }

        return Exam::query()
            ->with(['subject', 'teacher', 'examType', 'academicTerm', 'standard'])
            ->where('school_id', $student->school_id)
            ->where('section_id', $sectionId)
            ->where('academic_year_id', $year->id)
            ->whereNull('deleted_at')
            ->get();
    }

    private function isLocked(Exam $exam): bool
    {
        $submission = ExamMarksSubmission::query()
            ->where('exam_id', $exam->id)
            ->where('class_id', $exam->section_id)
            ->where('subject_id', $exam->subject_id)
            ->first();

        return (bool) $submission?->isLocked();
    }

    private function isNursery(Exam $exam): bool
    {
        return $exam->standard && GradingHelper::levelTypeForStandard($exam->standard) === 'nursery';
    }

    private function sittingLabel(Exam $exam): string
    {
        $type = $this->sentenceCase((string) ($exam->examType?->name ?: 'Exam'));
        $term = trim((string) $exam->academicTerm?->name);

        return $term === '' ? $type : $type.' · '.$term;
    }

    private function sentenceCase(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value !== mb_strtoupper($value)) {
            return $value;
        }
        $lower = mb_strtolower($value);

        return mb_strtoupper(mb_substr($lower, 0, 1)).mb_substr($lower, 1);
    }

    private function markText(float $mark): string
    {
        return fmod($mark, 1.0) === 0.0 ? (string) (int) $mark : rtrim(rtrim(number_format($mark, 2, '.', ''), '0'), '.');
    }
}
