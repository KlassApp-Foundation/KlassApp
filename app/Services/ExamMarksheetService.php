<?php

namespace App\Services;

use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Builds single-exam marksheet rows from enrolled class students and the
 * exam's subject — not only from marks already saved (which produced empty
 * sheets right after exam create).
 */
class ExamMarksheetService
{
    /**
     * @return array{headings: list<string>, rows: list<list<string|float>>, title: string}
     */
    public function build(Exam $exam, int $schoolId): array
    {
        if ((int) $exam->school_id !== $schoolId) {
            throw new HttpException(403, 'You are not authorized to download this marksheet.');
        }

        $exam->loadMissing(['section', 'examType', 'subject']);

        $subjects = $this->subjectsForExam($exam);
        $students = $this->enrolledStudents($exam, $schoolId);

        $marksLookup = Marks::query()
            ->where('exam_id', $exam->id)
            ->when($students->isNotEmpty(), fn ($q) => $q->whereIn('student_id', $students->pluck('id')))
            ->when($subjects->isNotEmpty(), fn ($q) => $q->whereIn('subject_id', $subjects->pluck('id')))
            ->get()
            ->keyBy(fn (Marks $m) => $m->student_id.'-'.$m->subject_id);

        $headings = array_merge(['STUDENT NAME'], $subjects->pluck('name')->all());
        $rows = [];

        foreach ($students as $student) {
            $row = [$student->name];
            foreach ($subjects as $subject) {
                $mark = $marksLookup->get($student->id.'-'.$subject->id);
                $row[] = ($mark !== null && $mark->marks !== null) ? (float) $mark->marks : '';
            }
            $rows[] = $row;
        }

        $title = str_replace(' ', '_', $exam->section?->name ?? 'class')
            .'_'.($exam->examType?->code ?? 'exam');

        return [
            'headings' => $headings,
            'rows' => $rows,
            'title' => $title,
        ];
    }

    /**
     * @return Collection<int, object{id: int, name: string}>
     */
    private function subjectsForExam(Exam $exam): Collection
    {
        $byId = collect();

        if ($exam->subject_id) {
            $subject = $exam->subject ?? Subject::query()->find($exam->subject_id);
            if ($subject) {
                $byId->put((int) $subject->id, (object) [
                    'id' => (int) $subject->id,
                    'name' => (string) $subject->name,
                ]);
            }
        }

        $fromMarksIds = Marks::query()
            ->where('exam_id', $exam->id)
            ->distinct()
            ->pluck('subject_id')
            ->filter()
            ->all();

        if ($fromMarksIds !== []) {
            foreach (Subject::query()->whereIn('id', $fromMarksIds)->get() as $subject) {
                $byId->put((int) $subject->id, (object) [
                    'id' => (int) $subject->id,
                    'name' => (string) $subject->name,
                ]);
            }
        }

        return $byId->sortBy(fn ($s) => mb_strtolower($s->name))->values();
    }

    /**
     * Active students enrolled in the exam's class (standard + section via standards_link).
     *
     * @return Collection<int, User>
     */
    private function enrolledStudents(Exam $exam, int $schoolId): Collection
    {
        return User::query()
            ->where('usergroup_id', 6)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereHas('studentAcademic', function ($q) use ($exam) {
                $q->whereHas('standardLink', function ($q2) use ($exam) {
                    $q2->where('standard_id', $exam->standard_id)
                        ->where('section_id', $exam->section_id);
                });
            })
            ->orderBy('name')
            ->get();
    }
}
