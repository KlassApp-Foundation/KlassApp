<?php

namespace App\Onboarding\Steps;

use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Services\OnboardingNameListExtractor;
use InvalidArgumentException;

/**
 * Step schemas for the wizard onboarding steps.
 *
 * Exams and SchoolPay are not onboarding steps. Nothing here is read by the
 * wizard or by Toshi, so saving behaviour is unchanged.
 */
final class StepSchemaCatalog
{
    /** @var list<string> */
    public const EXCLUDED_KEYS = ['exams', 'exam', 'schoolpay', 'school_pay'];

    /**
     * @param  list<array{value: string, label: string}>  $stepOptions
     */
    public static function for(string $key, School $school, array $stepOptions): StepSchema
    {
        if (in_array($key, self::EXCLUDED_KEYS, true)) {
            throw new InvalidArgumentException("[{$key}] is not an onboarding step.");
        }

        return match ($key) {
            'school_name' => self::schoolName(),
            'student_size' => self::studentSize($stepOptions),
            'country' => self::country(),
            'curriculum' => self::curriculum($stepOptions),
            'school_category' => self::schoolCategory($stepOptions),
            'emis' => self::emis(),
            'uneb_center' => self::unebCenter(),
            'academic_year' => self::academicYear(),
            'standards' => self::standards(),
            'subjects' => self::subjects(),
            'teachers' => self::teachers(),
            'students' => self::students(),
            'terms' => self::terms($school),
            'fees' => self::fees(),
            'whatsapp_verify' => self::whatsapp(),
            'plan_selection' => self::plan($stepOptions),
            default => throw new InvalidArgumentException("No onboarding step schema for [{$key}]."),
        };
    }

    private static function schoolName(): StepSchema
    {
        return self::make(
            'school_name',
            [self::text('name', 'School name', true, ['required', 'string'])],
            'Type the school name and confirm it.',
            'Save the name on the school.',
            ['name' => ''],
            [],
            ['kind' => 'filled_string'],
            ['name' => ['min:3', 'not_placeholder']],
        );
    }

    /**
     * @param  list<array{value: string, label: string}>  $options
     */
    private static function studentSize(array $options): StepSchema
    {
        return self::choiceStep(
            'student_size',
            'student_size',
            'Approximate number of students',
            'Choose a size and confirm it.',
            'Save the size on the school.',
            $options,
            ['student_size' => ''],
            '',
        );
    }

    private static function country(): StepSchema
    {
        $options = self::countryOptions();

        return self::make(
            'country',
            [self::choice('country', 'Country', true, ['required', 'string', 'min:2'], $options, 'Uganda')],
            'Choose the country and confirm it.',
            'Save the country on the school.',
            ['country' => 'Uganda'],
            $options,
            ['kind' => 'min_string', 'min' => 2],
        );
    }

    /**
     * @param  list<array{value: string, label: string}>  $options
     */
    private static function curriculum(array $options): StepSchema
    {
        return self::choiceStep(
            'curriculum',
            'curriculum',
            'Board / Curriculum',
            'Choose the board or curriculum and confirm it.',
            'Save the curriculum on the school.',
            $options,
            ['curriculum' => 'uneb'],
            'uneb',
        );
    }

    /**
     * @param  list<array{value: string, label: string}>  $options
     */
    private static function schoolCategory(array $options): StepSchema
    {
        return self::choiceStep(
            'school_category',
            'school_category',
            'School category',
            'Choose the kind of school and confirm it.',
            'Save the category and create its classes and subjects.',
            $options,
            ['school_category' => ''],
            '',
        );
    }

    private static function emis(): StepSchema
    {
        return self::make(
            'emis',
            [self::text('ministry_code', 'EMIS / Ministry code', true, ['required', 'string'])],
            'Enter the EMIS code and confirm it.',
            'Save the ministry code on the school.',
            ['ministry_code' => ''],
            [],
            ['kind' => 'filled_string'],
            ['ministry_code' => ['max:20', 'unique']],
        );
    }

