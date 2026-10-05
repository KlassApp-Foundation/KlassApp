<?php

namespace Tests\Feature\Onboarding\Steps;

use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Onboarding\Steps\StepRegistry;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Per-step proof of the structured-answer contract for registry steps:
 *
 * (b) validate + save works with pure structured data — no chat, no Livewire,
 *     no session;
 * (d) the same answer may be submitted twice: rows already present are skipped
 *     with a report (not duplicated, not re-created, not failing).
 *
 * One short test per step. preview() must never write.
 */
class StepIdempotenceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private StepRegistry $registry;

    /** @var array<string, string> indexed per-step test answers */
    private const ANSWERS = [
        'school_name' => 'Registry Academy',
        'student_size' => 'Up to 500',
        'country' => 'Uganda',
        'curriculum' => 'UNEB',
        'school_category' => 'primary',
        'emis' => 'EMIS-STEP-001',
        'uneb_center' => '',
        'academic_year' => ['name' => 'Current Academic Year', 'start' => '2026-01-12', 'end' => '2026-12-18'],
        'standards' => [['name' => 'P1', 'streams' => ['A']]],
        'subjects' => ['P1' => ['Mathematics']],
        'teachers' => [['name' => 'Grace Tutor', 'email' => 'grace.tutor@step-test.sch.ug', 'phone' => '0770000111', 'links' => []]],
        'students' => [['name' => 'Sean Learner', 'class' => 'P1', 'stream' => '']],
        'terms' => [['name' => 'Term 1', 'start' => '2026-01-12', 'end' => '2026-04-30']],
        'fees' => [['name' => 'Tuition', 'amount' => 100000]],
        'whatsapp_verify' => '+256770000111',
        'plan_selection' => 'Freemium',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = app(StepRegistry::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => "Idempotence's School",
            'email' => 'step-idempotence@test.sch.ug',
            'phone' => '0700000077',
            'slug' => 'step-idempotence',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 0,
        ]);

        Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Step Admin',
            'email' => 'admin@step-idempotence.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::firstOrCreate(
            ['user_id' => $this->admin->id],
            [
                'school_id' => $this->school->id,
                'usergroup_id' => 3,
                'firstname' => 'Step',
                'lastname' => 'Admin',
                'profession' => 'schooladmin',
                'status' => 'active',
            ]
        );
    }

    private function registry(): StepRegistry
    {
        return app(StepRegistry::class);
    }

    private function admin(): int
    {
        return $this->admin->id;
    }

    /**
     * Save every step up to and including $key with its structured answer
     * (data prerequisites), then return the target step.
     */
    private function prepareThrough(string $key): \App\Onboarding\Steps\Contracts\OnboardingStep
    {
        $userId = $this->admin();

        foreach ($this->registry->all() as $step) {
            $isTarget = $step->key() === $key;
            $step->saveAndReport($this->school->fresh(), self::ANSWERS[$step->key()], $userId);
            $this->school = $this->school->fresh();
            if ($isTarget) {
                return $step;
            }
        }

        $this->fail("Step {$key} not found.");
    }

    /** Run one saveAndReport on the target step and return its report. */
    private function reportOf(string $key, mixed $answer): array
    {
        return $this->registry->byKey($key)->saveAndReport(
            $this->school->fresh(),
            $answer,
            $this->admin()
        );
    }

    /** Global row counts used as the idempotence sentinel. */
    private function counts(): array
    {
        return [
            'users' => DB::table('users')->count(),
            'userprofiles' => DB::table('userprofiles')->count(),
            'academic_years' => DB::table('academic_years')->count(),
            'standards' => DB::table('standards')->count(),
            'sections' => DB::table('sections')->count(),
            'standards_link' => DB::table('standards_link')->count(),
            'subjects' => DB::table('subjects')->count(),
            'class_teacher_links' => DB::table('class_teacher_links')->count(),
            'academic_terms' => DB::table('academic_terms')->count(),
            'fees_categories' => DB::table('fees_categories')->count(),
            'whatsapp_users' => DB::table('whatsapp_users')->count(),
            'current_plans' => DB::table('current_plans')->count(),
        ];
    }

    public function test_school_name(): void
    {
        $step = $this->prepareThrough('school_name');

        $step->saveAndReport($this->school, self::ANSWERS['school_name']);
        $this->assertTrue($step->isComplete($this->school->fresh()));
        $this->assertSame('Registry Academy', $this->school->fresh()->name);

        $counts = $this->counts();
        $second = $this->reportOf('school_name', self::ANSWERS['school_name']);

        $this->assertSame($counts, $this->counts());
        $this->assertSame([], $second['created']);
    }

    public function test_student_size(): void
    {
        $this->prepareThrough('student_size');

        $this->assertTrue($this->registry->byKey('student_size')->isComplete($this->school->fresh()));
        $this->assertSame('Up to 500', $this->school->fresh()->student_size);

        $counts = $this->counts();
        $second = $this->reportOf('student_size', self::ANSWERS['student_size']);

        $this->assertSame($counts, $this->counts());
        $this->assertSame([], $second['created']);
    }

    public function test_country(): void
    {
        $this->prepareThrough('country');

        $school = $this->school->fresh();
        $this->assertTrue(OnboardingStepsService::isUganda($school->registration_country));

        $counts = $this->counts();
        $second = $this->reportOf('country', self::ANSWERS['country']);

        $this->assertSame($counts, $this->counts());
        $this->assertSame([], $second['created']);
    }

    public function test_curriculum(): void
    {
        $this->prepareThrough('curriculum');

        $this->assertSame('uneb', $this->school->fresh()->curriculum);

        $counts = $this->counts();
        $second = $this->reportOf('curriculum', self::ANSWERS['curriculum']);

        $this->assertSame($counts, $this->counts());
        $this->assertSame([], $second['created']);
    }

    public function test_school_category(): void
    {
        $this->prepareThrough('school_category');

        $this->assertSame('primary', $this->school->fresh()->school_category);

        $counts = $this->counts();
        $second = $this->reportOf('school_category', self::ANSWERS['school_category']);

        $this->assertSame($counts, $this->counts());
        $this->assertSame([], $second['created']);
    }

    public function test_emis(): void
    {
        $this->prepareThrough('emis');

        $this->assertSame('EMIS-STEP-001', $this->school->fresh()->ministry_code);

        $counts = $this->counts();
        $second = $this->reportOf('emis', self::ANSWERS['emis']);

        $this->assertSame($counts, $this->counts());
        $this->assertSame([], $second['created']);
    }

    public function test_uneb_center(): void
    {
        $this->prepareThrough('uneb_center');

        $this->assertTrue($this->registry->byKey('uneb_center')->isComplete($this->school->fresh()));

        $counts = $this->counts();
        $second = $this->reportOf('uneb_center', self::ANSWERS['uneb_center']);

        $this->assertSame($counts, $this->counts());
        $this->assertSame([], $second['created']);
    }

    public function test_academic_year(): void
    {
        $this->prepareThrough('academic_year');

        $this->assertTrue($this->registry->byKey('academic_year')->isComplete($this->school->fresh()));

        $counts = $this->counts();
        $second = $this->reportOf('academic_year', self::ANSWERS['academic_year']);

        $this->assertSame($counts, $this->counts());
        $this->assertSame([], $second['created']);
    }

    public function test_standards(): void
    {
        $this->prepareThrough('standards');

        $this->assertTrue($this->registry->byKey('standards')->isComplete($this->school->fresh()));

        $counts = $this->counts();
        $second = $this->reportOf('standards', self::ANSWERS['standards']);

        $this->assertSame($counts, $this->counts(), 're-running the same structure answer must not duplicate class rows');
        $this->assertSame([], $second['created']);
    }

    public function test_subjects(): void
    {
        $this->prepareThrough('subjects');

        $this->assertTrue($this->registry->byKey('subjects')->isComplete($this->school->fresh()));
        $this->assertGreaterThanOrEqual(
            1,
            Subject::where('school_id', $this->school->id)->where('name', 'Mathematics')->count()
        );

        $counts = $this->counts();
        $second = $this->reportOf('subjects', self::ANSWERS['subjects']);

        $this->assertSame($counts, $this->counts(), 're-running the same subject list must not duplicate subject rows');
        $this->assertSame([], $second['created']);
    }

    public function test_teachers(): void
    {
        // The teacher answer carries class-subject links (roster + assignment
        // is what makes the step complete in product semantics), so the ids
        // come from the P1 link seeded by the earlier steps.
        $this->prepareThrough('subjects');

        $link = \App\Models\StandardLink::where('school_id', $this->school->id)
            ->whereHas('section', fn ($q) => $q->where('name', 'P1'))
            ->first();
        $subject = Subject::where('school_id', $this->school->id)
            ->where('name', 'Mathematics')
            ->where('section_id', $link->section_id)
            ->first();
        $this->assertNotNull($link, 'standards step should have created a P1 link');
        $this->assertNotNull($subject, 'subjects step should have created Mathematics for P1');

        $answer = [['name' => 'Grace Tutor', 'email' => 'grace.tutor@step-test.sch.ug', 'phone' => '0770000111', 'links' => [['standardLink_id' => $link->id, 'subject_id' => $subject->id]]]];
        $step = $this->registry->byKey('teachers');

        $report = $step->saveAndReport($this->school->fresh(), $answer, $this->admin());
        $this->assertSame(1, DB::table('users')->where('email', 'grace.tutor@step-test.sch.ug')->count());

        $counts = $this->counts();
        $second = $step->saveAndReport($this->school->fresh(), $answer, $this->admin());

        $this->assertSame($counts, $this->counts(), 're-adding the same teacher must not duplicate the user row');
        $this->assertSame([], $second['created']);
        $this->assertSame(
            [0 => ['label' => 'Grace Tutor <grace.tutor@step-test.sch.ug>', 'reason' => 'already present']],
            $second['skipped']
        );
        $this->assertTrue($step->isComplete($this->school->fresh()));
    }

    public function test_students(): void
    {
        $step = $this->prepareThrough('students');

        $this->reportOf('students', self::ANSWERS['students']);
        $students = DB::table('users')->where('school_id', $this->school->id)
            ->where('usergroup_id', 6)->where('name', 'Sean Learner')->count();
        $this->assertSame(1, $students, 'the student is created exactly once');

        $counts = $this->counts();
        $second = $this->reportOf('students', self::ANSWERS['students']);

        $this->assertSame($counts, $this->counts(), 're-adding the same student must not duplicate the user row');
        $this->assertSame([], $second['created']);
        $this->assertSame(
            [0 => ['label' => 'Sean Learner (P1)', 'reason' => 'already present']],
            $second['skipped']
        );
        $this->assertTrue($step->isComplete($this->school->fresh()));
    }

    public function test_terms(): void
    {
        $this->prepareThrough('terms');

        $this->assertTrue($this->registry->byKey('terms')->isComplete($this->school->fresh()));

        $counts = $this->counts();
        $second = $this->reportOf('terms', self::ANSWERS['terms']);

        $this->assertSame($counts, $this->counts(), 're-saving the same term list must not duplicate term rows');
        $this->assertSame([], $second['created']);
    }

    public function test_fees(): void
    {
        $this->prepareThrough('fees');

        $this->assertTrue($this->registry->byKey('fees')->isComplete($this->school->fresh()));

        $counts = $this->counts();
        $second = $this->reportOf('fees', self::ANSWERS['fees']);

        $this->assertSame($counts, $this->counts(), 're-saving the same fee must not duplicate fee rows');
        $this->assertSame([], $second['created']);
    }

    public function test_whatsapp_verify(): void
    {
        $this->prepareThrough('whatsapp_verify');

        $user = DB::table('whatsapp_users')->where('phone', '+256770000111')->first();
        $this->assertNotNull($user, 'the WhatsApp link is created with the structured phone answer');
        $this->assertSame($this->admin(), $user->user_id);

        $counts = $this->counts();
        $second = $this->reportOf('whatsapp_verify', self::ANSWERS['whatsapp_verify']);

        $this->assertSame($counts, $this->counts());
        $this->assertSame([], $second['created']);
        $this->assertSame(
            [0 => ['label' => '+256770000111', 'reason' => 'already present']],
            $second['skipped']
        );
    }

    public function test_plan_selection(): void
    {
        $this->prepareThrough('plan_selection');

        $this->assertTrue($this->registry->byKey('plan_selection')->isComplete($this->school->fresh()));

        $counts = $this->counts();
        $second = $this->reportOf('plan_selection', self::ANSWERS['plan_selection']);

        $this->assertSame($counts, $this->counts());
        $this->assertSame([], $second['created']);
    }

    /** preview() must describe every step without writing anything. */
    public function test_preview_never_writes_for_every_step(): void
    {
        $userId = $this->admin();
        $steps = $this->registry->all();

        foreach ($steps as $step) {
            $before = $this->counts();
            $preview = $step->preview($this->school->fresh(), self::ANSWERS[$step->key()], $userId);

            $this->assertArrayHasKey('action', $preview, $step->key());
            $this->assertArrayHasKey('summary', $preview, $step->key());
            $this->assertArrayHasKey('rows', $preview, $step->key());
            $this->assertIsString($preview['summary']);
            $this->assertIsArray($preview['rows']);
            $this->assertSame($before, $this->counts(), "{$step->key()} preview wrote something");
        }
    }
}
