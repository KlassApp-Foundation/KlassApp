<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\WhatsAppUser;

/**
 * Single canonical source of truth for onboarding steps.
 *
 * Both Toshi's checklist and the System admin UI should read from this
 * service, not maintain separate hardcoded step lists.
 */
class OnboardingStepsService
{
    /**
     * Ordered steps. School name → curriculum → academic year are near-first
     * because classes/streams/students/terms depend on year + board.
     */
    const ALL_STEPS = [
        'school_name' => [
            'label' => 'School name',
            'icon'  => '🏫',
        ],
        'curriculum' => [
            'label' => 'Board / Curriculum',
            'icon'  => '📚',
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

    /**
     * @return array<int, array{key: string, label: string, icon: string, is_complete: bool, route: ?string}>
     */
    public static function steps(School $school, ?int $userId = null): array
    {
        $result = [];
        $i = 0;
        foreach (self::ALL_STEPS as $key => $cfg) {
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
            // null / empty = not yet answered (do not treat DB default as complete)
            'curriculum' => filled($school->curriculum),
            'academic_year' => AcademicYear::where('school_id', $sid)->exists(),
            'standards'  => StandardLink::where('school_id', $sid)->exists(),
            'subjects'   => Subject::where('school_id', $sid)->exists(),
            'teachers'   => Teacherlink::where('school_id', $sid)->exists(),
            'terms'      => AcademicTerm::where('school_id', $sid)->exists(),
            'fees'       => FeesCategories::where('school_id', $sid)->exists(),
            'whatsapp_verify' => $userId && WhatsAppUser::where('user_id', $userId)->exists(),
            default      => false,
        };
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

    private static function stepRoute(string $step, School $school): ?string
    {
        return match ($step) {
            'school_name' => null,
            'curriculum' => '/admin/standard/create',
            'academic_year' => '/admin/academics',
            'standards'  => '/admin/standard/create',
            'subjects'   => '/admin/subjects',
            'teachers'   => '/admin/staff',
            'terms'      => '/admin/term',
            'fees'       => '/admin/fees',
            'whatsapp_verify' => null,
            default      => null,
        };
    }
}
