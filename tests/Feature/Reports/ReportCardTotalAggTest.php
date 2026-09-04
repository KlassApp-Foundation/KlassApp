<?php

namespace Tests\Feature\Reports;

use App\Helpers\GradingHelper;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentReportHelperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportCardTotalAggTest extends TestCase
{
    use RefreshDatabase;

    public function test_effective_points_falls_back_to_numeric_grade_when_points_null(): void
    {
        $row = (object) ['points' => null, 'grade' => '3'];
        $this->assertSame(3, GradingHelper::effectivePoints($row));
        $this->assertSame('C3', GradingHelper::formatAggLabel($row));
    }

    public function test_grade_helper_sums_agg_when_points_null_but_grade_is_numeric(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('exam_types')->insert([
            [
                'id' => 2,
                'name' => 'End Of Term',
                'code' => 'EOT',
                'contributes_to_report_total' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $school = School::create([
            'name' => 'Agg Total School',
            'email' => 'agg@test.sch.ug',
            'phone' => '0700000001',
            'slug' => 'agg-total-school',
            'status' => 1,
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => '1',
            'grading_style' => 'aggregate',
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.7',
            'status' => 1,
        ]);
        StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => '1',
        ]);

        // Historic onboarding shape: grade labels 1–9, points null.
        foreach ([
            [95, 100, '1'], [85, 94, '2'], [75, 84, '3'], [65, 74, '4'],
            [60, 64, '5'], [50, 59, '6'], [45, 49, '7'], [40, 44, '8'], [0, 39, '9'],
        ] as [$min, $max, $grade]) {
            SchoolGradingSystem::create([
                'school_id' => $school->id,
                'standard_id' => $standard->id,
                'grade' => $grade,
                'points' => null,
                'min_score' => $min,
                'max_score' => $max,
                'remark' => 'band '.$grade,
            ]);
        }

        $teacher = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => 'Agg Teacher',
            'email' => 'agg-teacher@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $student = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Agg Student',
            'email' => 'agg-student@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $examType = ExamType::find(2);
        $anchorExam = null;

        foreach ([98, 88, 78, 68] as $i => $score) {
            $subject = Subject::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'name' => 'Subj'.$i,
                'code' => 'S'.$i,
                'type' => 'core',
                'status' => 1,
            ]);
            $exam = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'academic_term_id' => $term->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'exam_type_id' => $examType->id,
                'scheduled_at' => now(),
                'status' => 'done',
            ]));
            $anchorExam ??= $exam;

            Marks::create([
                'school_id' => $school->id,
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'section_id' => $section->id,
                'marks' => $score,
                'grade' => (string) ($i + 1),
            ]);
        }

        $learner = app(StudentReportHelperService::class)->learner($school->id, $student, $anchorExam);
        $this->assertNotNull($learner);

        $grade = app(StudentReportHelperService::class)->grade($learner, $anchorExam);

        // 98→1, 88→2, 78→3, 68→4 = 10
        $this->assertSame(10, $grade['agg']);
        $this->assertSame(4, $grade['counted']);
        $this->assertNotSame(0, $grade['agg']);
    }
}
