<?php

namespace Tests\Support;

use App\Models\Academics\Exam;
use App\Models\MarksRelease;
use Illuminate\Support\Facades\DB;

/**
 * A released sitting for tests that already expect stored marks to be visible.
 */
final class ReleasedExam
{
    public static function release(Exam $exam, string $scope = 'school', ?int $standardLinkId = null): void
    {
        MarksRelease::create([
            'school_id' => $exam->school_id,
            'exam_id' => $exam->id,
            'scope' => $scope,
            'standard_link_id' => $scope === 'class' ? $standardLinkId : null,
            'scheduled_at' => now()->subHour(),
            'released_at' => now(),
            'status' => MarksRelease::STATUS_RELEASED,
            'timezone' => 'UTC',
        ]);
    }

    public static function releaseId(int $schoolId, int $examId, string $scope = 'school', ?int $standardLinkId = null): void
    {
        MarksRelease::create([
            'school_id' => $schoolId,
            'exam_id' => $examId,
            'scope' => $scope,
            'standard_link_id' => $scope === 'class' ? $standardLinkId : null,
            'scheduled_at' => now()->subHour(),
            'released_at' => now(),
            'status' => MarksRelease::STATUS_RELEASED,
            'timezone' => 'UTC',
        ]);
    }

    /**
     * An exam row plus a school-wide release, for fixtures that previously
     * pointed marks at a hard-coded exam id with no exam row.
     */
    public static function schoolExam(
        int $schoolId,
        int $academicYearId,
        int $standardId,
        int $sectionId,
        int $subjectId,
        int $teacherId,
    ): int {
        $termId = DB::table('academic_terms')->insertGetId([
            'school_id' => $schoolId,
            'academic_year_id' => $academicYearId,
            'name' => 'Released '.uniqid(),
            'status' => 'current',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addMonth(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $typeId = DB::table('exam_types')->insertGetId([
            'name' => 'Released',
            'code' => 'REL-'.uniqid(),
            'contributes_to_report_total' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $examId = DB::table('exams')->insertGetId([
            'school_id' => $schoolId,
            'academic_year_id' => $academicYearId,
            'academic_term_id' => $termId,
            'standard_id' => $standardId,
            'section_id' => $sectionId,
            'subject_id' => $subjectId,
            'teacher_id' => $teacherId,
            'exam_type_id' => $typeId,
            'status' => 'done',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        self::releaseId($schoolId, $examId);

        return $examId;
    }
}