    private static function unebCenter(): StepSchema
    {
        return self::make(
            'uneb_center',
            [self::text('uneb_center_number', 'UNEB centre number', false, ['string'], '')],
            'Enter the UNEB centre number, or confirm a blank skip.',
            'Save the centre number on the school.',
            ['uneb_center_number' => ''],
            [],
            ['kind' => 'any_string'],
        );
    }

    private static function academicYear(): StepSchema
    {
        $start = now()->startOfYear()->toDateString();
        $end = now()->endOfYear()->toDateString();

        return self::make(
            'academic_year',
            [
                self::text('description', 'Description', false, ['string'], 'Current Academic Year'),
                self::field('start', 'Starts on', 'date', true, ['required', 'date'], default: $start),
                self::field('end', 'Ends on', 'date', true, ['required', 'date', 'after:start'], default: $end),
            ],
            'Confirm the year dates.',
            'Create the academic year.',
            [
                'description' => 'Current Academic Year',
                'start' => $start,
                'end' => $end,
            ],
            [],
            ['kind' => 'academic_year'],
            ['end' => ['after:start'], 'dates' => ['both_or_neither']],
        );
    }

    private static function standards(): StepSchema
    {
        return self::make(
            'standards',
            [
                self::text('class_name', 'Class', false, ['string'], 'P1'),
                self::text('stream', 'Stream', false, ['string']),
                self::text('class_teacher_name', 'Class teacher name', false, ['string']),
                self::text('class_teacher_email', 'Class teacher email', false, ['nullable', 'email']),
                self::text('class_teacher_phone', 'Class teacher phone', false, ['nullable']),
            ],
            'Confirm the classes, or add a class, streams, and a class teacher.',
            'Create the classes and streams.',
            ['class_name' => 'P1'],
            [],
            ['kind' => 'list', 'allow_skip' => false, 'confirm_seeded' => 'standards_link'],
        );
    }

    private static function subjects(): StepSchema
    {
        return self::make(
            'subjects',
            [self::text('subject_name', 'Subjects', false, ['string'], 'Mathematics')],
            'Confirm the subjects, or add more.',
            'Create the subjects on each class.',
            ['subject_name' => 'Mathematics'],
            [],
            ['kind' => 'list', 'allow_skip' => false, 'confirm_seeded' => 'subjects'],
        );
    }

    private static function teachers(): StepSchema
    {
        return self::make(
            'teachers',
            [
                self::upload('teacher_upload', 'Teacher file'),
                self::upload('class_teacher_upload', 'Class teacher file'),
                self::text('teacher_name', 'Teacher name', true, ['required', 'string']),
                self::text('email', 'Email', false, ['nullable', 'email']),
                self::text('phone', 'Phone', false, ['nullable']),
                self::text('classes', 'Classes taught', false, ['array']),
                self::text('subjects', 'Subjects taught', false, ['array']),
            ],
            'Upload or confirm the teacher list, or skip.',
            'Create the teacher accounts and link their classes and subjects.',
            [],
            [],
            ['kind' => 'list', 'allow_skip' => true],
        );
    }

    private static function students(): StepSchema
    {
        return self::make(
            'students',
            [
                self::upload('student_upload', 'Student file'),
                self::text('student_name', 'Student name', true, ['required', 'string']),
                self::text('class', 'Class', false, ['nullable', 'string']),
                self::text('stream', 'Stream', false, ['nullable', 'string']),
                self::choice('gender', 'Gender', false, ['nullable'], [
                    ['value' => 'female', 'label' => 'Female'],
                    ['value' => 'male', 'label' => 'Male'],
                ]),
                self::field('date_of_birth', 'Date of birth', 'date', false, ['nullable', 'date']),
                self::text('parent', 'Parent name', false, ['nullable', 'string']),
                self::text('parent_phone', 'Parent phone', false, ['nullable']),
                self::text('school_student_id', 'School Student ID', false, ['nullable', 'string']),
                self::text('lin', 'LIN', false, ['nullable', 'string']),
                self::text('board_registration_number', 'UNEB Reg No.', false, ['nullable', 'string']),
            ],
            'Upload or confirm the student list, or skip.',
            'Create the students and link parent contacts.',
            [],
            [],
            ['kind' => 'list', 'allow_skip' => true],
        );
    }

