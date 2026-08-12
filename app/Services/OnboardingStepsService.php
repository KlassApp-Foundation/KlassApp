<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\WhatsAppUser;
use Illuminate\Support\Facades\Schema;

/**
 * Single canonical source of truth for onboarding steps.
 *
 * Both Toshi's checklist and the System admin UI should read from this
 * service, not maintain separate hardcoded step lists.
 */
class OnboardingStepsService
{
    /**
     * Ordered steps. Country comes before curriculum; the board/curriculum
     * choice then leads into the school-category smart-default step. EMIS /
     * UNEB center sit before academic year. Plan selection is last.
     */
    const ALL_STEPS = [
        'school_name' => [
            'label' => 'School name',
            'icon'  => '🏫',
        ],
        'country' => [
            'label' => 'Country',
            'icon'  => '🌍',
        ],
        'curriculum' => [
            'label' => 'Board / Curriculum',
            'icon'  => '📚',
        ],
        'school_category' => [
            'label' => 'School category',
            'icon'  => '🏫',
        ],
        'emis' => [
            'label' => 'EMIS / Ministry code',
            'icon'  => '🏷️',
        ],
        'uneb_center' => [
            'label' => 'UNEB centre number',
            'icon'  => '🎓',
        ],
        'academic_year' => [
            'label' => 'Academic year',
            'icon'  => '📆',
        ],
        'standards' => [
            'label' => 'Classes',
            'icon'  => '📚',
        ],
        'subjects' => [
            'label' => 'Subjects',
            'icon'  => '📖',
        ],
        'teachers' => [
            'label' => 'Teachers',
            'icon'  => '👨‍🏫',
        ],
        'students' => [
            'label' => 'Students',
            'icon'  => '🎒',
        ],
        'terms' => [
            'label' => 'Academic terms',
            'icon'  => '📅',
        ],
        'fees' => [
            'label' => 'Fee structures',
            'icon'  => '💵',
        ],
        'whatsapp_verify' => [
            'label' => 'WhatsApp verification',
            'icon'  => '📱',
        ],
        'plan_selection' => [
            'label' => 'Plan selection',
            'icon'  => '💳',
        ],
    ];

    /**
     * Optional in Toshi's $mandatorySteps — may be skipped without blocking finish.
     * Still shown on Completing Setup until data exists.
     */
    public const OPTIONAL_STEPS = [
        'teachers',
        'students',
    ];

    /**
     * Placeholder names created at SaaS signup: "{FirstName}'s School" (+ optional -N).
     */
    public static function isPlaceholderSchoolName(?string $name): bool
    {
        if ($name === null || trim($name) === '') {
            return true;
        }

        return (bool) preg_match("/^.+'s School(-\d+)?$/u", trim($name));
    }

    public static function isUganda(?string $country): bool
    {
        if ($country === null || trim($country) === '') {
            return false;
        }

        return strcasecmp(trim($country), 'Uganda') === 0
            || strcasecmp(trim($country), 'UG') === 0;
    }

    public static function isUnebCurriculum(?string $curriculum): bool
    {
        return strcasecmp(trim((string) $curriculum), 'uneb') === 0;
    }

    /**
     * Steps that apply to this school right now (conditionals filtered out).
     *
     * @return array<string, array{label: string, icon: string}>
     */
    public static function applicableSteps(School $school): array
    {
        $steps = self::ALL_STEPS;

        if (! filled($school->registration_country) || ! self::isUganda($school->registration_country)) {
            unset($steps['emis']);
        }

        if (! self::isUnebCurriculum($school->curriculum)) {
            unset($steps['uneb_center']);
            unset($steps['school_category']);
        }

        return $steps;
    }

    /**
     * @return array<int, array{key: string, label: string, icon: string, is_complete: bool, route: ?string}>
     */
    public static function steps(School $school, ?int $userId = null): array
    {
        $result = [];
        $i = 0;
        foreach (self::applicableSteps($school) as $key => $cfg) {
            $result[$i] = [
                'key'         => $key,
                'label'       => $cfg['label'],
                'icon'        => $cfg['icon'],
                'is_complete' => self::isStepComplete($key, $school, $userId),
                'route'       => self::stepRoute($key, $school),
            ];
            $i++;
        }

        return $result;
    }

