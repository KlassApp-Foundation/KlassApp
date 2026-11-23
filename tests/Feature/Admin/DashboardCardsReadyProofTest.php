<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\DashboardV2DataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\ReleasedExam;
use Tests\TestCase;

/**
 * "Report cards ready" counts the learners whose class can hand out a term
 * report. A class whose term is still blocked by an unreleased counting set
 * cannot: its cards print a withheld set, so its learners are not counted yet.
 *
 * Probe, before the fix: three learners were counted as ready while the Mid
 * Term set had marks and no release, because releasedLearnerCount() only asked
 * whether one counting set had been released.
 */
class DashboardCardsReadyProofTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Standard $standard;

    private Section $section;

    private StandardLink $link;

    private User $teacher;

    private User $admin;

    /** @var list<User> */
    private array $learners = [];

    /** @var array<string, list<Exam>> */
    private array $exams = ['eot' => [], 'mid' => []];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        // Both sets count toward the term total, so both are part of its report.
        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Proof School', 'slug' => 'proof-'.uniqid(), 'email' => uniqid().'@proof.test',
            'phone' => '0772'.random_int(100000, 999999), 'status' => 1, 'registration_country' => 'Uganda',
            'toshi_mode' => 'preview', 'toshi_enabled' => 0, 'report_template' => 'progressive',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'Term I', 'status' => 'current', 'starts_on' => '2026-01-05', 'ends_on' => '2026-12-01',
        ]);

        $this->standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $this->section = Section::create(['school_id' => $this->school->id, 'name' => 'Class A', 'status' => 1]);
        $this->link = StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id, 'section_id' => $this->section->id, 'status' => 1,
        ]);

        foreach ([['D1', 1, 80, 100], ['C4', 4, 50, 79], ['F9', 9, 0, 49]] as [$grade, $points, $min, $max]) {
            SchoolGradingSystem::create([
                'school_id' => $this->school->id, 'standard_id' => $this->standard->id,
                'grade' => $grade, 'points' => $points, 'min_score' => $min, 'max_score' => $max, 'remark' => $grade,
            ]);
        }

        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'usergroup_id' => 5, 'status' => 'active']);
        $this->admin = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 3, 'status' => 'active',
            'email' => 'admin@proof.test', 'email_verified' => 1,
        ]);

        $subjects = [];
        foreach (['Maths', 'English'] as $name) {
            $subjects[] = Subject::create([
                'school_id' => $this->school->id, 'standard_id' => $this->standard->id,
                'section_id' => $this->section->id, 'academic_year_id' => $this->year->id,
                'name' => $name, 'type' => 'core', 'status' => 1,
            ]);
        }

        foreach (['Ada Learner', 'Ben Learner', 'Cleo Learner'] as $name) {
            $learner = User::factory()->create([
                'school_id' => $this->school->id, 'usergroup_id' => 6, 'status' => 'active', 'name' => $name,
            ]);
            StudentAcademic::create([
                'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
                'user_id' => $learner->id, 'standardLink_id' => $this->link->id,
            ]);
            $this->learners[] = $learner;
        }

        // End of Term: marked, released. Mid Term: marked, NOT released.
        foreach (['eot' => 1, 'mid' => 2] as $key => $typeId) {
            foreach ($subjects as $subject) {
                $exam = Exam::withoutEvents(fn () => Exam::create([
                    'school_id' => $this->school->id, 'standard_id' => $this->standard->id,
                    'section_id' => $this->section->id, 'subject_id' => $subject->id,
                    'teacher_id' => $this->teacher->id, 'exam_type_id' => $typeId,
                    'academic_term_id' => $this->term->id, 'academic_year_id' => $this->year->id,
                    'scheduled_at' => '2026-03-10', 'status' => 'submitted',
                ]));
                $this->exams[$key][] = $exam;

                foreach ($this->learners as $index => $learner) {
                    Marks::create([
                        'student_id' => $learner->id, 'exam_id' => $exam->id, 'school_id' => $this->school->id,
                        'subject_id' => $subject->id, 'teacher_id' => $this->teacher->id,
                        'section_id' => $this->section->id, 'marks' => 40 + ($index * 10), 'grade' => 'stored',
                    ]);
                }
            }
        }

        foreach ($this->exams['eot'] as $exam) {
            ReleasedExam::release($exam);
        }
    }

    public function test_a_class_blocked_by_an_unreleased_counting_set_is_not_counted_as_ready(): void
    {
        $desk = app(DashboardV2DataService::class)->marksDesk($this->school->id);

        $this->assertSame(3, $desk['cards_total']);
        $this->assertSame(0, $desk['cards_released'], 'The Mid Term set has marks and no release, so the term report is withheld.');

        $html = $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/data-testid="admin-marks-cards-count">0 <small>of 3<\/small>/', $html);
    }

    public function test_the_class_counts_once_the_blocking_set_is_released(): void
    {
        foreach ($this->exams['mid'] as $exam) {
            ReleasedExam::release($exam);
        }

        $desk = app(DashboardV2DataService::class)->marksDesk($this->school->id);

        $this->assertSame(3, $desk['cards_released']);
        $this->assertSame(3, $desk['cards_total']);
    }
}
