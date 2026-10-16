<?php

namespace App\Livewire\Admin\Concerns;

use App\Services\Approvals\ApprovalDecisionService;
use App\Services\WhatsApp\LinkRequestBoard;

/**
 * Approve, Reject and Unlink for the WhatsApp link request cards. Approve
 * and reject call ApprovalDecisionService, the same code as the Approvals
 * page; unlink calls LinkRequestBoard::unlink (the demo:expire-chats
 * change). Each action looks the record up again inside the admin's own
 * school, so an id from another school is never actionable.
 */
trait ActsOnLinkRequests
{
    /** Feedback shown above the cards after an action: ['ok' => bool, 'text' => string]. */
    public ?array $notice = null;

    /** Chosen student for a form request that matched more than one child. */
    public array $studentChoice = [];

    /** Highest request id already announced, so a poll toasts only a new one. */
    public ?int $announcedRequestId = null;

    public function approve(int $requestId, ?int $studentId = null): void
    {
        $admin = $this->linkRequestAdmin();
        $board = app(LinkRequestBoard::class);
        $row = $board->pending((int) $admin->school_id)->firstWhere('id', $requestId);
        $approval = $board->pendingApproval((int) $admin->school_id, $requestId);
        if (! $row || ! $approval) {
            $this->tell(false, 'This request was already handled or has expired.');

            return;
        }

        $chosen = (int) ($studentId ?: ($this->studentChoice[$requestId] ?? 0) ?: ($row['student_id'] ?? 0));
        $allowed = array_map('intval', (array) ($row['candidate_ids'] ?? []));
        if ($row['student_id']) {
            $allowed[] = (int) $row['student_id'];
        }
        if ($chosen < 1 || ! in_array($chosen, $allowed, true)) {
            $this->tell(false, 'Choose the student for this request on the Approvals page.');

            return;
        }

        $decision = app(ApprovalDecisionService::class)->approve($approval, $admin, $chosen);
        $who = $row['profile_name'] !== '' ? $row['profile_name'] : $row['parent_name'];
        $this->tell(
            $decision->ok,
            $decision->ok ? "Approved. {$who} ({$row['phone_display']}) now follows {$row['child_name']}." : $decision->message,
        );
    }

    public function reject(int $requestId): void
    {
        $admin = $this->linkRequestAdmin();
        $board = app(LinkRequestBoard::class);
        $row = $board->pending((int) $admin->school_id)->firstWhere('id', $requestId);
        $approval = $board->pendingApproval((int) $admin->school_id, $requestId);
        if (! $row || ! $approval) {
            $this->tell(false, 'This request was already handled or has expired.');

            return;
        }

        // The comment is stored on the approval. A WhatsApp Flow parent would
        // see it as the reason, so it is left empty there and the parent gets
        // the standard "check the details" text.
        $comment = $row['demo_bind'] ? 'Rejected on the WhatsApp page.' : null;
        $decision = app(ApprovalDecisionService::class)->reject($approval, $admin, $comment);
        $who = $row['profile_name'] !== '' ? $row['profile_name'] : $row['parent_name'];
        $this->tell($decision->ok, $decision->ok ? "Request rejected. {$who} was told the number is not linked." : $decision->message);
    }

    public function unlink(int $whatsAppUserId): void
    {
        $admin = $this->linkRequestAdmin();
        $ok = app(LinkRequestBoard::class)->unlink($whatsAppUserId, $admin);
        $this->tell(
            $ok,
            $ok ? 'Number unlinked. It no longer follows the student.' : 'This number is no longer linked.',
        );
    }

    /**
     * Toast when a newer request arrives while the page is open. The first
     * render only remembers the current id.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $requests
     */
    protected function announceNewRequests($requests): void
    {
        $latest = $requests->first();
        $latestId = (int) ($latest['id'] ?? 0);
        if ($this->announcedRequestId === null) {
            $this->announcedRequestId = $latestId;

            return;
        }
        if ($latestId <= $this->announcedRequestId) {
            return;
        }
        $this->announcedRequestId = $latestId;
        $who = ($latest['profile_name'] ?? '') !== '' ? $latest['profile_name'] : $latest['parent_name'];
        $this->dispatch(
            'alert',
            type: 'info',
            title: 'WhatsApp link request',
            message: $who.' asked to link '.$latest['child_name'].' ('.$latest['source_label'].').',
        );
    }

    private function tell(bool $ok, string $text): void
    {
        $this->notice = ['ok' => $ok, 'text' => $text];
        $this->dispatch('alert', type: $ok ? 'success' : 'error', title: 'WhatsApp', message: $text);
    }

    public function dismissNotice(): void
    {
        $this->notice = null;
    }

    protected function linkRequestAdmin(): \App\Models\User
    {
        $user = auth()->user();
        abort_unless($user !== null && (int) $user->usergroup_id === 3 && (int) $user->school_id > 0, 403);

        return $user;
    }
}
