<?php

namespace Database\Seeders\result;

use App\Database\ReferenceUpsert;
use App\Models\AcademicYear;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Depends on subjects, a teacher in the same school, exam types,
 * and an academic term for the school. Does not read the signed-in user.
 */
class ExamTableSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::query()->where('status', 1)->get();
        if ($schools->isEmpty()) {
            $this->command?->warn('No active schools. Skipping exams.');

            return;
        }

        $types = ExamType::query()->orderBy('id')->get();
        if ($types->isEmpty()) {
            $this->command?->warn('No exam types. Seed ExamTypes before exams.');

            return;
        }

        foreach ($schools as $school) {
            $year = AcademicYear::query()
                ->where('school_id', $school->id)
                ->where('status', 1)
                ->first();
            $termId = DB::table('academic_terms')
                ->where('school_id', $school->id)
                ->where('status', 'current')
                ->value('id');
            $teacherId = User::query()
                ->where('school_id', $school->id)
                ->where('usergroup_id', 5)
                ->value('id');

            if ($year === null || $termId === null || $teacherId === null) {
                $this->command?->warn("Skipping exams for school {$school->id}: need a year, a current term, and a teacher.");

                continue;
            }

            $subjects = Subject::query()->where('school_id', $school->id)->get();
            foreach ($subjects as $subject) {
                foreach ($types as $type) {
                    ReferenceUpsert::sync('exams', [
                        'school_id' => $school->id,
                        'subject_id' => $subject->id,
                        'exam_type_id' => $type->id,
                        'academic_year_id' => $year->id,
                        'academic_term_id' => $termId,
                    ], [
                        'standard_id' => $subject->standard_id,
                        'section_id' => $subject->section_id,
                        'teacher_id' => $teacherId,
                        'scheduled_at' => '2026-03-01 09:00:00',
                        'status' => 'undone',
                    ]);
                }
            }
        }

        $this->command?->info('Exams seeded.');
    }
}
