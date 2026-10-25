<?php

namespace App\Onboarding\Steps;

use App\Models\AcademicYear;
use App\Models\School;
use App\Onboarding\Country\CountryProfiles;
use App\Services\OnboardingStepsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Cause and fix text for onboarding step failures.
 *
 * Causes start from the wording the steps and the engine already show.
 * Exams and SchoolPay are not onboarding steps.
 */
final class StepIssueCatalog
{
    public static function issue(
        string $code,
        ?string $field,
        ?int $row,
        string $cause,
        string $fix,
    ): StepIssue {
        return new StepIssue($code, $field, $row, $cause, $fix);
    }

    public static function fromMessage(string $key, string $message, ?int $row = null): StepIssue
    {
        $message = trim($message);
        $code = self::codeForMessage($key, $message);
        if ($code === null) {
            $code = $key.'.rejected';
        }

        return self::issue($code, self::fieldFor($code), $row, $message, self::fixFor($code, $message));
    }

    public static function fromValidation(string $key, ValidationException $exception): StepIssue
    {
        $message = trim((string) collect($exception->errors())->flatten()->first());
        $code = self::codeForEngineFailure($key, $message, $exception) ?? $key.'.save';

        return self::issue($code, self::fieldFor($code), null, $message, self::fixFor($code, $message));
    }

    /**
     * Stricter checks the engine runs at save time. Validation-only answers
     * that already fail issues() are left to that list.
     *
     * @return list<StepIssue>
     */
    public static function saveIssues(string $key, School $school, mixed $normalized): array
    {
        if (in_array($key, StepSchemaCatalog::EXCLUDED_KEYS, true)) {
            throw new InvalidArgumentException("[{$key}] is not an onboarding step.");
        }

        if (self::needsAcademicYear($key, $normalized)
            && ! AcademicYear::query()->where('school_id', $school->id)->exists()) {
            return [self::fromMessage($key, 'Create an academic year before this step.')];
        }

        return match ($key) {
            'school_name' => self::schoolNameSaveIssues($normalized),
            'emis' => self::emisSaveIssues($school, $normalized),
            'academic_year' => self::academicYearSaveIssues($normalized),
            'standards' => self::standardsSaveIssues($normalized),
            'subjects' => self::subjectsSaveIssues($school, $normalized),
            'terms' => self::termsSaveIssues($normalized),
            default => [],
        };
    }

    public static function whatsappVerification(string $serviceMessage): StepIssue
    {
        $message = trim($serviceMessage);
        $code = 'whatsapp.code';
        foreach (self::whatsappVerificationRules() as $rule) {
            if (self::ruleMatches($message, $rule)) {
                $code = $rule['code'];
                break;
            }
        }

        return self::issue($code, 'code', null, $message, self::fixFor($code, $message));
    }

    /**
     * Issue codes a wizard step can return, keyed by the step.
     *
     * whatsapp.* belongs to whatsapp_verify and plan.* belongs to plan_selection.
     * {key}.skip, {key}.list and {key}.year are expanded for the steps in
     * genericCodeRule(). {key}.rejected and {key}.save are fallbacks: they are
     * not listed here, and the playbook states the rule for them.
     *
     * @return array<string, list<string>>
     */
    public static function codes(): array
    {
        $buckets = [];
        $add = function (string $code) use (&$buckets): void {
            if (str_contains($code, '{key}')) {
                return;
            }
            $step = self::stepKeyForCode($code);
            $buckets[$step][] = $code;
        };

        foreach (self::exactMessageCodes() as $template) {
            $add($template);
        }
        $add('academic_year.invalid');
        foreach (self::patternRules() as $rule) {
            $add($rule['code']);
        }
        foreach (self::whatsappVerificationRules() as $rule) {
            $add($rule['code']);
        }
        $add('whatsapp.code');
        foreach (array_keys(self::fixTexts()) as $code) {
            $add($code);
        }

        $rule = self::genericCodeRule();
        foreach ($rule['steps'] as $step) {
            foreach ($rule['suffixes'] as $suffix) {
                $add($step.'.'.$suffix);
            }
        }

        $ordered = [];
        foreach ($buckets as $step => $codes) {
            $ordered[$step] = array_values(array_unique($codes));
        }

        return $ordered;
    }

