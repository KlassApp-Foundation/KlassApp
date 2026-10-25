<?php

namespace App\Onboarding;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Onboarding\Steps\StepSchemaCatalog;
use App\Services\ClassStructureService;
use App\Services\OnboardingStepsService;
use App\Services\SchoolCategorySeeder;
use RuntimeException;

/**
 * One running summary for a school, read from saved rows and the playbook.
 *
 * The playbook's Summary line is the sentence. Tokens in that line are filled
 * from the school's saved data, so the wizard sidebar, the Toshi bar and the
 * dashboard banner say the same thing after a reload.
 */
final class OnboardingProgress
{
    public const SKIPPED_FOR_LATER = 'skipped for later';

    public const LEFT_BLANK = 'blank';

    public const ALREADY_SET_UP = 'already set up';

    /** @var array<string, string>|null */
    private static ?array $templates = null;

    /**
     * Tokens each playbook summary line must contain, in order.
     *
     * @return array<string, list<string>>
     */
    public static function summaryTokens(): array
    {
        return [
            'school_name' => ['name'],
            'student_size' => ['size'],
            'country' => ['country'],
            'curriculum' => ['curriculum'],
            'school_category' => ['category'],
            'emis' => ['code'],
            'uneb_center' => ['number'],
            'academic_year' => ['name', 'start', 'end'],
            'standards' => ['classes', 'streams'],
            'subjects' => ['count'],
            'teachers' => ['teachers'],
            'students' => ['students'],
            'terms' => ['terms', 'current'],
            'fees' => ['fees'],
            'whatsapp_verify' => ['phone'],
            'plan_selection' => ['plan'],
        ];
    }

    public static function template(string $key): string
    {
        $templates = self::templates();
        if (! isset($templates[$key]) || $templates[$key] === '') {
            throw new RuntimeException('Playbook step '.$key.' has no summary line.');
        }

        return $templates[$key];
    }

    /**
     * @return array{done: int, total: int, current_key: ?string, summary: list<string>}
     */
    public static function for(School $school, ?int $userId = null): array
    {
        $school = $school->fresh() ?? $school;
        if ($userId !== null && $userId < 1) {
            $userId = null;
        }

        $steps = OnboardingStepsService::steps($school, $userId);
        $summary = [];
        $done = 0;
        $current = null;

        foreach ($steps as $step) {
            $key = (string) $step['key'];
            if (in_array($key, StepSchemaCatalog::EXCLUDED_KEYS, true)) {
                throw new RuntimeException($key.' is not an onboarding step.');
            }

            if (! empty($step['is_complete'])) {
                $done++;
                $summary[] = self::line($key, $school, $userId);

                continue;
            }

            if ($current === null) {
                $current = $key;
            }
        }

        return [
            'done' => $done,
            'total' => count($steps),
            'current_key' => $current,
            'summary' => $summary,
        ];
    }

    private static function line(string $key, School $school, ?int $userId): string
    {
        $tokens = self::summaryTokens()[$key] ?? null;
        if ($tokens === null) {
            throw new RuntimeException('No summary tokens for '.$key.'.');
        }

        $values = self::values($key, $school, $userId);
        $replace = [];
        foreach ($tokens as $token) {
            if (! array_key_exists($token, $values)) {
                throw new RuntimeException('Summary for '.$key.' has no value for {'.$token.'}.');
            }
            $replace['{'.$token.'}'] = $values[$token];
        }

        $line = strtr(self::template($key), $replace);
        if (preg_match('/\{[a-z_]+\}/', $line) === 1) {
            throw new RuntimeException('Summary for '.$key.' still has a token: '.$line);
        }

        return $line;
    }

    /**
     * @return array<string, string>
     */
    private static function values(string $key, School $school, ?int $userId): array
    {
        return match ($key) {
            'school_name' => ['name' => trim((string) $school->name)],
            'student_size' => ['size' => trim((string) $school->student_size)],
            'country' => ['country' => trim((string) $school->registration_country)],
            'curriculum' => ['curriculum' => trim((string) $school->curriculum)],
            'school_category' => ['category' => self::category($school)],
            'emis' => ['code' => trim((string) $school->ministry_code)],
            'uneb_center' => ['number' => self::uneb($school)],
            'academic_year' => self::academicYear($school),
            'standards' => self::classes($school),
            'subjects' => ['count' => self::countPhrase(self::subjectCount($school), 'subject', 'subjects')],
            'teachers' => ['teachers' => self::people($school, 'teachers', 5, 'teacher', 'teachers')],
            'students' => ['students' => self::people($school, 'students', 6, 'student', 'students')],
            'terms' => self::terms($school),
            'fees' => ['fees' => self::fees($school)],
            'whatsapp_verify' => ['phone' => self::whatsappPhone($userId)],
            'plan_selection' => ['plan' => self::plan($school)],
            default => throw new RuntimeException('No summary values for '.$key.'.'),
        };
    }

    private static function category(School $school): string
    {
        $key = trim((string) $school->school_category);
        if ($key === '') {
            return self::ALREADY_SET_UP;
        }

        return SchoolCategorySeeder::CATEGORIES[$key] ?? $key;
    }

    private static function uneb(School $school): string
    {
        $number = trim((string) $school->uneb_center_number);

        return $number === '' ? self::LEFT_BLANK : $number;
    }