    private static function terms(School $school): StepSchema
    {
        $drafts = self::termDrafts(self::termYear($school));

        return self::make(
            'terms',
            [
                self::field('terms', 'Academic terms', 'list', true, ['array'], children: [
                    self::text('name', 'Term name', true, ['required', 'string']),
                    self::field('start', 'Starts on', 'date', true, ['required', 'date']),
                    self::field('end', 'Ends on', 'date', true, ['required', 'date', 'after:start']),
                ]),
                self::text('current_term', 'Current term', true, ['required', 'string'], 'Term 1'),
            ],
            'Confirm the terms and which one is current.',
            'Create the academic terms.',
            ['terms' => $drafts, 'current_term' => 'Term 1'],
            [],
            ['kind' => 'list', 'allow_skip' => false],
            ['terms' => ['min:1']],
        );
    }

    private static function fees(): StepSchema
    {
        $scope = [
            ['value' => 'whole_school', 'label' => 'Whole school'],
            ['value' => 'class', 'label' => 'One class'],
        ];

        return self::make(
            'fees',
            [
                self::field('fees', 'Fees', 'list', true, ['required', 'array', 'min:1'], children: [
                    self::text('name', 'Fee name', true, ['required', 'string'], 'Tuition'),
                    self::field('amount', 'Amount', 'number', true, ['required', 'numeric', 'gt:0'], default: '100000'),
                    self::choice('scope', 'Applies to', true, ['required', 'in:whole_school,class'], $scope, 'whole_school'),
                    self::text('class', 'Class', false, ['required_if:scope,class']),
                    self::text('term', 'Academic term', false, ['required_unless:is_yearly,true']),
                    self::field('is_yearly', 'Yearly fee', 'choice', false, ['boolean'], default: false),
                ]),
            ],
            'Enter each fee and confirm the list.',
            'Create the fee structures.',
            [
                'name' => 'Tuition',
                'amount' => '100000',
                'scope' => 'whole_school',
                'is_yearly' => false,
            ],
            [],
            ['kind' => 'fees'],
        );
    }

    private static function whatsapp(): StepSchema
    {
        return self::make(
            'whatsapp_verify',
            [
                self::field('phone', 'WhatsApp number', 'phone', true, ['required', 'string'], default: ''),
                self::text('code', 'Verification code', false, ['digits:6']),
            ],
            'Enter the WhatsApp number and confirm the code.',
            'Send the code and link the number after it matches.',
            ['phone' => ''],
            [],
            ['kind' => 'filled_string'],
        );
    }

    /**
     * @param  list<array{value: string, label: string}>  $options
     */
    private static function plan(array $options): StepSchema
    {
        return self::make(
            'plan_selection',
            [self::choice('plan_id', 'Plan', true, ['required', 'integer', 'exists:plans,id,is_active,1'], $options, self::defaultPlanId())],
            'Choose a plan and confirm it.',
            'Save the plan on the school.',
            ['plan_id' => self::defaultPlanId()],
            $options,
            ['kind' => 'active_plan'],
        );
    }

    /**
     * @param  list<StepField>  $fields
     * @param  array<string, mixed>  $defaults
     * @param  list<array{value: string, label: string}>  $options
     * @param  array<string, mixed>  $validation
     * @param  array<string, list<string>>  $saveRules
     */
    private static function make(
        string $key,
        array $fields,
        string $schoolDoes,
        string $engineDoes,
        array $defaults,
        array $options,
        array $validation,
        array $saveRules = [],
    ): StepSchema {
        return new StepSchema(
            $key,
            $fields,
            true,
            new StepActor($schoolDoes, $engineDoes),
            $defaults,
            $options,
            $validation,
            $saveRules,
        );
    }