    /**
     * Step that owns an issue code. whatsapp.* and plan.* are the exceptions
     * to "the prefix is the step key".
     */
    public static function stepKeyForCode(string $code): string
    {
        $prefix = strstr($code, '.', true);
        if (! is_string($prefix) || $prefix === '') {
            return $code;
        }

        return self::prefixSteps()[$prefix] ?? $prefix;
    }

    /**
     * @return array<string, string>
     */
    public static function prefixSteps(): array
    {
        return [
            'whatsapp' => 'whatsapp_verify',
            'plan' => 'plan_selection',
        ];
    }

    /**
     * Generated codes. suffixes apply only to steps. fallback applies to every step.
     *
     * @return array{steps: list<string>, suffixes: list<string>, fallback: list<string>}
     */
    public static function genericCodeRule(): array
    {
        return [
            'steps' => ['standards', 'subjects', 'teachers', 'students', 'terms'],
            'suffixes' => ['skip', 'list', 'year'],
            'fallback' => ['rejected', 'save'],
        ];
    }

    public static function academicYearCause(): string
    {
        $suggested = (string) date('Y');
        $following = (string) ((int) $suggested + 1);

        return "Enter an academic year such as {$suggested} or {$suggested}/{$following}, or reply yes to use {$suggested}.";
    }

    public static function feeAmountCause(string $name): string
    {
        return "Enter an amount for {$name}. For example: {$name} 250000.";
    }

    /**
     * @return list<StepIssue>
     */
    private static function schoolNameSaveIssues(mixed $normalized): array
    {
        if (! is_string($normalized)) {
            return [];
        }

        $name = trim($normalized);
        if ($name === '') {
            return [];
        }

        if (mb_strlen($name) < 3) {
            return [self::fromMessage('school_name', 'The school name must be at least 3 characters.')];
        }

        if (OnboardingStepsService::isPlaceholderSchoolName($name)) {
            return [self::issue(
                'school_name.placeholder',
                'name',
                null,
                'Enter your real school name.',
                self::fixFor('school_name.placeholder', 'Enter your real school name.'),
            )];
        }

        return [];
    }

    /**
     * @return list<StepIssue>
     */
    private static function emisSaveIssues(School $school, mixed $normalized): array
    {
        if (! CountryProfiles::requiresMinistryCode($school->registration_country)) {
            return [];
        }

        if (! is_string($normalized)) {
            return [];
        }

        $code = trim($normalized);
        if ($code === '') {
            return [];
        }

        if (mb_strlen($code) > 20) {
            return [self::fromMessage('emis', 'An EMIS / ministry code can be at most 20 characters.')];
        }

        $taken = School::query()
            ->where('ministry_code', $code)
            ->whereKeyNot($school->getKey())
            ->exists();
        if ($taken) {
            return [self::fromMessage(
                'emis',
                'That EMIS / ministry code is already used by another school. Check the code and try again.',
            )];
        }

        return [];
    }

    /**
     * @return list<StepIssue>
     */
    private static function academicYearSaveIssues(mixed $normalized): array
    {
        if (! is_array($normalized)) {
            return [];
        }

        $start = $normalized['start'] ?? null;
        $end = $normalized['end'] ?? null;
        $hasStart = $start !== null && trim((string) $start) !== '';
        $hasEnd = $end !== null && trim((string) $end) !== '';

        if ($hasStart !== $hasEnd) {
            return [self::fromMessage('academic_year', 'Provide both start and end dates.')];
        }

        if (! $hasStart) {
            return [];
        }

        $validator = Validator::make(
            ['academicYearStart' => $start, 'academicYearEnd' => $end],
            [
                'academicYearStart' => 'required|date',
                'academicYearEnd' => 'required|date|after:academicYearStart',
            ],
            [
                'academicYearEnd.after' => 'The academic year end date must be after the start date.',
            ]
        );
        if ($validator->fails()) {
            $message = (string) collect($validator->errors()->all())->first();

            return [self::fromMessage('academic_year', $message)];
        }

        return [];
    }

