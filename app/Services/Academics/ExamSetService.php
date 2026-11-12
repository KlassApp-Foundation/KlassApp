<?php

namespace App\Services\Academics;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamSet;
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
     * @param  list<int>|null  $standardIds  classes; null = the whole school
     * @return array{created:int, skipped:int, sections:int, subjects:int, set_id:int}
     */
    public function create(School $school, AcademicTerm $term, array $typeIds, ?array $standardIds, User $actor): array
    {
        app(ExamTypeCatalogSync::class)->syncShared();

        $typeIds = array_values(array_unique(array_map('intval', $typeIds)));
        // null = the whole school: base classes only, never their streams.
        $sectionIds = $standardIds === null ? $this->baseSectionIds($school) : $standardIds;
        $subjects = $this->subjectsInScope($school, $term, $sectionIds);

        $created = 0;
        $skipped = 0;
        $setId = 0;

        DB::transaction(function () use ($school, $term, $typeIds, $subjects, $actor, &$created, &$skipped, &$setId) {
            $set = ExamSet::create([
                'school_id' => $school->id,
                'academic_year_id' => $term->academic_year_id,
                'academic_term_id' => $term->id,
                'created_by' => $actor->id,
            ]);
            $setId = (int) $set->id;

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
                        'exam_set_id' => $set->id,
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
            'set_id' => $setId,
        ];
    }

    /**
     * The school's classes: sections that are not another section's stream
     * (Riverside: "Primary One" is a class, "Primary One E" is its stream).
     * Exams default to these; a stream only gets its own exam when picked.
     *
     * @return \Illuminate\Support\Collection<int, Section>
     */
    public function baseSections(School $school)
    {
        $sections = \App\Models\Section::query()
            ->where('school_id', $school->id)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return $sections->reject(function ($section) use ($sections) {
            foreach ($sections as $other) {
                if ($other->id !== $section->id && str_starts_with($section->name, $other->name.' ')) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    /** @return list<int> */
    public function baseSectionIds(School $school): array
    {
        return $this->baseSections($school)->pluck('id')->map(fn ($id) => (int) $id)->all();
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