    /**
     * @return array{name: string, start: string, end: string}
     */
    private static function academicYear(School $school): array
    {
        $year = AcademicYear::query()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->first();

        $name = trim((string) ($year->name ?? ''));
        if ($name === '') {
            $name = trim((string) ($year->description ?? ''));
        }

        return [
            'name' => $name,
            'start' => $year?->start_date?->toDateString() ?? '',
            'end' => $year?->end_date?->toDateString() ?? '',
        ];
    }

    /**
     * @return array{classes: string, streams: string}
     */
    private static function classes(School $school): array
    {
        $year = AcademicYear::query()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->first();

        $classCount = 0;
        $streamCount = 0;
        if ($year !== null) {
            foreach (app(ClassStructureService::class)->structureSnapshot($school, $year) as $row) {
                $classCount++;
                $streamCount += count($row['streams'] ?? []);
            }
        }

        if ($classCount === 0) {
            $classCount = StandardLink::query()->where('school_id', $school->id)->count();
        }

        return [
            'classes' => self::countPhrase($classCount, 'class', 'classes'),
            'streams' => self::countPhrase($streamCount, 'stream', 'streams'),
        ];
    }

    private static function subjectCount(School $school): int
    {
        return Subject::query()
            ->where('school_id', $school->id)
            ->pluck('name')
            ->map(fn ($name): string => mb_strtolower(trim((string) $name)))
            ->filter(fn (string $name): bool => $name !== '')
            ->unique()
            ->count();
    }

    private static function people(School $school, string $step, int $group, string $one, string $many): string
    {
        $people = User::query()
            ->where('school_id', $school->id)
            ->where('usergroup_id', $group);
        $count = $step === 'students'
            ? $people->ByActive()->count()
            : $people->where('status', 'active')->count();

        if ($count === 0 && $step === 'teachers') {
            $count = (int) Teacherlink::query()
                ->where('school_id', $school->id)
                ->distinct()
                ->count('teacher_id');
        }

        if ($count === 0 && OnboardingStepsService::wasStepSkipped($school, $step)) {
            return self::SKIPPED_FOR_LATER;
        }

        return self::countPhrase($count, $one, $many);
    }

    /**
     * @return array{terms: string, current: string}
     */
    private static function terms(School $school): array
    {
        $terms = AcademicTerm::query()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->get()
            ->sortBy(fn (AcademicTerm $term): array => [
                substr((string) $term->getRawOriginal('starts_on'), 0, 10),
                (string) $term->name,
            ])
            ->values();

        $current = $terms->first(fn (AcademicTerm $term): bool => (string) $term->status === 'current');

        return [
            'terms' => $terms->pluck('name')->implode(', '),
            'current' => trim((string) ($current?->name ?? '')) !== '' ? (string) $current->name : 'none',
        ];
    }

    private static function fees(School $school): string
    {
        $rows = FeesCategories::query()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->get()
            ->map(fn (FeesCategories $fee): array => [
                'name' => trim((string) $fee->name),
                'amount' => self::amount($fee->amount),
            ])
            ->sortBy(fn (array $fee): array => [$fee['name'], $fee['amount']])
            ->values();

        return $rows
            ->map(fn (array $fee): string => trim($fee['name'].' '.$fee['amount']))
            ->implode(', ');
    }

    private static function whatsappPhone(?int $userId): string
    {
        if ($userId === null) {
            return '';
        }

        $phone = WhatsAppUser::query()
            ->where('user_id', $userId)
            ->whereNotNull('verified_at')
            ->value('phone');

        return trim((string) $phone);
    }

    private static function plan(School $school): string
    {
        $planId = CurrentPlan::query()
            ->where('school_id', $school->id)
            ->orderByDesc('id')
            ->value('plan_id');
        if (! $planId) {
            return '';
        }

        $plan = Plan::query()->whereKey($planId)->first();
        if ($plan === null) {
            return '';
        }

        $label = trim((string) ($plan->display_name ?: $plan->name));

        return $label !== '' ? $label : trim((string) $plan->name);
    }

    private static function amount(mixed $amount): string
    {
        $value = (float) $amount;
        if (abs($value - round($value)) < 0.00001) {
            return (string) (int) round($value);
        }

        return number_format($value, 2, '.', '');
    }

    private static function countPhrase(int $count, string $one, string $many): string
    {
        return $count.' '.($count === 1 ? $one : $many);
    }

    /**
     * @return array<string, string>
     */
    private static function templates(): array
    {
        if (self::$templates !== null) {
            return self::$templates;
        }

        $markdown = file_get_contents(base_path('docs/onboarding/playbook.md'));
        if ($markdown === false) {
            throw new RuntimeException('The onboarding playbook is missing.');
        }

        preg_match_all('/^## step: (\S+)\s*$/m', $markdown, $matches, PREG_OFFSET_CAPTURE);
        $templates = [];
        $count = count($matches[0]);

        for ($i = 0; $i < $count; $i++) {
            $key = $matches[1][$i][0];
            $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $end = $i + 1 < $count ? $matches[0][$i + 1][1] : strlen($markdown);
            $body = substr($markdown, $start, $end - $start);
            if (preg_match('/^## /m', $body, $heading, PREG_OFFSET_CAPTURE) === 1) {
                $body = substr($body, 0, $heading[0][1]);
            }
            if (preg_match('/^Summary:\s*(.+)$/m', $body, $summary) !== 1) {
                throw new RuntimeException('Playbook step '.$key.' has no summary line.');
            }
            $templates[$key] = trim($summary[1]);
        }

        self::$templates = $templates;

        return $templates;
    }
}
