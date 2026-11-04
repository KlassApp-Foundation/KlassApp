<?php

namespace App\Services\Academics;

use App\Models\Academics\Exam;
use App\Models\AcademicTerm;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * One action turns a term into an exam set: for every class (or stream) and
 * every subject in scope, one exam per chosen exam type. Rows that already
 * exist are skipped, so the action is safe to re-run.
 */
class ExamSetService
{
    /**
     * @param  list<int>  $typeIds
     * @param  list<int>|null  $sectionIds  null = the whole school
     * @return array{created:int, skipped:int, sections:int, subjects:int}
     */
    public function create(School $school, AcademicTerm $term, array $typeIds, ?array $sectionIds, User $actor): array
    {
        $typeIds = array_values(array_unique(array_map('intval', $typeIds)));
        $subjects = $this->subjectsInScope($school, $term, $sectionIds);

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($school, $term, $typeIds, $subjects, $actor, &$created, &$skipped) {
            foreach ($subjects as $subject) {
                $teacherId = $this->teacherFor($school, $term, $subject) ?? (int) $actor->id;

                foreach ($typeIds as $typeId) {
                    $exists = Exam::query()
                        ->where('school_id', $school->id)
                        ->where('academic_year_id', $term->academic_year_id)
                        ->where('academic_term_id', $term->id)
                        ->where('subject_id', $subject->id)
                        ->where('exam_type_id', $typeId)
                        ->exists();

                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    Exam::create([
                        'school_id' => $school->id,
                        'standard_id' => $subject->standard_id,
                        'section_id' => $subject->section_id,
                        'academic_year_id' => $term->academic_year_id,
                        'academic_term_id' => $term->id,
                        'subject_id' => $subject->id,
                        'teacher_id' => $teacherId,
                        'exam_type_id' => $typeId,
                        'status' => 'undone',
                    ]);

                    $created++;
                }
            }
        });

        return [
            'created' => $created,
            'skipped' => $skipped,
            'sections' => $subjects->pluck('section_id')->unique()->count(),
            'subjects' => $subjects->count(),
        ];
    }

    /**
     * @param  list<int>|null  $sectionIds
     * @return \Illuminate\Support\Collection<int, Subject>
     */
    public function subjectsInScope(School $school, AcademicTerm $term, ?array $sectionIds)
    {
        return Subject::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $term->academic_year_id)
            ->where('status', 1)
            ->when($sectionIds !== null, fn ($q) => $q->whereIn('section_id', $sectionIds))
            ->orderBy('section_id')
            ->orderBy('id')
            ->get();
    }

    /** The subject's teacher from the teacher links, if one is set. */
    private function teacherFor(School $school, AcademicTerm $term, Subject $subject): ?int
    {
        return Teacherlink::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $term->academic_year_id)
            ->where('subject_id', $subject->id)
            ->value('teacher_id');
    }
}