    /**
     * @return list<StepIssue>
     */
    private static function standardsSaveIssues(mixed $normalized): array
    {
        if (! is_array($normalized)) {
            return [];
        }

        if ($normalized === []) {
            return [self::fromMessage('standards', 'Add at least one class.')];
        }

        $seen = [];
        foreach ($normalized as $index => $class) {
            $name = trim((string) (is_array($class) ? ($class['name'] ?? '') : ''));
            if ($name === '') {
                return [self::fromMessage('standards', 'Each class must have a name.', is_int($index) ? $index : null)];
            }
            if (in_array($name, $seen, true)) {
                return [self::fromMessage('standards', "Duplicate class name: {$name}.", is_int($index) ? $index : null)];
            }
            $seen[] = $name;
        }

        return [];
    }

    /**
     * @return list<StepIssue>
     */
    private static function subjectsSaveIssues(School $school, mixed $normalized): array
    {
        if (! is_array($normalized)) {
            return [];
        }

        if ($normalized === []) {
            return [self::fromMessage('subjects', 'Add at least one subject.')];
        }

        $year = AcademicYear::query()->where('school_id', $school->id)->first();
        foreach ($normalized as $className => $subjectNames) {
            $className = trim((string) $className);
            if ($className === '') {
                return [self::fromMessage('subjects', 'Class name cannot be empty.')];
            }
            if (! is_array($subjectNames) || $subjectNames === []) {
                return [self::fromMessage('subjects', "Class '{$className}' must have at least one subject.")];
            }
            foreach ($subjectNames as $index => $subjectName) {
                if (trim((string) $subjectName) === '') {
                    return [self::fromMessage('subjects', 'Subject name cannot be empty.', is_int($index) ? $index : null)];
                }
            }
            if ($year && app(\App\Services\OnboardingEngine::class)->standardLinksForClass($school, $year, $className)->isEmpty()) {
                return [self::fromMessage('subjects', "Class '{$className}' does not exist. Add it on the classes step first.")];
            }
        }

        return [];
    }

    /**
     * @return list<StepIssue>
     */
    private static function termsSaveIssues(mixed $normalized): array
    {
        if (! is_array($normalized)) {
            return [];
        }

        if ($normalized === []) {
            return [self::fromMessage('terms', 'Add at least one term.')];
        }

        foreach ($normalized as $index => $term) {
            if (! is_array($term)) {
                return [self::fromMessage('terms', 'Term name cannot be empty.', is_int($index) ? $index : null)];
            }
            $name = trim((string) ($term['name'] ?? ''));
            if ($name === '') {
                return [self::fromMessage('terms', 'Term name cannot be empty.', is_int($index) ? $index : null)];
            }

            $start = $term['start'] ?? null;
            $end = $term['end'] ?? null;
            $hasStart = $start !== null && trim((string) $start) !== '';
            $hasEnd = $end !== null && trim((string) $end) !== '';
            if ($hasStart !== $hasEnd) {
                return [self::fromMessage(
                    'terms',
                    "Term '{$name}': provide both start and end dates, or neither.",
                    is_int($index) ? $index : null,
                )];
            }
            if ($hasStart && $hasEnd) {
                try {
                    $startParsed = Carbon::parse($start);
                    $endParsed = Carbon::parse($end);
                } catch (\Throwable) {
                    return [self::fromMessage(
                        'terms',
                        "Term '{$name}': end date must be after start date.",
                        is_int($index) ? $index : null,
                    )];
                }
                if ($endParsed->lte($startParsed)) {
                    return [self::fromMessage(
                        'terms',
                        "Term '{$name}': end date must be after start date.",
                        is_int($index) ? $index : null,
                    )];
                }
            }
        }

        return [];
    }

    private static function needsAcademicYear(string $key, mixed $normalized): bool
    {
        if (! in_array($key, ['standards', 'subjects', 'teachers', 'students', 'terms'], true)) {
            return false;
        }
        if ($normalized === 'skip' || $normalized === 'confirm_seeded') {
            return false;
        }
        if (! is_array($normalized)) {
            return false;
        }
        if (in_array($key, ['teachers', 'students'], true) && $normalized === []) {
            return false;
        }

        return true;
    }

