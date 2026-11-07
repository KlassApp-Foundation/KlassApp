<?php

namespace Tests\Feature\Results;

use App\Helpers\GradingHelper;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\Academics\Marks;
use App\Models\Academics\Exam;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Results\ResultSheet;
use App\Services\Results\ResultsPolicy;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Report composition (which exam types appear on report cards and their
 * weights) and curriculum profiles (new vs old O-level grading bands).
 */
class ReportCompositionAndProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'school admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_weights_merge_the_term_mark_as_a_weighted_mean(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [['grade' => 'C', 'points' => null, 'min' => 50, 'max' => 100]]);
        $ctx['school']->forceFill([
            'exam_type_preferences' => ['1' => '1', '2' => '1'],
            'report_composition' => ['1' => ['include' => true, 'weight' => 30.0], '2' => ['include' => true, 'weight' => 70.0]],
        ])->save();

        $student = $this->student($ctx, 'Weighted Student');
        $mid = $this->exam($ctx, $term, $ctx['maths'], '2026-03-01', 1);
        $eot = $this->exam($ctx, $term, $ctx['maths'], '2026-04-20', 2);
        $this->mark($ctx, $mid, $ctx['maths'], $student, 60, '');
        $this->mark($ctx, $eot, $ctx['maths'], $student, 80, '');

        $row = $this->sheet($ctx, $term)->student($student->id);
        $this->assertNotNull($row);
        $this->assertSame(74.0, $row->cell($ctx['maths']->id)?->mark);
    }

    public function test_without_weights_the_latest_mark_still_wins(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [['grade' => 'C', 'points' => null, 'min' => 50, 'max' => 100]]);
        $ctx['school']->forceFill(['exam_type_preferences' => ['1' => '1', '2' => '1']])->save();

        $student = $this->student($ctx, 'Plain Student');
        $mid = $this->exam($ctx, $term, $ctx['maths'], '2026-03-01', 1);
        $eot = $this->exam($ctx, $term, $ctx['maths'], '2026-04-20', 2);
        $this->mark($ctx, $mid, $ctx['maths'], $student, 60, '');
        $this->mark($ctx, $eot, $ctx['maths'], $student, 80, '');

        $row = $this->sheet($ctx, $term)->student($student->id);
        $this->assertNotNull($row);
        $this->assertSame(80.0, $row->cell($ctx['maths']->id)?->mark);
    }

    public function test_a_hidden_type_is_dropped_from_the_term_sheet_sittings(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [['grade' => 'C', 'points' => null, 'min' => 50, 'max' => 100]]);
        $ctx['school']->forceFill([
            'report_composition' => ['1' => ['include' => false, 'weight' => null]],
        ])->save();

        $student = $this->student($ctx, 'Hidden Type Student');
        $mid = $this->exam($ctx, $term, $ctx['maths'], '2026-03-01', 1);
        $eot = $this->exam($ctx, $term, $ctx['maths'], '2026-04-20', 2);
        $this->mark($ctx, $mid, $ctx['maths'], $student, 60, '');
        $this->mark($ctx, $eot, $ctx['maths'], $student, 80, '');

        $sheet = $this->sheet($ctx, $term);
        $this->assertSame([2], array_map(fn ($sitting) => $sitting->examTypeId, $sheet->sittings));
    }

    public function test_a_visible_type_stays_on_the_term_sheet(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [['grade' => 'C', 'points' => null, 'min' => 50, 'max' => 100]]);

        $student = $this->student($ctx, 'Visible Type Student');
        $mid = $this->exam($ctx, $term, $ctx['maths'], '2026-03-01', 1);
        $eot = $this->exam($ctx, $term, $ctx['maths'], '2026-04-20', 2);
        $this->mark($ctx, $mid, $ctx['maths'], $student, 60, '');
        $this->mark($ctx, $eot, $ctx['maths'], $student, 80, '');

        $sheet = $this->sheet($ctx, $term);
        $this->assertSame([1, 2], array_map(fn ($sitting) => $sitting->examTypeId, $sheet->sittings));
    }

    public function test_the_default_profile_seeds_nlsc_o_level_bands(): void
    {
        $school = School::create([
            'name' => 'NLSC School',
            'email' => 'nlsc-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'nlsc-'.uniqid(),
            'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'Senior One',
            'order' => 1,
            'status' => 1,
        ]);

        GradingHelper::seedGradingForSchool($school->id, 'o-level');

        $row = SchoolGradingSystem::query()
            ->where('school_id', $school->id)
            ->where('standard_id', $standard->id)
            ->where('grade', 'C')
            ->first();
        $this->assertNotNull($row);
        $this->assertSame(60, $row->min_score);
        $this->assertSame(69, $row->max_score);
        $this->assertSame('C', app(\App\Services\Results\DisplayedGrade::class)->subject($school->id, $standard->id, 65.0, ''));
    }

    public function test_the_legacy_profile_reseeds_the_o_level_bands(): void
    {
        $school = School::create([
            'name' => 'Legacy School',
            'email' => 'legacy-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'legacy-'.uniqid(),
            'status' => 1,
            'curriculum_profile' => 'legacy',
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'Senior One',
            'order' => 1,
            'status' => 1,
        ]);

        GradingHelper::seedGradingForSchool($school->id, 'o-level');

        $row = SchoolGradingSystem::query()
            ->where('school_id', $school->id)
            ->where('standard_id', $standard->id)
            ->where('grade', 'C3')
            ->first();
        $this->assertNotNull($row);
        $this->assertSame(65, $row->min_score);
        $this->assertSame('C3', app(\App\Services\Results\DisplayedGrade::class)->subject($school->id, $standard->id, 65.0, ''));
    }

    public function test_the_class_overview_shows_the_school_scale_not_the_stored_letter(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [['grade' => 'C', 'points' => null, 'min' => 50, 'max' => 100]]);
        $student = $this->student($ctx, 'Overview Student');

        // The stored letter here is junk; the overview must grade through
        // the school's own scale, never print the raw stored string.
        $eotMaths = $this->exam($ctx, $term, $ctx['maths'], '2026-04-20', 2);
        $this->mark($ctx, $eotMaths, $ctx['maths'], $student, 65, 'ZZ');

        $admin = User::create([
            'school_id' => $ctx['school']->id,
            'usergroup_id' => 3,
            'name' => 'Admin',
            'email' => 'admin.'.uniqid().'@t.sch.ug',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $ctx['school']->id,
            'usergroup_id' => 3,
            'firstname' => 'School',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/admin/marks/classes/'.$ctx['section']->id)
            ->assertOk()
            ->assertSee('65')
            ->assertSee('>C<', false)
            ->assertDontSee('ZZ');
    }

    private function school(string $name = 'Composition School'): array
    {
        $school = School::create([
            'name' => $name,
            'email' => 'comp-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'comp-'.uniqid(),
            'status' => 1,
            'report_template' => 'formal',
            'registration_country' => 'Uganda',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
            'grading_style' => 'aggregate',
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.4',
            'status' => 1,
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
            'email' => 'ct.'.uniqid().'@t.sch.ug',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacher->id,
            'stream' => 'East',
            'status' => 1,
        ]);
        $english = $this->subject(compact('school', 'year', 'standard', 'section'), 'English', 'ENG');
        $maths = $this->subject(compact('school', 'year', 'standard', 'section'), 'Mathematics', 'MATH');

        return compact('school', 'year', 'standard', 'section', 'link', 'teacher', 'english', 'maths');
    }

    private function subject(array $ctx, string $name, string $code): Subject
    {
        return Subject::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id,
            'name' => $name,
            'code' => $code,
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function student(array $ctx, string $name): User
    {
        $user = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $ctx['school']->id,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@t.sch.ug',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'user_id' => $user->id,
            'standardLink_id' => $ctx['link']->id,
        ]);

        return $user;
    }

    private function term(array $ctx): AcademicTerm
    {
        return AcademicTerm::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'name' => 'Term I',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);
    }

    private function exam(array $ctx, AcademicTerm $term, Subject $subject, string $when, int $examTypeId = 2): Exam
    {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'exam_type_id' => $examTypeId,
            'academic_term_id' => $term->id,
            'academic_year_id' => $ctx['year']->id,
            'scheduled_at' => $when,
            'status' => 'submitted',
        ]));
    }

    private function mark(array $ctx, Exam $exam, Subject $subject, User $student, float $score, string $grade): void
    {
        Marks::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'school_id' => $ctx['school']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'section_id' => $exam->section_id,
            'marks' => $score,
            'grade' => $grade,
        ]);
    }

    private function bands(array $ctx, array $rows): void
    {
        foreach ($rows as $row) {
            SchoolGradingSystem::create([
                'school_id' => $ctx['school']->id,
                'standard_id' => $ctx['standard']->id,
                'grade' => $row['grade'],
                'points' => $row['points'],
                'min_score' => $row['min'],
                'max_score' => $row['max'],
                'remark' => $row['grade'],
            ]);
        }
    }

    private function sheet(
        array $ctx,
        AcademicTerm $term,
        ?ResultsPolicy $policy = null,
    ): ResultSheet {
        return app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: $ctx['school']->id,
            sectionId: $ctx['section']->id,
            academicTermId: $term->id,
        ), $policy ?? new ResultsPolicy(
            app(\App\Contracts\Results\GradingScale::class),
            app(\App\Contracts\Results\DivisionRule::class),
        ));
    }
}
