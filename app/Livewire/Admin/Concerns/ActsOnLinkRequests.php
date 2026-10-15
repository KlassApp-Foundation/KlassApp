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

    public function approve(int $requestId): void
    {
        $admin = $this->linkRequestAdmin();
        $board = app(LinkRequestBoard::class);
        $row = $board->pending((int) $admin->school_id)->firstWhere('id', $requestId);
        $approval = $board->pendingApproval((int) $admin->school_id, $requestId);
        if (! $row || ! $approval) {
            $this->notice = ['ok' => false, 'text' => 'This request was already handled or has expired.'];

            return;
        }
        if (! $row['demo_bind'] || ! $row['student_id']) {
            $this->notice = ['ok' => false, 'text' => 'Choose the student for this request on the Approvals page.'];

            return;
        }

        $decision = app(ApprovalDecisionService::class)->approve($approval, $admin, (int) $row['student_id']);
        $this->notice = ['ok' => $decision->ok, 'text' => $decision->ok ? "Approved. {$row['phone']} now follows {$row['child_name']}." : $decision->message];
    }

    public function reject(int $requestId): void
    {
        $admin = $this->linkRequestAdmin();
        $board = app(LinkRequestBoard::class);
        $row = $board->pending((int) $admin->school_id)->firstWhere('id', $requestId);
        $approval = $board->pendingApproval((int) $admin->school_id, $requestId);
        if (! $row || ! $approval) {
            $this->notice = ['ok' => false, 'text' => 'This request was already handled or has expired.'];

            return;
        }

        // The comment is stored on the approval. A WhatsApp Flow parent would
        // see it as the reason, so it is left empty there and the parent gets
        // the standard "check the details" text.
        $comment = $row['demo_bind'] ? 'Rejected on the WhatsApp page.' : null;
        $decision = app(ApprovalDecisionService::class)->reject($approval, $admin, $comment);
        $this->notice = ['ok' => $decision->ok, 'text' => $decision->ok ? 'Request rejected. The number was told it is not linked.' : $decision->message];
    }

    public function unlink(int $whatsAppUserId): void
    {
        $admin = $this->linkRequestAdmin();
        $ok = app(LinkRequestBoard::class)->unlink($whatsAppUserId, $admin);
        $this->notice = $ok
            ? ['ok' => true, 'text' => 'Number unlinked. It no longer follows the student.']
            : ['ok' => false, 'text' => 'This number is no longer linked.'];
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
