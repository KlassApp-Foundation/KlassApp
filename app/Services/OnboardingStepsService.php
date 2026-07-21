<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Standard;
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
     * All steps in order, with their labels and completion checks.
     * 'curriculum' is step 0 — everything else depends on it.
     */
    const ALL_STEPS = [
        'curriculum' => [
            'label' => 'Board / Curriculum',
            'icon'  => '📚',
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
     * Get the full ordered list of onboarding steps with completion status.
     *
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

    /**
     * Check whether a single step is complete.
     */
    public static function isStepComplete(string $step, School $school, ?int $userId = null): bool
    {
        $sid = $school->id;

        return match ($step) {
            'curriculum' => !empty($school->curriculum),
            'standards'  => Standard::where('school_id', $sid)->exists(),
            'subjects'   => Subject::where('school_id', $sid)->exists(),
            'teachers'   => Teacherlink::where('school_id', $sid)->exists(),
            'terms'      => AcademicTerm::where('school_id', $sid)->exists(),
            'fees'       => FeesCategories::where('school_id', $sid)->exists(),
            'whatsapp_verify' => $userId && WhatsAppUser::where('user_id', $userId)->exists(),
            default      => false,
        };
    }

    /**
     * Get the first incomplete step, or null if all are complete.
     *
     * @return ?array{key: string, label: string, icon: string, is_complete: bool, route: ?string}
     */
    public static function nextIncompleteStep(School $school, ?int $userId = null): ?array
    {
        foreach (self::steps($school, $userId) as $step) {
            if (!$step['is_complete']) {
                return $step;
            }
        }
        return null;
    }

    /**
     * Get the list of incomplete step keys (for the checklist message).
     */
    public static function incompleteSteps(School $school, ?int $userId = null): array
    {
        return array_values(array_filter(
            self::steps($school, $userId),
            fn($s) => !$s['is_complete']
        ));
    }

    /**
     * Returns true if any onboarding step is incomplete.
     */
    public static function hasIncompleteSteps(School $school, ?int $userId = null): bool
    {
        return self::nextIncompleteStep($school, $userId) !== null;
    }

    /**
     * Admin route for each step (null if no dedicated page).
     */
    private static function stepRoute(string $step, School $school): ?string
    {
        return match ($step) {
            'curriculum' => '/admin/standard/create',
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
