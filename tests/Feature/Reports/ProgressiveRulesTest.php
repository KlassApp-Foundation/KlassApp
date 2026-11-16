<?php

namespace Tests\Feature\Reports;

use App\Helpers\GradingHelper;
use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
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
use App\Models\Userprofile;
use App\Services\Reports\ProgressiveRules;
use App\Services\Results\ThresholdDivisionRule;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProgressiveRulesTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Standard $standard;

    private User $admin;

    private User $head;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBePrivilege::class]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'SchoolSubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('exam_types')->insert([
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Rules School', 'slug' => 'rules-'.uniqid(), 'email' => uniqid().'@rules.test',
            'phone' => '073'.random_int(1000000, 9999999), 'status' => 1, 'report_template' => 'formal',
        ]);
        $year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $this->standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        $this->yearId = $year->id;
        $this->admin = $this->user(3, 'Rules Admin');
        $this->head = $this->user(4, 'Rules Head');
        GradingHelper::seedGradingForSchool((int) $this->school->id, 'primary');
    }

    private int $yearId;

    public function test_the_screen_starts_from_todays_ranges_without_storing_them(): void
    {
        $this->actingAs($this->head)
            ->get('/admin/reports/cards/progressive-rules?standard='.$this->standard->id)
            ->assertOk()
            ->assertSee('Progressive report rules')
            ->assertSee('12')
            ->assertSee('By aggregate')
            ->assertSee('data-testid="automatic-division"', false)
            ->assertSee('From aggregate')
            ->assertSee('data-label="From"', false)
            ->assertSee('data-label="Remark"', false)
            ->assertSee('division-ranges-scroll', false);

        $this->assertNull($this->school->fresh()->ranking_basis);
        $this->assertNull($this->standard->fresh()->ranking_basis);
        $this->assertSame(0, ReportDivisionRange::query()->count());
        $this->assertInstanceOf(
            ThresholdDivisionRule::class,
            app(ProgressiveRules::class)->divisionRule((int) $this->school->id, (int) $this->standard->id)
        );
    }

    public function test_a_head_teacher_can_save_a_division_range_and_a_ranking_for_one_class_level(): void
    {
        $this->actingAs($this->head)
            ->post('/admin/reports/cards/progressive-rules', [
                'standard_id' => $this->standard->id,
                'ranking_basis' => 'total',
                'ranges' => [
                    ['label' => '1', 'min' => '', 'max' => '6'],
                    ['label' => '2', 'min' => '7', 'max' => ''],
                ],
            ])
            ->assertRedirect();

        $this->assertSame('total', $this->standard->fresh()->ranking_basis);
        $this->assertNull($this->school->fresh()->ranking_basis);
        $this->assertSame('2', app(ProgressiveRules::class)->divisionRule((int) $this->school->id, (int) $this->standard->id)->division(7, 2));
        $this->assertSame('1', app(ProgressiveRules::class)->divisionRule((int) $this->school->id, (int) $this->standard->id)->division(6, 2));

        [$basis, $higher] = app(ProgressiveRules::class)->ranking((int) $this->school->id, (int) $this->standard->id);
        $this->assertSame('total', $basis);
        $this->assertTrue($higher);
    }

    public function test_a_class_level_without_its_own_choice_uses_the_school_choice(): void
    {
        $other = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary upper', 'order' => 2, 'status' => 1,
        ]);

        $this->actingAs($this->admin)
            ->post('/admin/reports/cards/progressive-rules', [
                'ranking_basis' => 'aggregate',
                'ranges' => [
                    ['label' => 'A', 'min' => '', 'max' => '10'],
                    ['label' => 'B', 'min' => '11', 'max' => ''],
                ],
            ])
            ->assertRedirect();

        $this->assertSame('A', app(ProgressiveRules::class)->divisionRule((int) $this->school->id, (int) $other->id)->division(10, 1));
        [$basis] = app(ProgressiveRules::class)->ranking((int) $this->school->id, (int) $other->id);
        $this->assertSame('aggregate', $basis);
        $this->assertNull($other->fresh()->ranking_basis);
    }

    public function test_changing_a_band_point_changes_the_aggregate_on_the_card(): void
    {
        $bands = SchoolGradingSystem::query()
            ->where('school_id', $this->school->id)
            ->where('standard_id', $this->standard->id)
            ->orderByDesc('min_score')
            ->get();
        $payload = [];
        foreach ($bands as $index => $band) {
            $payload[$index] = [
                'grade' => $band->grade,
                'points' => (string) $band->grade === 'C3' ? 1 : $band->points,
                'min_score' => $band->min_score,
                'max_score' => $band->max_score,
                'remark' => $band->remark,
            ];
        }

        $this->actingAs($this->admin)
            ->post('/admin/reports/cards/progressive-rules', [
                'standard_id' => $this->standard->id,
                'ranking_basis' => 'automatic',
                'use_automatic_division' => '1',
                'bands' => $payload,
            ])
            ->assertRedirect();

        $this->assertSame(1, (int) SchoolGradingSystem::query()
            ->where('school_id', $this->school->id)
            ->where('standard_id', $this->standard->id)
            ->where('grade', 'C3')
            ->value('points'));
        $this->assertNull($this->standard->fresh()->ranking_basis);
    }

    public function test_a_teacher_cannot_open_the_rules_and_another_schools_class_is_refused(): void
    {
        $teacher = $this->user(5, 'Rules Teacher');
        $this->actingAs($teacher)
            ->post('/admin/reports/cards/progressive-rules', ['ranking_basis' => 'total'])
            ->assertRedirect('/teacher/dashboard');

        $other = School::create([
            'name' => 'Other Rules', 'slug' => 'other-rules-'.uniqid(), 'email' => uniqid().'@or.test',
            'phone' => '074'.random_int(1000000, 9999999), 'status' => 1,
        ]);
        $foreign = Standard::create([
            'school_id' => $other->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);

        $this->actingAs($this->admin)
            ->post('/admin/reports/cards/progressive-rules', [
                'standard_id' => $foreign->id,
                'ranking_basis' => 'total',
                'use_automatic_division' => '1',
            ])
            ->assertNotFound();

        $this->assertNull($foreign->fresh()->ranking_basis);
    }

    public function test_a_class_ranked_by_total_puts_the_higher_total_first(): void
    {
        $this->standard->forceFill(['ranking_basis' => 'total'])->save();
        $ctx = $this->classContext();
        $lowerPoints = $this->learner($ctx, 'Lower Points', 'KLS0091001');
        $higherTotal = $this->learner($ctx, 'Higher Total', 'KLS0091002');
        $english = $this->exam($ctx, $ctx['english']);
        $maths = $this->exam($ctx, $ctx['maths']);
        $this->mark($ctx, $english, $ctx['english'], $lowerPoints, 95);
        $this->mark($ctx, $maths, $ctx['maths'], $lowerPoints, 10);
        $this->mark($ctx, $english, $ctx['english'], $higherTotal, 60);
        $this->mark($ctx, $maths, $ctx['maths'], $higherTotal, 60);

        $lower = $this->card($ctx, $lowerPoints);
        $higher = $this->card($ctx, $higherTotal);

        $this->assertSame(2, $lower['myPos']);
        $this->assertSame(1, $higher['myPos']);
    }

    /** @return array<string, mixed> */
    private function classContext(): array
    {
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'P.5', 'status' => 1]);
        $teacher = $this->user(5, 'Class Teacher');
        $link = StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->yearId,
            'standard_id' => $this->standard->id, 'section_id' => $section->id,
            'class_teacher_id' => $teacher->id, 'stream' => 'A', 'status' => 1,
        ]);
        $english = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->yearId, 'standard_id' => $this->standard->id,
            'section_id' => $section->id, 'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);
        $maths = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->yearId, 'standard_id' => $this->standard->id,
            'section_id' => $section->id, 'name' => 'Mathematics', 'code' => 'MTH', 'type' => 'core', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->yearId, 'name' => 'Term I',
            'status' => 'current', 'starts_on' => '2026-02-02', 'ends_on' => '2026-04-30',
        ]);

        return compact('section', 'teacher', 'link', 'english', 'maths', 'term');
    }

    private function learner(array $ctx, string $name, string $kls): User
    {
        $user = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $this->school->id, 'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@rules.test',
            'status' => 'active', 'registration_number' => $kls,
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->yearId,
            'user_id' => $user->id, 'standardLink_id' => $ctx['link']->id,
        ]);

        return $user;
    }

    private function exam(array $ctx, Subject $subject): Exam
    {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id, 'standard_id' => $this->standard->id, 'section_id' => $ctx['section']->id,
            'subject_id' => $subject->id, 'teacher_id' => $ctx['teacher']->id, 'exam_type_id' => 2,
            'academic_term_id' => $ctx['term']->id, 'academic_year_id' => $this->yearId,
            'scheduled_at' => '2026-04-20 09:00:00', 'status' => 'submitted',
        ]));
    }

    private function mark(array $ctx, Exam $exam, Subject $subject, User $student, float $score): void
    {
        Marks::create([
            'student_id' => $student->id, 'exam_id' => $exam->id, 'school_id' => $this->school->id,
            'subject_id' => $subject->id, 'teacher_id' => $ctx['teacher']->id, 'section_id' => $ctx['section']->id,
            'marks' => $score, 'grade' => 'stored',
        ]);
    }

    /** @return array<string, mixed> */
    private function card(array $ctx, User $learner): array
    {
        $captured = null;
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = $data;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF');
        $this->app->instance('dompdf.wrapper', $pdf);
        app(StudentReportCardService::class)->pdfForStudent((int) $this->school->id, $ctx['link'], $learner);

        return $captured ?? [];
    }

    private function user(int $group, string $name): User
    {
        $user = User::create([
            'name' => $name.' '.uniqid(),
            'email' => uniqid().'@rules-user.test',
            'password' => Hash::make('password123'),
            'usergroup_id' => $group,
            'school_id' => $this->school->id,
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $user->id, 'school_id' => $this->school->id, 'usergroup_id' => $group, 'status' => 'active',
        ]);

        return $user;
    }
}
