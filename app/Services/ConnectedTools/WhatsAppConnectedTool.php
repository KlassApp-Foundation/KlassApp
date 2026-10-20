<?php

namespace App\Services\ConnectedTools;

use App\Contracts\ConnectedTools\ConnectedTool;
use App\Models\WhatsAppUser;
use App\Services\WhatsApp\LinkRequestBoard;
use DateTimeInterface;

/**
 * WhatsApp is not a per-school OAuth grant. The Meta app credentials are
 * instance-wide. This school is Connected only when a parent of this school
 * has a verified, opted-in WhatsApp number (the same rule as
 * LinkRequestBoard::linkedNumberCount, which the dashboard already uses).
 *
 * An admin's own onboarding number does not count: that row is not a parent.
 * Needs attention means a parent link for this school was started or then
 * stopped, and no parent number is currently reachable.
 */
class WhatsAppConnectedTool implements ConnectedTool
{
    public function __construct(private readonly LinkRequestBoard $links)
    {
    }

    public function key(): string
    {
        return 'whatsapp';
    }

    public function stateForSchool(int $schoolId): array
    {
        $settings = url('/admin/whatsapp/dashboard');

        if ($this->links->linkedNumberCount($schoolId) > 0) {
            return [
                'status' => ConnectedToolsRegistry::CONNECTED,
                'attention' => null,
                'last_synced_at' => $this->latestActivity($schoolId),
                'action' => ConnectedToolsRegistry::ACTION_SETTINGS,
                'action_url' => $settings,
                'action_method' => 'GET',
            ];
        }

        if ($this->hasUnhealthyParentLink($schoolId)) {
            return [
                'status' => ConnectedToolsRegistry::NEEDS_ATTENTION,
                'attention' => 'A parent link is unfinished, or a parent has stopped WhatsApp messages.',
                'last_synced_at' => null,
                'action' => ConnectedToolsRegistry::ACTION_SETTINGS,
                'action_url' => $settings,
                'action_method' => 'GET',
            ];
        }

        return [
            'status' => ConnectedToolsRegistry::NOT_CONNECTED,
            'attention' => null,
            'last_synced_at' => null,
            'action' => ConnectedToolsRegistry::ACTION_SETTINGS,
            'action_url' => $settings,
            'action_method' => 'GET',
        ];
    }

    private function hasUnhealthyParentLink(int $schoolId): bool
    {
        return WhatsAppUser::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('user_id')
            ->whereHas('user', function ($query) use ($schoolId) {
                $query->where('usergroup_id', 7)
                    ->where(function ($query) use ($schoolId) {
                        $query->where('school_id', $schoolId)->orWhereNull('school_id');
                    });
            })
            ->where(function ($query) {
                $query->whereNull('verified_at')->orWhere('opted_in', false);
            })
            ->exists();
    }

    private function latestActivity(int $schoolId): ?DateTimeInterface
    {
        $latest = null;

        $rows = WhatsAppUser::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('user_id')
            ->whereNotNull('verified_at')
            ->where('opted_in', true)
            ->whereHas('user', function ($query) use ($schoolId) {
                $query->where('usergroup_id', 7)
                    ->where(function ($query) use ($schoolId) {
                        $query->where('school_id', $schoolId)->orWhereNull('school_id');
                    });
            })
            ->where(function ($query) {
                $query->whereNull('demo_student_id')
                    ->orWhere('demo_linked_at', '>', \App\Services\Demo\DemoFollowAlong::bindCutoff());
            })
            ->get(['last_inbound_at', 'verified_at']);

        foreach ($rows as $row) {
            $at = $row->last_inbound_at ?? $row->verified_at;

            if ($at !== null && ($latest === null || $at->greaterThan($latest))) {
                $latest = $at;
            }
        }

        return $latest;
    }
}