    public static function isStepComplete(string $step, School $school, ?int $userId = null): bool
    {
        $sid = $school->id;

        return match ($step) {
            'school_name' => ! self::isPlaceholderSchoolName($school->name),
            'curriculum' => filled($school->curriculum),
            'country' => filled($school->registration_country),
            'school_category' => filled($school->school_category)
                // Fallback: schools onboarded before this step existed (or via
                // Toshi) already have standards — never block them on it.
                || StandardLink::where('school_id', $sid)->exists(),
            'emis' => self::isEmisComplete($school),
            'uneb_center' => self::isUnebCenterComplete($school),
            'academic_year' => AcademicYear::where('school_id', $sid)->exists(),
            'standards'  => StandardLink::where('school_id', $sid)->exists(),
            'subjects'   => Subject::where('school_id', $sid)->exists(),
            'teachers'   => Teacherlink::where('school_id', $sid)->exists(),
            'students'   => User::query()
                ->where('school_id', $sid)
                ->where('usergroup_id', 6)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'exit');
                })
                ->exists(),
            'terms'      => AcademicTerm::where('school_id', $sid)->exists(),
            'fees'       => FeesCategories::where('school_id', $sid)->exists(),
            'whatsapp_verify' => $userId && WhatsAppUser::where('user_id', $userId)->exists(),
            'plan_selection' => CurrentPlan::where('school_id', $sid)->exists(),
            default      => false,
        };
    }

    /**
     * Uganda requires ministry_code; other countries skip this step.
     */
    public static function isEmisComplete(School $school): bool
    {
        if (! filled($school->registration_country)) {
            return false;
        }

        if (! self::isUganda($school->registration_country)) {
            return true;
        }

        return filled($school->ministry_code);
    }

    /**
     * Optional when curriculum is UNEB. Never blocks non-UNEB schools.
     * null = not asked yet; '' or a value = asked (skip or filled).
     */
    public static function isUnebCenterComplete(School $school): bool
    {
        if (! self::isUnebCurriculum($school->curriculum)) {
            return true;
        }

        if (! Schema::hasColumn('schools', 'uneb_center_number')) {
            return true;
        }

        // null means never asked; any string (including '') means handled.
        return $school->uneb_center_number !== null;
    }

    /**
     * @return ?array{key: string, label: string, icon: string, is_complete: bool, route: ?string}
     */
    public static function nextIncompleteStep(School $school, ?int $userId = null): ?array
    {
        foreach (self::steps($school, $userId) as $step) {
            if (! $step['is_complete']) {
                return $step;
            }
        }

        return null;
    }

    public static function incompleteSteps(School $school, ?int $userId = null): array
    {
        return array_values(array_filter(
            self::steps($school, $userId),
            fn ($s) => ! $s['is_complete']
        ));
    }

    public static function hasIncompleteSteps(School $school, ?int $userId = null): bool
    {
        return self::nextIncompleteStep($school, $userId) !== null;
    }

    /**
     * Incomplete steps that block wizard finish / plan confirmation.
     * Teachers & students are optional (match Toshi $mandatorySteps).
     */
    public static function hasBlockingIncompleteSteps(School $school, ?int $userId = null): bool
    {
        return self::nextBlockingIncompleteStep($school, $userId) !== null;
    }

    /**
     * @return ?array{key: string, label: string, icon: string, is_complete: bool, route: ?string}
     */
    public static function nextBlockingIncompleteStep(School $school, ?int $userId = null): ?array
    {
        foreach (self::steps($school, $userId) as $step) {
            if ($step['is_complete']) {
                continue;
            }
            if (in_array($step['key'], self::OPTIONAL_STEPS, true)) {
                continue;
            }

            return $step;
        }

        return null;
    }

    /**
     * Active students for plan suggestion (excludes exited).
     */
    public static function countActiveStudents(int $schoolId): int
    {
        return User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '!=', 'exit');
            })
            ->count();
    }

    /**
     * Suggest Freemium / Growth / Premium from plans.no_of_students.
     * Picks the smallest tier that can hold $studentCount (0 = unlimited).
     */
    public static function suggestPlanForStudentCount(int $studentCount): ?Plan
    {
        $plans = Plan::query()
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) in (?, ?, ?)', ['freemium', 'growth', 'premium'])
                    ->orWhereRaw('LOWER(display_name) in (?, ?, ?)', ['freemium', 'growth', 'premium']);
            })
            ->orderBy('order')
            ->get();

        if ($plans->isEmpty()) {
            return Plan::query()->where('is_active', 1)->orderBy('order')->first();
        }

        $sorted = $plans->sortBy(function (Plan $plan) {
            $limit = (int) ($plan->no_of_students ?? 0);

            return $limit === 0 ? PHP_INT_MAX : $limit;
        })->values();

        foreach ($sorted as $plan) {
            $limit = (int) ($plan->no_of_students ?? 0);
            if ($limit === 0 || $limit >= $studentCount) {
                return $plan;
            }
        }

        return $sorted->last();
    }

    /**
     * Persist registration country (+ country_id when a matching countries row exists).
     * Mirrors historical RegisterController / CreateSchool persistence (no student_size).
     */
    public static function persistCountry(School $school, string $countryName): void
    {
        $countryName = trim($countryName);
        $school->registration_country = $countryName;

        if (Schema::hasColumn('schools', 'country_id')) {
            $country = Country::query()
                ->where(function ($q) use ($countryName) {
                    $q->whereRaw('LOWER(name) = ?', [strtolower($countryName)])
                        ->orWhereRaw('LOWER(short_name) = ?', [strtolower($countryName)]);
                })
                ->first();
            if ($country) {
                $school->country_id = $country->id;
            }
        }

        $school->save();
    }

    /**
     * Where a school admin can complete each step by hand (manual path).
     *
     * Every route here is a real, registered admin route (verified against
     * `php artisan route:list`). School identity / curriculum / country / EMIS /
     * UNEB centre are all editable on the School Details page, so those steps
     * point there. Content steps point at their dedicated add/create pages.
     */
    private static function stepRoute(string $step, School $school): ?string
    {
        return match ($step) {
            'school_name' => '/admin/schooldetails',
            'curriculum' => '/admin/schooldetails',
            'country' => '/admin/schooldetails',
            'school_category' => '/admin/schooldetails',
            'emis' => '/admin/schooldetails',
            'uneb_center' => '/admin/schooldetails',
            'academic_year' => '/admin/academics',
            'standards'  => '/admin/standard/create',
            'subjects'   => '/admin/subjects',
            'teachers'   => '/admin/teacher/add',
            'students'   => '/admin/student/add',
            'terms'      => '/admin/academic-term/create',
            'fees'       => '/admin/fees-categories/create',
            'whatsapp_verify' => '/admin/whatsapp/phone',
            'plan_selection' => '/admin/subscriptions',
            default      => null,
        };
    }
}
