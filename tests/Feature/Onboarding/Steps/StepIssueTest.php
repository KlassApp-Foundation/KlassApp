<?php

namespace Tests\Feature\Onboarding\Steps;

use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Onboarding\Steps\OnboardingStepAwaitingInput;
use App\Onboarding\Steps\OnboardingStepValidationException;
use App\Onboarding\Steps\StepIssue;
use App\Onboarding\Steps\StepIssueCatalog;
use App\Onboarding\Steps\StepIssueException;
use App\Onboarding\Steps\StepRegistry;
use App\Onboarding\Steps\StepSchemaCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class StepIssueTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

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
            'name' => "Issue's School",
            'email' => 'step-issue@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'step-issue',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 0,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Issue Admin',
            'email' => 'admin@step-issue.sch.ug',
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

    public function test_every_validation_rejection_has_a_cause_and_a_fix(): void
    {
        $year = StepIssueCatalog::academicYearCause();
        $cases = [
            ['school_name', '', 'school_name.required', 'Enter your real school name.'],
            ['school_name', '   ', 'school_name.required', 'Enter your real school name.'],
            ['student_size', 'huge', 'student_size.choice', 'Choose one of the school size options.'],
            ['country', 'U', 'country.required', 'Enter a country.'],
            ['country', '', 'country.required', 'Enter a country.'],
            ['curriculum', 'ib', 'curriculum.choice', 'Choose a curriculum option.'],
            ['school_category', 'university', 'school_category.choice', 'Choose a school category.'],
            ['emis', '', 'emis.required', 'Enter your EMIS / ministry code.'],
            ['uneb_center', null, 'uneb_center.invalid', 'Enter a UNEB centre number or skip.'],
            ['uneb_center', 12, 'uneb_center.invalid', 'Enter a UNEB centre number or skip.'],
            ['academic_year', '2026', $year, 'academic_year.invalid'],
            ['academic_year', ['name' => '', 'free_text' => false], $year, 'academic_year.invalid'],
            ['academic_year', ['name' => 'hello', 'free_text' => true], $year, 'academic_year.invalid'],
            ['standards', 'skip', 'standards.skip', 'This step cannot be skipped.'],
            ['standards', 'confirm_seeded', 'standards.confirm', 'Add or confirm your classes first.'],
            ['standards', 'nope', 'standards.list', 'Provide a valid list for this step.'],
            ['subjects', 'skip', 'subjects.skip', 'This step cannot be skipped.'],
            ['subjects', 'confirm_seeded', 'subjects.confirm', 'Add or confirm subjects first.'],
            ['subjects', 'nope', 'subjects.list', 'Provide a valid list for this step.'],
            ['teachers', 'nope', 'teachers.list', 'Provide a valid list for this step.'],
            ['students', 'nope', 'students.list', 'Provide a valid list for this step.'],
            ['terms', 'skip', 'terms.skip', 'This step cannot be skipped.'],
            ['terms', 'nope', 'terms.list', 'Provide a valid list for this step.'],
            ['fees', [], 'fees.required', 'Add at least one fee structure.'],
            ['fees', 'nope', 'fees.required', 'Add at least one fee structure.'],
            ['fees', [['name' => '', 'amount' => 1]], 'fees.name', 'Enter a fee name and an amount.'],
            ['fees', [['name' => 'Tuition', 'amount' => 0]], 'fees.amount', StepIssueCatalog::feeAmountCause('Tuition')],
            ['whatsapp_verify', '', 'whatsapp.phone', 'Enter a WhatsApp phone number.'],
            ['whatsapp_verify', '   ', 'whatsapp.phone', 'Enter a WhatsApp phone number.'],
            ['plan_selection', 0, 'plan.required', 'Choose a plan.'],
            ['plan_selection', 99999, 'plan.unavailable', 'That plan is not available.'],
        ];

        foreach ($cases as [$key, $value, $codeOrCause, $cause]) {
            if ($codeOrCause === $year) {
                $code = $cause;
                $cause = $codeOrCause;
            } else {
                $code = $codeOrCause;
            }

            $this->assertIssue($key, $value, $code, $cause);
        }
    }

    public function test_fee_row_issues_keep_the_first_message(): void
    {
        $step = $this->registry->byKey('fees');
        $value = [
            ['name' => 'Tuition', 'amount' => 1],
            ['name' => 'Lab', 'amount' => 0],
        ];
        $issues = $step->issues($this->school, $value);

        $this->assertCount(1, $issues);
        $this->assertSame(1, $issues[0]->row);
        $this->assertSame('fees.amount', $issues[0]->code);
        $this->assertSame(StepIssueCatalog::feeAmountCause('Lab'), $issues[0]->cause);
        $this->assertNotSame('', $issues[0]->fix);

        try {
            $step->validate($this->school, $value);
            $this->fail('Expected the fee amount to be rejected.');
        } catch (StepIssueException $e) {
            $this->assertSame($issues[0]->cause, $e->getMessage());
        }
    }

    public function test_save_time_rules_have_their_own_issues_and_the_same_message(): void
    {
        $step = $this->registry->byKey('school_name');
        $short = $step->saveIssues($this->school, 'Ab');
        $this->assertIssueShape($short[0], 'school_name.min', 'The school name must be at least 3 characters.');
        $this->assertSame([], $step->issues($this->school, 'Ab'));
        $this->assertSaveMessage(
            $step,
            'Ab',
            'school_name.min',
            'The school name must be at least 3 characters.',
            'name',
        );
        $this->assertSame("Issue's School", $this->school->fresh()->name);

        $placeholder = $step->saveIssues($this->school, "Ada's School");
        $this->assertIssueShape($placeholder[0], 'school_name.placeholder', 'Enter your real school name.');
        $this->assertSaveMessage(
            $step,
            "Ada's School",
            'school_name.placeholder',
            'Enter your real school name.',
            'name',
        );

        $this->school->forceFill(['registration_country' => 'Uganda'])->save();
        $emis = $this->registry->byKey('emis');
        $long = str_repeat('9', 21);
        $this->assertIssueShape(
            $emis->saveIssues($this->school->fresh(), $long)[0],
            'emis.max',
            'An EMIS / ministry code can be at most 20 characters.',
        );
        $this->assertSame([], $emis->issues($this->school->fresh(), $long));
        $this->assertSaveMessage(
            $emis,
            $long,
            'emis.max',
            'An EMIS / ministry code can be at most 20 characters.',
            'ministryCode',
        );

        School::create([
            'name' => 'Other Issue School',
            'email' => 'other-issue@test.sch.ug',
            'phone' => '0700000089',
            'slug' => 'other-issue',
            'status' => 1,
            'ministry_code' => 'EMIS-TAKEN',
        ]);
        $this->assertIssueShape(
            $emis->saveIssues($this->school->fresh(), 'EMIS-TAKEN')[0],
            'emis.unique',
            'That EMIS / ministry code is already used by another school. Check the code and try again.',
        );
        $this->assertSaveMessage(
            $emis,
            'EMIS-TAKEN',
            'emis.unique',
            'That EMIS / ministry code is already used by another school. Check the code and try again.',
            'ministryCode',
        );
        $this->assertNull($this->school->fresh()->ministry_code);

        $year = $this->registry->byKey('academic_year');
        $dates = ['name' => '2026', 'start' => '2026-06-01', 'end' => '2026-01-01', 'free_text' => false];
        $this->assertSame([], $year->issues($this->school, $dates));
        $this->assertIssueShape(
            $year->saveIssues($this->school, $dates)[0],
            'academic_year.end',
            'The academic year end date must be after the start date.',
        );
        $oneDate = ['name' => '2026', 'start' => '2026-01-01', 'end' => null, 'free_text' => false];
        $this->assertIssueShape(
            $year->saveIssues($this->school, $oneDate)[0],
            'academic_year.dates',
            'Provide both start and end dates.',
        );
        $this->assertSaveMessage(
            $year,
            $dates,
            'academic_year.end',
            'The academic year end date must be after the start date.',
            'academicYearEnd',
        );
        $this->assertNull(AcademicYear::where('school_id', $this->school->id)->first());

        $this->makeYear();
        $terms = $this->registry->byKey('terms');
        $this->assertSame([], $terms->issues($this->school->fresh(), []));
        $this->assertIssueShape(
            $terms->saveIssues($this->school->fresh(), [])[0],
            'terms.min',
            'Add at least one term.',
        );
        $this->assertSaveMessage($terms, [], 'terms.min', 'Add at least one term.', 'terms');

        $standards = $this->registry->byKey('standards');
        $this->assertIssueShape(
            $standards->saveIssues($this->school->fresh(), [])[0],
            'standards.min',
            'Add at least one class.',
        );
        $unnamed = $standards->saveIssues($this->school->fresh(), [['name' => ''], ['name' => 'P.1'], ['name' => 'P.1']]);
        $this->assertIssueShape($unnamed[0], 'standards.name', 'Each class must have a name.');
        $this->assertSame(0, $unnamed[0]->row);
    }

    public function test_missing_academic_year_is_an_issue_before_later_steps_save(): void
    {
        $cases = [
            'standards' => [['name' => 'P.1']],
            'subjects' => ['P.1' => ['Mathematics']],
            'teachers' => [['name' => 'Grace Nabirye', 'email' => 'grace@step-issue.test']],
            'students' => [['name' => 'Amina Okello', 'class' => 'Primary One']],
            'terms' => [['name' => 'Term 1']],
        ];

        foreach ($cases as $key => $value) {
            $step = $this->registry->byKey($key);
            $issues = $step->saveIssues($this->school, $value);
            $this->assertIssueShape($issues[0], $key.'.year', 'Create an academic year before this step.');

            try {
                $step->save($this->school, $value);
                $this->fail($key.' saved without an academic year.');
            } catch (StepIssueException $e) {
                $this->assertSame('Create an academic year before this step.', $e->getMessage());
                $this->assertSame($key.'.year', $e->issue->code);
                $this->assertNotSame('', $e->issue->fix);
            }
        }
    }

    public function test_typed_roster_lines_are_issues(): void
    {
        $teachers = $this->registry->byKey('teachers');
        $students = $this->registry->byKey('students');
        $teacherLine = 'Grace Nabirye, grace@school.test, 0772000000, Primary One, Mathematics';
        $studentLine = 'Amina Okello | Primary One | A | 0772000001';

        $teacherIssue = $teachers->inputIssues($this->school, $teacherLine)[0];
        $this->assertIssueShape($teacherIssue, 'teachers.line', 'Add a teacher with a name and an email, or type skip.');
        $this->assertStringContainsString('email', strtolower($teacherIssue->cause));

        $studentIssue = $students->inputIssues($this->school, $studentLine)[0];
        $this->assertIssueShape($studentIssue, 'students.line', 'Add a student with a name and a class, or type skip.');
        $this->assertStringContainsString('class', strtolower($studentIssue->cause));

        try {
            $teachers->save($this->school, $teacherLine);
            $this->fail('A teacher line was accepted.');
        } catch (StepIssueException $e) {
            $this->assertSame($teacherIssue->cause, $e->getMessage());
        }
    }

    public function test_plan_id_text_is_an_issue_and_the_integer_id_is_accepted(): void
    {
        $step = $this->registry->byKey('plan_selection');
        $schema = $step->schema($this->school);

        $this->assertSame([], $step->issues($this->school, (int) $this->plan->id));
        $this->assertTrue($schema->accepts($this->school, (int) $this->plan->id));

        $issues = $step->issues($this->school, (string) $this->plan->id);
        $this->assertIssueShape($issues[0], 'plan.id_type', 'Choose a plan.');
        $this->assertSame('plan_id', $issues[0]->field);
        $this->assertFalse($schema->accepts($this->school, (string) $this->plan->id));
        $this->assertNotSame('', $issues[0]->fix);

        try {
            $step->validate($this->school, (string) $this->plan->id);
            $this->fail('A text plan id was accepted.');
        } catch (StepIssueException $e) {
            $this->assertSame('Choose a plan.', $e->getMessage());
        }
    }

    public function test_whatsapp_code_mismatch_has_a_cause_and_keeps_the_reply(): void
    {
        session([
            'wizard_wa_otp.code' => '123456',
            'wizard_wa_otp.phone' => '+256700000111',
            'wizard_wa_otp.user_id' => $this->admin->id,
            'wizard_wa_otp.expires_at' => now()->addMinutes(10)->timestamp,
            'wizard_wa_otp.attempts' => 0,
        ]);

        $step = $this->registry->byKey('whatsapp_verify');

        try {
            $step->save($this->school, '000000', $this->admin->id);
            $this->fail('A wrong WhatsApp code was accepted.');
        } catch (OnboardingStepAwaitingInput $e) {
            $this->assertSame(
                "That code doesn't match. Try again or send a new code. Reply **resend** for a new code, or **retry** if it did not arrive.",
                $e->getMessage(),
            );
            $this->assertNotNull($e->issue);
            $this->assertSame('whatsapp.code_mismatch', $e->issue->code);
            $this->assertSame('code', $e->issue->field);
            $this->assertSame("That code doesn't match. Try again or send a new code.", $e->issue->cause);
            $this->assertNotSame('', $e->issue->fix);
            $this->assertStringNotContainsString('123456', $e->getMessage());
        }
    }

    public function test_other_whatsapp_code_failures_have_a_cause_and_a_fix(): void
    {
        $messages = [
            'That code has expired. Send a new one.' => 'whatsapp.code_expired',
            'That code is no longer valid. Request a new one.' => 'whatsapp.code_invalid',
            'Too many incorrect attempts. That code is no longer valid. Request a new one.' => 'whatsapp.code_invalid',
            'Phone number changed after the code was sent. Send a new code.' => 'whatsapp.code_phone',
            'Send a verification code first.' => 'whatsapp.code_missing',
        ];

        foreach ($messages as $message => $code) {
            $issue = StepIssueCatalog::whatsappVerification($message);
            $this->assertSame($code, $issue->code);
            $this->assertSame($message, $issue->cause);
            $this->assertSame('code', $issue->field);
            $this->assertNotSame('', $issue->fix);
        }
    }

    public function test_accepted_answers_have_no_validation_issue(): void
    {
        $this->school->forceFill(['registration_country' => 'Uganda'])->save();
        $school = $this->school->fresh();
        $accepted = [
            'school_name' => 'Sunrise Academy',
            'student_size' => 'Up to 500',
            'country' => 'Uganda',
            'curriculum' => 'uneb',
            'school_category' => 'primary',
            'emis' => 'EMIS-1001',
            'uneb_center' => '',
            'academic_year' => ['name' => '2026', 'free_text' => false],
            'standards' => [],
            'subjects' => [],
            'teachers' => 'skip',
            'students' => 'skip',
            'terms' => [],
            'fees' => [['name' => 'Tuition', 'amount' => '25000']],
            'whatsapp_verify' => '0772000111',
            'plan_selection' => (int) $this->plan->id,
        ];

        foreach ($accepted as $key => $value) {
            $step = $this->registry->byKey($key);
            $this->assertSame([], $step->issues($school, $value), $key);
            $this->assertTrue($step->schema($school)->accepts($school, $value), $key);
        }

        $this->assertSame([], $this->registry->byKey('school_name')->saveIssues($school, 'Sunrise Academy'));
    }

    public function test_onboarding_issues_exclude_exams_and_schoolpay(): void
    {
        foreach (StepSchemaCatalog::EXCLUDED_KEYS as $key) {
            try {
                StepIssueCatalog::saveIssues($key, $this->school, []);
                $this->fail("Expected [{$key}] to stay out of onboarding.");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString($key, $e->getMessage());
            }
        }
    }

    public function test_an_unlisted_save_error_still_has_a_cause_and_a_fix(): void
    {
        $exception = ValidationException::withMessages(['classes' => 'Duplicate class name: P.1.']);
        $issue = StepIssueCatalog::fromValidation('standards', $exception);

        $this->assertSame('standards.duplicate', $issue->code);
        $this->assertSame('Duplicate class name: P.1.', $issue->cause);
        $this->assertNotSame('', $issue->fix);
    }

    private function assertIssue(string $key, mixed $value, string $code, string $cause): void
    {
        $step = $this->registry->byKey($key);
        $issues = $step->issues($this->school, $value);

        $this->assertNotEmpty($issues, $key);
        $this->assertIssueShape($issues[0], $code, $cause, $key);
        $this->assertFalse($step->schema($this->school)->accepts($this->school, $value), $key);

        try {
            $step->validate($this->school, $value);
            $this->fail($key.' accepted '.$cause);
        } catch (InvalidArgumentException $e) {
            $this->assertSame($cause, $e->getMessage(), $key);
        }
    }

    private function assertIssueShape(StepIssue $issue, string $code, string $cause, string $label = ''): void
    {
        $this->assertSame($code, $issue->code, $label !== '' ? $label : $cause);
        $this->assertSame($cause, $issue->cause, $label !== '' ? $label : $code);
        $this->assertNotSame('', trim($issue->cause), $code);
        $this->assertNotSame('', trim($issue->fix), $code);
    }

    private function assertSaveMessage(object $step, mixed $value, string $code, string $cause, string $errorKey): void
    {
        try {
            $step->save($this->school->fresh(), $value);
            $this->fail('Expected the save to be rejected: '.$cause);
        } catch (OnboardingStepValidationException $e) {
            $message = (string) collect($e->errors())->flatten()->first();
            $this->assertSame($cause, $message);
            $this->assertSame($cause, $e->issue->cause);
            $this->assertSame($code, $e->issue->code);
            $this->assertNotSame('', $e->issue->fix);
            $this->assertArrayHasKey($errorKey, $e->errors());
        }
    }

    private function makeYear(): void
    {
        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'status' => 1,
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
        ]);
    }
}