    /**
     * @param  list<array{value: string, label: string}>  $options
     * @param  array<string, mixed>  $defaults
     */
    private static function choiceStep(
        string $key,
        string $field,
        string $label,
        string $schoolDoes,
        string $engineDoes,
        array $options,
        array $defaults,
        mixed $fieldDefault = null,
    ): StepSchema {
        return self::make(
            $key,
            [self::choice($field, $label, true, ['required', 'string', 'in:options'], $options, $fieldDefault)],
            $schoolDoes,
            $engineDoes,
            $defaults,
            $options,
            ['kind' => 'one_of'],
        );
    }

    /**
     * @param  list<string>  $rules
     */
    private static function text(string $name, string $label, bool $required, array $rules = [], mixed $default = null): StepField
    {
        return self::field($name, $label, 'text', $required, $rules, default: $default);
    }

    /**
     * @param  list<string>  $rules
     * @param  list<array{value: string, label: string}>  $options
     */
    private static function choice(
        string $name,
        string $label,
        bool $required,
        array $rules,
        array $options,
        mixed $default = null,
    ): StepField {
        return self::field($name, $label, 'choice', $required, $rules, $options, $default);
    }

    private static function upload(string $name, string $label): StepField
    {
        return self::field(
            $name,
            $label,
            'file',
            false,
            ['file', 'max:10240'],
            extensions: OnboardingNameListExtractor::ACCEPTED_MIMES,
        );
    }

    /**
     * @param  list<string>  $rules
     * @param  list<array{value: string, label: string}>  $options
     * @param  list<string>  $extensions
     * @param  list<StepField>  $children
     */
    private static function field(
        string $name,
        string $label,
        string $type,
        bool $required,
        array $rules = [],
        array $options = [],
        mixed $default = null,
        array $extensions = [],
        array $children = [],
    ): StepField {
        return new StepField($name, $label, $type, $required, $rules, $options, $default, $extensions, $children);
    }

    /**
     * Same country list the wizard select uses, including its empty fallback.
     *
     * @return list<array{value: string, label: string}>
     */
    private static function countryOptions(): array
    {
        $names = Country::query()->orderBy('order')->orderBy('name')->pluck('name');
        if ($names->isEmpty()) {
            $names = collect(['Uganda', 'Kenya', 'Tanzania']);
        }

        return $names
            ->map(fn ($name) => ['value' => (string) $name, 'label' => (string) $name])
            ->all();
    }

    /**
     * Same three-term prefill the wizard shows (ManualOnboardingWizard::defaultTermDrafts).
     *
     * @return list<array{name: string, start: string, end: string}>
     */
    private static function termDrafts(int $year): array
    {
        return [
            ['name' => 'Term 1', 'start' => "{$year}-02-03", 'end' => "{$year}-05-02"],
            ['name' => 'Term 2', 'start' => "{$year}-05-26", 'end' => "{$year}-08-29"],
            ['name' => 'Term 3', 'start' => "{$year}-09-22", 'end' => "{$year}-12-19"],
        ];
    }

    private static function termYear(School $school): int
    {
        $saved = AcademicYear::query()
            ->where('school_id', $school->id)
            ->orderByDesc('id')
            ->first();

        if ($saved) {
            return $saved->calendarYear();
        }

        return (int) now()->year;
    }

    /**
     * Same Freemium-then-first-active choice the wizard makes.
     */
    private static function defaultPlanId(): ?int
    {
        $freemium = Plan::query()
            ->where('is_active', 1)
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['freemium'])
                    ->orWhereRaw('LOWER(display_name) = ?', ['freemium']);
            })
            ->orderBy('order')
            ->first();

        if ($freemium) {
            return (int) $freemium->id;
        }

        $first = Plan::query()->where('is_active', 1)->orderBy('order')->first();

        return $first ? (int) $first->id : null;
    }
}
