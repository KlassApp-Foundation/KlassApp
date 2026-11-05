<?php

namespace App\Services;

use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\User;

/**
 * One source of truth for the teacher and student limits on a school's plan.
 *
 * A limit of 0 (or no CurrentPlan) means unlimited. "Early adopter" is stored
 * with both limits at 0, so it never blocks a founding school.
 */
class PlanLimitService
{
    public const TYPES = [
        'teachers' => [
            'usergroup_id' => 5,
            'plan_column' => 'no_of_users',
            'label' => 'teachers',
            'singular' => 'teacher',
        ],
        'students' => [
            'usergroup_id' => 6,
            'plan_column' => 'no_of_students',
            'label' => 'students',
            'singular' => 'student',
        ],
        'admins' => [
            'usergroup_id' => 3,
            'plan_column' => 'no_of_users',
            'label' => 'administrators',
            'singular' => 'administrator',
        ],
    ];

    public function plan(int $schoolId): ?Plan
    {
        $current = CurrentPlan::query()
            ->with('plan')
            ->where('school_id', $schoolId)
            ->first();

        return $current?->plan;
    }

    /**
     * The plan's limit for this type, or null when unlimited.
     */
    public function limit(int $schoolId, string $type): ?int
    {
        $config = self::TYPES[$type] ?? null;
        if ($config === null) {
            return null;
        }

        $plan = $this->plan($schoolId);
        if ($plan === null) {
            return null;
        }

        $limit = (int) ($plan->{$config['plan_column']} ?? 0);

        return $limit > 0 ? $limit : null;
    }

    public function count(int $schoolId, string $type): int
    {
        $config = self::TYPES[$type] ?? null;
        if ($config === null) {
            return 0;
        }

        return User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', $config['usergroup_id'])
            ->count();
    }

    /**
     * Everything a caller needs to allow, block, or partially import.
     *
     * @return array{
     *     plan: ?Plan,
     *     plan_name: ?string,
     *     limit: ?int,
     *     count: int,
     *     remaining: ?int,
     *     blocked: bool,
     *     can_add: bool,
     *     message: ?string
     * }
     */
    public function status(int $schoolId, string $type): array
    {
        $config = self::TYPES[$type] ?? null;
        $plan = $this->plan($schoolId);
        $limit = $this->limit($schoolId, $type);
        $count = $this->count($schoolId, $type);
        $remaining = $limit === null ? null : max(0, $limit - $count);
        $blocked = $limit !== null && $count >= $limit;

        return [
            'plan' => $plan,
            'plan_name' => $plan?->display_name ?: $plan?->name,
            'limit' => $limit,
            'count' => $count,
            'remaining' => $remaining,
            'blocked' => $blocked,
            'can_add' => $limit === null || $remaining > 0,
            'message' => $blocked ? $this->blockedMessage($plan, $limit, $count, $config['label']) : null,
        ];
    }

    public function message(int $schoolId, string $type): ?string
    {
        return $this->status($schoolId, $type)['message'];
    }

    /**
     * A clear, plain-text message for both HTTP flashes and Toshi replies.
     * It always carries the current count and the sales route.
     */
    public function blockedMessage(?Plan $plan, int $limit, int $count, string $label): string
    {
        $name = $plan?->display_name ?: $plan?->name ?: 'current';

        return "Your {$name} plan allows {$limit} {$label}. You already have {$count}. "
            .'Talk to sales to upgrade and add more.';
    }

    /**
     * Message after a partial import: how many landed and why the rest did not.
     */
    public function importNotice(int $schoolId, string $type, int $imported, int $notImported): ?string
    {
        if ($notImported <= 0) {
            return null;
        }

        $status = $this->status($schoolId, $type);
        if ($status['limit'] === null) {
            return null;
        }

        $config = self::TYPES[$type];
        $label = $config['label'];
        $importedLabel = $imported === 1 ? $config['singular'] : $label;
        $notLabel = $notImported === 1 ? $config['singular'] : $label;
        $verb = $notImported === 1 ? 'was' : 'were';
        $name = $status['plan_name'] ?: 'current';

        return "Imported {$imported} {$importedLabel}. {$notImported} {$notLabel} {$verb} not imported: "
            ."your {$name} plan allows {$status['limit']} {$label} and the school now has {$status['count']}. "
            .'Talk to sales to upgrade and add more.';
    }
}
