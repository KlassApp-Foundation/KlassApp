<?php

namespace Tests\Feature\Marks;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\ReportDivisionRange;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The marks overview aggregate is the four core subjects only.
 * A missing core counts as zero, which is the lowest grade (9 points).
 * Division comes from the school's saved ranges, through the existing rule.
 */
class CoreAggregateOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_aggregate_uses_only_the_four_cores_and_the_schools_division(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Core Aggregate School',
            'slug' => 'core-aggregate-school',
            'email' => 'core-aggregate@school.test',
            'phone' => '0700000111',
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_mode' => 'preview',
            'toshi_enabled' => 0,
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-02-02',
            'end_date' => '2026-12-04',
            'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term III',
            'starts_on' => '2026-09-07',
            'ends_on' => '2026-12-04',
            'status' => 'current',
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.4',
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'stream' => 'East',
            'status' => 1,
        ]);
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Core Admin',
            'email' => 'admin@core-aggregate.test',
            'status' => 'active',
        ]);
        $learner = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Lina Core',
            'email' => 'lina.core@core-aggregate.test',
            'status' => 'active',
            'registration_number' => 'KLS0042001',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $learner->id,
            'standardLink_id' => $link->id,
        ]);

        foreach ([
            ['D1', 1, 95, 100],
            ['D2', 2, 90, 94],
            ['C3', 3, 75, 89],
            ['C4', 4, 65, 74],
            ['C5', 5, 60, 64],
            ['C6', 6, 50, 59],
            ['P7', 7, 45, 49],
            ['P8', 8, 40, 44],
            ['F9', 9, 0, 39],
        ] as [$grade, $points, $min, $max]) {
            SchoolGradingSystem::create([
                'school_id' => $school->id,
                'standard_id' => $standard->id,
                'grade' => $grade,
                'points' => $points,
                'min_score' => $min,
                'max_score' => $max,
                'remark' => $grade,
            ]);
        }
        ReportDivisionRange::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'label' => 'Q',
            'min_aggregate' => 18,
            'max_aggregate' => 18,
            'position' => 0,
        ]);

        $subjects = [];
        foreach ([
            'English' => 'ENG',
            'Mathematics' => 'MTH',
            'Science' => 'SCI',
            'Social Studies' => 'SST',
            'Literacy' => 'LIT',
        ] as $name => $code) {
            $subjects[$name] = Subject::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'name' => $name,
                'code' => $code,
                'type' => 'core',
                'status' => 1,
            ]);
        }
        $type = ExamType::create([
            'name' => 'Opening Paper',
            'code' => 'OPN',
            'contributes_to_report_total' => 0,
        ]);
        $exams = [];
        foreach ($subjects as $name => $subject) {
            $exams[$name] = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'subject_id' => $subject->id,
                'teacher_id' => $admin->id,
                'exam_type_id' => $type->id,
                'academic_term_id' => $term->id,
                'academic_year_id' => $year->id,
                'scheduled_at' => '2026-02-10 09:00:00',
                'status' => 'undone',
            ]));
            ExamMarksSubmission::create([
                'school_id' => $school->id,
                'exam_id' => $exams[$name]->id,
                'class_id' => $section->id,
                'subject_id' => $subject->id,
                'teacher_id' => $admin->id,
                'status' => 'submitted',
                'approval_status' => 'approved',
            ]);
        }
        foreach ([
            'English' => 90,
            'Science' => 81,
            'Social Studies' => 70,
            'Literacy' => 66,
        ] as $name => $score) {
            Marks::create([
                'student_id' => $learner->id,
                'exam_id' => $exams[$name]->id,
                'school_id' => $school->id,
                'subject_id' => $subjects[$name]->id,
                'teacher_id' => $admin->id,
                'section_id' => $section->id,
                'marks' => $score,
                'grade' => '',
            ]);
        }

        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: (int) $school->id,
            sectionId: (int) $section->id,
            academicTermId: (int) $term->id,
            examTypeId: (int) $type->id,
            countApprovedSubjects: true,
        ));
        $row = $sheet->student((int) $learner->id);
        $this->assertNotNull($row);
        $this->assertSame(18, $row->aggregate);
        $this->assertSame('Q', $row->division);
        $this->assertSame(307.0, $row->total);
        $this->assertSame(66.0, $row->cell((int) $subjects['Literacy']->id)->mark);

        $this->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class);
        $html = $this->actingAs($admin)->get(route('admin.marks.filter', [
            'year' => $year->id,
            'term' => $term->id,
            'class' => $section->id,
            'examType' => $type->id,
        ]))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<th[^>]*>\s*Aggregate\s*<\/th>/', $html);
        $this->assertMatchesRegularExpression('/<th[^>]*>\s*Division\s*<\/th>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<table[^>]*data-testid="ds-grid-marks"[\s\S]*?<th[^>]*>\s*Grade\s*<\/th>/', $html);
        $this->assertMatchesRegularExpression('/data-testid="marks-aggregate"[^>]*>\s*18\s*</', $html);
        $this->assertMatchesRegularExpression('/data-testid="marks-division"[^>]*>\s*Q\s*</', $html);
        $this->assertMatchesRegularExpression('/data-testid="marks-phone-aggregate"[^>]*>\s*18\s*</', $html);
        $this->assertMatchesRegularExpression('/data-testid="marks-phone-division"[^>]*>\s*Q\s*</', $html);
        $literacyId = (int) $subjects['Literacy']->id;
        $mathsId = (int) $subjects['Mathematics']->id;
        $this->assertSame('LITERACY', $subjects['Literacy']->name);
        $this->assertStringContainsString($subjects['Literacy']->name, $html);
        $this->assertMatchesRegularExpression('/data-subject="'.$literacyId.'"[^>]*>\s*66\b/', $html);
        $this->assertMatchesRegularExpression('/data-subject="'.$mathsId.'"[^>]*>\s*<span data-testid="marks-cell-missing">–<\/span>/', $html);
    }
}
