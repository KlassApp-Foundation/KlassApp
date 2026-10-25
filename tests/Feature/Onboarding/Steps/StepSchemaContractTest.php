<?php

namespace Tests\Feature\Onboarding\Steps;

use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Onboarding\Steps\StepRegistry;
use App\Onboarding\Steps\StepSchemaCatalog;
use App\Services\OnboardingEngine;
use App\Services\OnboardingNameListExtractor;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class StepSchemaContractTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Plan $plan;

    private StepRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

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
            'name' => "Schema's School",
            'email' => 'schema-steps@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'schema-steps',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 0,
        ]);

        User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Schema Admin',
            'email' => 'admin@schema-steps.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $this->plan = Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);

        $this->registry = app(StepRegistry::class);
    }

    public function test_every_wizard_step_has_a_schema_in_wizard_order(): void
    {
        $wizardKeys = array_keys(OnboardingStepsService::ALL_STEPS);
        $registryKeys = array_map(fn ($step) => $step->key(), $this->registry->all());
        $schemaKeys = [];

        foreach ($this->registry->all() as $step) {
            $schema = $step->schema($this->school);
            $schemaKeys[] = $schema->key;

            $this->assertSame($step->key(), $schema->key);
            $this->assertNotSame([], $schema->fields);
            $this->assertTrue($schema->confirm);
            $this->assertNotSame('', $schema->actor->school);
            $this->assertNotSame('', $schema->actor->engine);
            $this->assertMatchesRegularExpression('/confirm/i', $schema->actor->school);
            $this->assertMatchesRegularExpression('/save|create|send/i', $schema->actor->engine);
        }

        $this->assertSame($wizardKeys, $registryKeys);
        $this->assertSame($wizardKeys, $schemaKeys);
    }

    public function test_onboarding_schema_excludes_exams_and_schoolpay(): void
    {
        $keys = array_keys(OnboardingStepsService::ALL_STEPS);
        $registryKeys = array_map(fn ($step) => $step->key(), $this->registry->all());

        foreach (StepSchemaCatalog::EXCLUDED_KEYS as $banned) {
            $this->assertNotContains($banned, $keys);
            $this->assertNotContains($banned, $registryKeys);

            try {
                StepSchemaCatalog::for($banned, $this->school, []);
                $this->fail("Expected [{$banned}] to stay out of onboarding.");
            } catch (InvalidArgumentException) {
                // excluded
            }
        }
    }

    public function test_choice_options_match_the_step(): void
    {
        foreach (['student_size', 'curriculum', 'school_category', 'plan_selection'] as $key) {
            $step = $this->registry->byKey($key);
            $schema = $step->schema($this->school);

            $this->assertSame($step->options($this->school), $schema->options);
            $this->assertSame($schema->options, $schema->field($schema->fields[0]->name)->options);
        }
    }

    public function test_country_options_match_the_wizard_select(): void
    {
        $schema = $this->registry->byKey('country')->schema($this->school);
        $fromTable = Country::query()->orderBy('order')->orderBy('name')->pluck('name')->all();

        $this->assertSame($fromTable, $schema->optionValues());

        Country::query()->delete();
        $fallback = $this->registry->byKey('country')->schema($this->school->fresh());

        $this->assertSame(['Uganda', 'Kenya', 'Tanzania'], $fallback->optionValues());
    }

    public function test_schema_accepts_the_same_answers_as_step_validate(): void
    {
        $inactive = Plan::create([
            'name' => 'Retired',
            'display_name' => 'Retired',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 2,
            'is_active' => 0,
        ]);

        $cases = [
            'school_name' => [
                ['Sunrise Academy', true],
                ['A', true],
                ['  A  ', true],
                ['', false],
                ['   ', false],
                [null, false],
                [12, false],
            ],
            'student_size' => [
                ['Up to 500', true],
                ['Up to 1,000', true],
                ['More than 1,000', true],
                ['up to 500', false],
                ['', false],
                ['huge', false],
            ],
            'country' => [
                ['Uganda', true],
                ['UG', true],
                ['France', true],
                ['U', false],
                ['', false],
                [' ', false],
                [null, false],
            ],
            'curriculum' => [
                ['uneb', true],
                ['cambridge', true],
                ['montessori', true],
                ['other', true],
                ['UNEB', false],
                ['ib', false],
                ['', false],
            ],
            'school_category' => [
                ['nursery', true],
                ['primary', true],
                ['primary_nursery', true],
                ['o_level', true],
                ['o_a_level', true],
                ['Primary', false],
                ['university', false],
            ],
            'emis' => [
                ['EMIS-1001', true],
                ['123456789012345678901', true],
                ['', false],
                ['   ', false],
                [null, false],
            ],
            'uneb_center' => [
                ['', true],
                ['U1234', true],
                [null, false],
                [12, false],
            ],
            'academic_year' => [
                [['name' => '2026', 'free_text' => false], true],
                [['name' => 'hello', 'free_text' => false], true],
                [['name' => '2026'], true],
                [['name' => '', 'free_text' => false], false],
                [['name' => 'yes', 'free_text' => true], false],
                [['name' => '2026', 'free_text' => true], true],
                [['name' => '2026/2027', 'free_text' => true], true],
                [['name' => '2026-2027', 'free_text' => true], true],
                [['name' => '2026 / 2027', 'free_text' => true], true],
                [['name' => 'hello', 'free_text' => true], false],
                [['name' => '1899', 'free_text' => true], false],
                [['name' => '1990', 'free_text' => true], true],
                [['name' => '2200', 'free_text' => true], true],
                [['name' => '2201', 'free_text' => true], false],
                [['name' => '2027/2026', 'free_text' => true], false],
                ['2026', false],
            ],
            'standards' => [
                [[], true],
                [[['name' => 'P.1']], true],
                ['skip', false],
                ['confirm_seeded', false],
                ['nope', false],
            ],
            'subjects' => [
                [[], true],
                [['Primary One' => ['Mathematics']], true],
                ['skip', false],
                ['confirm_seeded', false],
                ['nope', false],
            ],
            'teachers' => [
                ['skip', true],
                [[], true],
                [[['name' => '']], true],
                ['nope', false],
            ],
            'students' => [
                ['skip', true],
                [[], true],
                [[['name' => 'Amina']], true],
                ['nope', false],
            ],
            'terms' => [
                [[], true],
                [[['name' => 'Term 1']], true],
                ['skip', false],
                ['nope', false],
            ],
            'fees' => [
                [[], false],
                [[['name' => '', 'amount' => 1]], false],
                [[['name' => 'Tuition', 'amount' => 0]], false],
                [[['name' => 'Tuition', 'amount' => '0']], false],
                [[['name' => 'Tuition', 'amount' => null]], false],
                [[['name' => 'Tuition']], false],
                [['not-an-array'], false],
                [[['name' => 'Tuition', 'amount' => '25000']], true],
                [[['name' => 'Tuition', 'amount' => 1]], true],
                ['nope', false],
            ],
            'whatsapp_verify' => [
                ['0772000111', true],
                ['skip', true],
                ['', false],
                ['   ', false],
                [null, false],
                [256, false],
            ],
            'plan_selection' => [
                [(int) $this->plan->id, true],
                [0, false],
                [99999, false],
                [(string) $this->plan->id, false],
                [(int) $inactive->id, false],
            ],
        ];

        foreach ($cases as $key => $rows) {
            $step = $this->registry->byKey($key);
            $schema = $step->schema($this->school);

            foreach ($rows as [$value, $expected]) {
                $this->assertSame(
                    $expected,
                    $schema->accepts($this->school, $value),
                    $key.' schema decision for '.json_encode($value)
                );
                $this->assertSame(
                    $expected,
                    $this->stepAccepts($step, $value),
                    $key.' validate() decision for '.json_encode($value)
                );
            }
        }

        $this->seedClassLink();
        $standards = $this->registry->byKey('standards');
        $this->assertTrue($standards->schema($this->school)->accepts($this->school, 'confirm_seeded'));
        $this->assertTrue($this->stepAccepts($standards, 'confirm_seeded'));

        $subjects = $this->registry->byKey('subjects');
        $this->assertFalse($subjects->schema($this->school)->accepts($this->school, 'confirm_seeded'));
        $this->assertFalse($this->stepAccepts($subjects, 'confirm_seeded'));

        $link = StandardLink::where('school_id', $this->school->id)->first();
        Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $link->academic_year_id,
            'standard_id' => $link->standard_id,
            'section_id' => $link->section_id,
            'name' => 'Mathematics',
        ]);
        $this->assertTrue($subjects->schema($this->school)->accepts($this->school, 'confirm_seeded'));
        $this->assertTrue($this->stepAccepts($subjects, 'confirm_seeded'));
    }

    public function test_save_rules_match_engine_rejections(): void
    {
        $engine = app(OnboardingEngine::class);
        $originalName = $this->school->name;

        $schema = $this->registry->byKey('school_name')->schema($this->school);
        $this->assertSame(['min:3', 'not_placeholder'], $schema->saveRules['name']);
        $this->assertEngineRejects(
            fn () => $engine->saveSchoolName($this->school, 'Ab'),
            'at least 3 characters'
        );
        $this->assertEngineRejects(
            fn () => $engine->saveSchoolName($this->school, "Ada's School"),
            'Enter your real school name.'
        );
        $this->assertSame($originalName, $this->school->fresh()->name);

        $this->school->forceFill(['registration_country' => 'Uganda'])->save();
        $emis = $this->registry->byKey('emis')->schema($this->school->fresh());
        $this->assertSame(['max:20', 'unique'], $emis->saveRules['ministry_code']);
        $this->assertEngineRejects(
            fn () => $engine->saveEmis($this->school->fresh(), '123456789012345678901'),
            'at most 20 characters'
        );

        School::create([
            'name' => 'Other Schema School',
            'email' => 'other-schema@test.sch.ug',
            'phone' => '0700000089',
            'slug' => 'other-schema',
            'status' => 1,
            'ministry_code' => 'EMIS-TAKEN',
        ]);
        $this->assertEngineRejects(
            fn () => $engine->saveEmis($this->school->fresh(), 'EMIS-TAKEN'),
            'already used'
        );
        $this->assertNull($this->school->fresh()->ministry_code);

        $yearSchema = $this->registry->byKey('academic_year')->schema($this->school);
        $this->assertSame(['after:start'], $yearSchema->saveRules['end']);
        $this->assertSame(['both_or_neither'], $yearSchema->saveRules['dates']);
        $this->assertEngineRejects(
            fn () => $engine->saveAcademicYear($this->school, '2026', '2026-06-01', '2026-01-01'),
            'end date must be after the start date'
        );
        $this->assertNull(AcademicYear::where('school_id', $this->school->id)->first());

        $terms = $this->registry->byKey('terms')->schema($this->school);
        $this->assertSame(['min:1'], $terms->saveRules['terms']);
        $this->assertEngineRejects(
            fn () => $engine->saveTerms($this->school, new AcademicYear(), []),
            'Add at least one term.'
        );
    }

    public function test_defaults_match_the_wizard_prefill(): void
    {
        $props = (new ReflectionClass(ManualOnboardingWizard::class))->getDefaultProperties();
        $school = $this->school;

        $this->assertSame($props['schoolName'], $this->schema('school_name')->defaults['name']);
        $this->assertSame($props['studentSize'], $this->schema('student_size')->defaults['student_size']);
        $this->assertSame($props['countryName'], $this->schema('country')->defaults['country']);
        $this->assertSame($props['curriculum'], $this->schema('curriculum')->defaults['curriculum']);
        $this->assertSame($props['schoolCategory'], $this->schema('school_category')->defaults['school_category']);
        $this->assertSame($props['ministryCode'], $this->schema('emis')->defaults['ministry_code']);
        $this->assertSame($props['unebCenterNumber'], $this->schema('uneb_center')->defaults['uneb_center_number']);
        $this->assertSame($props['academicYearDescription'], $this->schema('academic_year')->defaults['description']);
        $this->assertSame(now()->startOfYear()->toDateString(), $this->schema('academic_year')->defaults['start']);
        $this->assertSame(now()->endOfYear()->toDateString(), $this->schema('academic_year')->defaults['end']);
        $this->assertSame($props['className'], $this->schema('standards')->defaults['class_name']);
        $this->assertSame($props['subjectName'], $this->schema('subjects')->defaults['subject_name']);
        $this->assertSame([], $this->schema('teachers')->defaults);
        $this->assertSame([], $this->schema('students')->defaults);
        $this->assertSame($props['feeName'], $this->schema('fees')->defaults['name']);
        $this->assertSame($props['feeAmount'], $this->schema('fees')->defaults['amount']);
        $this->assertSame($props['feeScope'], $this->schema('fees')->defaults['scope']);
        $this->assertSame($props['feeIsYearly'], $this->schema('fees')->defaults['is_yearly']);
        $this->assertSame('', $this->schema('whatsapp_verify')->defaults['phone']);
        $this->assertSame((int) $this->plan->id, $this->schema('plan_selection')->defaults['plan_id']);
        $this->assertSame($this->wizardDefaultPlanId(), $this->schema('plan_selection')->defaults['plan_id']);

        $year = (int) now()->year;
        $this->assertSame($this->wizardTermDrafts($year), $this->schema('terms')->defaults['terms']);
        $this->assertSame('Term 1', $this->schema('terms')->defaults['current_term']);

        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2031',
            'description' => 'Current Academic Year',
            'status' => 1,
            'start_date' => '2031-01-01 00:00:00',
            'end_date' => '2031-12-31 23:59:59',
        ]);

        $this->assertSame(
            $this->wizardTermDrafts(2031),
            $this->registry->byKey('terms')->schema($school->fresh())->defaults['terms']
        );
    }

    public function test_upload_fields_use_the_wizard_file_rules(): void
    {
        foreach ([
            ['teachers', 'teacher_upload'],
            ['teachers', 'class_teacher_upload'],
            ['students', 'student_upload'],
        ] as [$step, $field]) {
            $upload = $this->schema($step)->field($field);
            $this->assertSame('file', $upload->type);
            $this->assertSame(['file', 'max:10240'], $upload->rules);
            $this->assertSame(OnboardingNameListExtractor::ACCEPTED_MIMES, $upload->extensions);
        }
    }

    public function test_reading_a_schema_does_not_write(): void
    {
        $updated = (string) $this->school->updated_at;

        foreach ($this->registry->all() as $step) {
            $schema = $step->schema($this->school);
            $schema->accepts($this->school, null);
        }

        $this->assertSame($updated, (string) $this->school->fresh()->updated_at);
        $this->assertSame("Schema's School", $this->school->fresh()->name);
    }

    private function schema(string $key): \App\Onboarding\Steps\StepSchema
    {
        return $this->registry->byKey($key)->schema($this->school);
    }

    private function stepAccepts(object $step, mixed $value): bool
    {
        try {
            $step->validate($this->school, $value);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    private function assertEngineRejects(callable $call, string $needle): void
    {
        try {
            $call();
            $this->fail('Expected the engine to reject this answer.');
        } catch (ValidationException $e) {
            $message = (string) collect($e->errors())->flatten()->first();
            $this->assertStringContainsString($needle, $message);
        }
    }

    private function wizardDefaultPlanId(): ?int
    {
        $reflection = new ReflectionClass(ManualOnboardingWizard::class);
        $wizard = $reflection->newInstanceWithoutConstructor();
        $selected = $reflection->getProperty('selectedPlanId');
        $selected->setValue($wizard, null);
        $method = new ReflectionMethod($wizard, 'defaultSelectedPlan');
        $method->setAccessible(true);
        $method->invoke($wizard);

        $planId = $selected->getValue($wizard);

        return $planId === null ? null : (int) $planId;
    }

    /**
     * @return list<array{name: string, start: string, end: string}>
     */
    private function wizardTermDrafts(int $year): array
    {
        $wizard = (new ReflectionClass(ManualOnboardingWizard::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($wizard, 'defaultTermDrafts');
        $method->setAccessible(true);

        return $method->invoke($wizard, $year);
    }

    private function seedClassLink(): void
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'status' => 1,
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One',
        ]);
        StandardLink::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
        ]);
    }
}
