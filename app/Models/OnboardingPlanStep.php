<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One confirmed onboarding step.
 *
 * Written when a person confirms a write, by any channel: the panel's tool
 * confirmation, the wizard's step confirmation, or a checklist plan step. It
 * exists so "who confirmed what, and when" survives the request.
 */
class OnboardingPlanStep extends Model
{
    protected $fillable = [
        'school_id',
        'plan_id',
        'step',
        'actor_id',
        'preview_hash',
        'applied_counts',
        'confirmed_at',
    ];

    protected $casts = [
        'applied_counts' => 'array',
        'confirmed_at' => 'datetime',
    ];

    /**
     * @param  array<string, mixed>  $appliedCounts
     */
    public static function record(
        int $schoolId,
        string $step,
        ?int $actorId,
        string $previewHash,
        array $appliedCounts = [],
        ?int $planId = null,
    ): self {
        return self::create([
            'school_id' => $schoolId,
            'plan_id' => $planId,
            'step' => $step,
            'actor_id' => $actorId,
            'preview_hash' => $previewHash,
            'applied_counts' => $appliedCounts,
            'confirmed_at' => now(),
        ]);
    }
}