    private static function codeForEngineFailure(string $key, string $message, ValidationException $exception): ?string
    {
        if ($key === 'school_name' && $message === 'Enter your real school name.') {
            $failed = $exception->validator->failed();
            $rules = array_keys($failed['name'] ?? []);
            if (in_array('Required', $rules, true)) {
                return 'school_name.required';
            }

            return 'school_name.placeholder';
        }

        return self::codeForMessage($key, $message);
    }

    private static function codeForMessage(string $key, string $message): ?string
    {
        $exact = self::exactMessageCodes();
        if (isset($exact[$message])) {
            return str_replace('{key}', $key, $exact[$message]);
        }

        if ($message === self::academicYearCause()) {
            return 'academic_year.invalid';
        }

        foreach (self::patternRules() as $rule) {
            if (self::ruleMatches($message, $rule)) {
                return $rule['code'];
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private static function exactMessageCodes(): array
    {
        return [
            'Enter your real school name.' => 'school_name.required',
            'The school name must be at least 3 characters.' => 'school_name.min',
            'Choose one of the school size options.' => 'student_size.choice',
            'Enter a country.' => 'country.required',
            'Choose a curriculum option.' => 'curriculum.choice',
            'Choose a school category.' => 'school_category.choice',
            'Enter your EMIS / ministry code.' => 'emis.required',
            'An EMIS / ministry code can be at most 20 characters.' => 'emis.max',
            'That EMIS / ministry code is already used by another school. Check the code and try again.' => 'emis.unique',
            'Enter a UNEB centre number or skip.' => 'uneb_center.invalid',
            'Provide both start and end dates.' => 'academic_year.dates',
            'The academic year end date must be after the start date.' => 'academic_year.end',
            'Enter an academic year.' => 'academic_year.required',
            'This step cannot be skipped.' => '{key}.skip',
            'Add or confirm your classes first.' => 'standards.confirm',
            'Add or confirm subjects first.' => 'subjects.confirm',
            'Provide a valid list for this step.' => '{key}.list',
            'Add at least one class.' => 'standards.min',
            'Each class must have a name.' => 'standards.name',
            'Add at least one subject.' => 'subjects.min',
            'Class name cannot be empty.' => 'subjects.class_name',
            'Subject name cannot be empty.' => 'subjects.name',
            'Add at least one term.' => 'terms.min',
            'Term name cannot be empty.' => 'terms.name',
            'Add at least one fee structure.' => 'fees.required',
            'Enter a fee name and an amount.' => 'fees.name',
            'Enter a WhatsApp phone number.' => 'whatsapp.phone',
            'A logged-in user is required to link WhatsApp.' => 'whatsapp.user',
            'Enter your WhatsApp number first. We will send a verification code.' => 'whatsapp.phone_first',
            'Enter your WhatsApp number. We send a verification code and link it only after you enter that code.' => 'whatsapp.required',
            'Choose a plan.' => 'plan.required',
            'That plan is not available.' => 'plan.unavailable',
            'Add a teacher with a name and an email, or type skip.' => 'teachers.line',
            'Add a student with a name and a class, or type skip.' => 'students.line',
            'Create an academic year before this step.' => '{key}.year',
            "We couldn't send the verification code. Please try again." => 'whatsapp.send_failed',
            'Verify the code sent to your WhatsApp before continuing.' => 'whatsapp.code_missing',
        ];
    }

    /**
     * @return list<array{code: string, starts_with?: string, contains?: string, contains_any?: list<string>}>
     */
    private static function patternRules(): array
    {
        return [
            ['code' => 'fees.amount', 'starts_with' => 'Enter an amount for '],
            ['code' => 'standards.duplicate', 'starts_with' => 'Duplicate class name:'],
            ['code' => 'standards.stream_duplicate', 'contains' => 'is listed more than once.'],
            ['code' => 'standards.stream_missing', 'contains' => "doesn't exist."],
            ['code' => 'standards.stream_students', 'contains' => 'still has students.'],
            ['code' => 'subjects.min_for_class', 'starts_with' => "Class '", 'contains' => 'must have at least one subject'],
            ['code' => 'subjects.missing_class', 'contains' => 'does not exist. Add it on the classes step first.'],
            ['code' => 'terms.dates', 'contains' => 'provide both start and end dates, or neither'],
            ['code' => 'terms.end', 'contains' => 'end date must be after start date'],
            ['code' => 'whatsapp.taken', 'starts_with' => 'WhatsApp number ', 'contains' => 'already registered'],
            ['code' => 'academic_year.date', 'contains_any' => ['must be a valid date', 'is not a valid date']],
        ];
    }

    /**
     * @return list<array{code: string, contains?: string, contains_any?: list<string>}>
     */
    private static function whatsappVerificationRules(): array
    {
        return [
            ['code' => 'whatsapp.code_mismatch', 'contains' => "doesn't match"],
            ['code' => 'whatsapp.code_expired', 'contains' => 'expired'],
            ['code' => 'whatsapp.code_invalid', 'contains_any' => ['no longer valid', 'Too many incorrect']],
            ['code' => 'whatsapp.code_phone', 'contains' => 'Phone number changed'],
            ['code' => 'whatsapp.code_missing', 'contains' => 'Send a verification code first'],
        ];
    }

    /**
     * @param  array{code?: string, starts_with?: string, contains?: string, contains_any?: list<string>}  $rule
     */
    private static function ruleMatches(string $message, array $rule): bool
    {
        if (isset($rule['starts_with']) && ! str_starts_with($message, $rule['starts_with'])) {
            return false;
        }
        if (isset($rule['contains']) && ! str_contains($message, $rule['contains'])) {
            return false;
        }
        if (isset($rule['contains_any'])) {
            foreach ($rule['contains_any'] as $needle) {
                if (str_contains($message, $needle)) {
                    return ! isset($rule['starts_with']) || str_starts_with($message, $rule['starts_with']);
                }
            }

            return false;
        }

        return true;
    }

    private static function fieldFor(string $code): ?string
    {
        return match (true) {
            str_starts_with($code, 'school_name') => 'name',
            str_starts_with($code, 'student_size') => 'student_size',
            str_starts_with($code, 'country') => 'country',
            str_starts_with($code, 'curriculum') => 'curriculum',
            str_starts_with($code, 'school_category') => 'school_category',
            str_starts_with($code, 'emis') => 'ministry_code',
            str_starts_with($code, 'uneb_center') => 'uneb_center_number',
            str_starts_with($code, 'academic_year.end'),
            str_starts_with($code, 'academic_year.date') => 'end',
            $code === 'academic_year.dates' => 'dates',
            str_starts_with($code, 'academic_year') => 'name',
            str_starts_with($code, 'standards') => 'class_name',
            str_starts_with($code, 'subjects') => 'subject_name',
            str_starts_with($code, 'teachers') => 'teacher_name',
            str_starts_with($code, 'students') => 'student_name',
            str_starts_with($code, 'terms') => 'terms',
            $code === 'fees.name' => 'name',
            $code === 'fees.amount' => 'amount',
            str_starts_with($code, 'fees') => 'fees',
            str_starts_with($code, 'whatsapp.code') => 'code',
            str_starts_with($code, 'whatsapp') => 'phone',
            str_starts_with($code, 'plan') => 'plan_id',
            default => null,
        };
    }

    private static function fixFor(string $code, string $message): string
    {
        return self::fixTexts()[$code] ?? self::fallbackFix($message);
    }

    /**
     * @return array<string, string>
     */
    private static function fixTexts(): array
    {
        $yearText = 'Enter a four-digit year, a span such as 2026/2027, or reply yes to use the suggested year.';
        $skip = 'Enter the information for this step. It cannot be skipped.';
        $list = 'Send the list for this step, or confirm what is already saved.';
        $needYear = 'Set the academic year, then return to this step.';
        $phone = 'Enter the WhatsApp number that should receive the verification code.';
        $resend = 'Reply resend for a new code, then enter that 6-digit code.';

        return [
            'school_name.required' => 'Type the school\'s real name.',
            'school_name.min' => 'Enter the full school name, at least 3 characters.',
            'school_name.placeholder' => 'Replace the placeholder with the school\'s real name.',
            'student_size.choice' => 'Choose Up to 500, Up to 1,000, or More than 1,000.',
            'country.required' => 'Enter the country name, at least 2 characters.',
            'curriculum.choice' => 'Choose UNEB, Cambridge, Montessori, or Other.',
            'school_category.choice' => 'Choose the kind of school from the list.',
            'emis.required' => 'Enter the EMIS or ministry code for this school.',
            'emis.max' => 'Shorten the code to 20 characters or fewer.',
            'emis.unique' => 'Enter the code that belongs to this school.',
            'uneb_center.invalid' => 'Enter the centre number, or skip this step.',
            'academic_year.invalid' => $yearText,
            'academic_year.required' => $yearText,
            'academic_year.dates' => 'Enter both the start date and the end date, or leave both blank.',
            'academic_year.end' => 'Move the end date to a day after the start date.',
            'academic_year.date' => 'Enter the start and end as real dates, with the end after the start.',
            'standards.skip' => $skip,
            'subjects.skip' => $skip,
            'teachers.skip' => $skip,
            'students.skip' => $skip,
            'terms.skip' => $skip,
            'standards.confirm' => 'Add at least one class, or confirm once classes are already on the school.',
            'subjects.confirm' => 'Add at least one subject, or confirm once subjects are already on the school.',
            'standards.list' => $list,
            'subjects.list' => $list,
            'teachers.list' => $list,
            'students.list' => $list,
            'terms.list' => $list,
            'standards.min' => 'Add at least one class with a name.',
            'standards.name' => 'Give each class a name.',
            'standards.duplicate' => 'Use each class name only once in the list.',
            'standards.stream_duplicate' => 'List each stream once for that class.',
            'standards.stream_missing' => 'Use a stream that is already on that class, or add it before referring to it.',
            'standards.stream_students' => 'Move those students to another class or stream, then remove it.',
            'standards.year' => $needYear,
            'subjects.year' => $needYear,
            'teachers.year' => $needYear,
            'students.year' => $needYear,
            'terms.year' => $needYear,
            'subjects.min' => 'Add at least one subject.',
            'subjects.class_name' => 'Name the class the subjects belong to.',
            'subjects.min_for_class' => 'Add at least one subject for that class.',
            'subjects.name' => 'Give each subject a name.',
            'subjects.missing_class' => 'Add the class first, then attach subjects to it.',
            'teachers.line' => 'Add the teacher with a name and an email, or type skip to do this later.',
            'students.line' => 'Add the student with a name and a class, or type skip to do this later.',
            'terms.min' => 'Add at least one term with a name, or use the standard three terms.',
            'terms.name' => 'Give the term a name, such as Term 1.',
            'terms.dates' => 'Enter both dates for that term, or leave both blank.',
            'terms.end' => 'Move the end date to a day after the term starts.',
            'fees.required' => 'Add a fee with a name and an amount, for example Tuition 250000.',
            'fees.name' => 'Enter the fee\'s name and an amount greater than zero.',
            'fees.amount' => 'Enter a number greater than zero for that fee, for example Tuition 250000.',
            'whatsapp.phone' => $phone,
            'whatsapp.phone_first' => $phone,
            'whatsapp.required' => $phone,
            'whatsapp.user' => 'Sign in, then enter the WhatsApp number.',
            'whatsapp.code_mismatch' => 'Enter the 6-digit code from the WhatsApp message, or reply resend for a new code.',
            'whatsapp.code_expired' => $resend,
            'whatsapp.code_invalid' => $resend,
            'whatsapp.code_missing' => $resend,
            'whatsapp.code' => $resend,
            'whatsapp.code_phone' => 'Send a new code to the current number, then enter that code.',
            'whatsapp.taken' => 'Enter a different WhatsApp number that is not already registered.',
            'whatsapp.send_failed' => 'Check the number and reply retry, or resend for a new code.',
            'plan.required' => 'Choose one of the plans in the list.',
            'plan.id_type' => 'Choose a plan from the list. The plan is saved as its number, not as text.',
            'plan.unavailable' => 'Choose a plan that is currently offered.',
        ];
    }

    private static function fallbackFix(string $message): string
    {
        $message = trim($message);

        return $message !== ''
            ? 'Correct the value and try again.'
            : 'Check this answer and try again.';
    }
}
