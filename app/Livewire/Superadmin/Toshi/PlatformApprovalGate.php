<?php

namespace App\Livewire\Superadmin\Toshi;

use App\Services\Toshi\PlatformOpsConversationService;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Models\Conversation;
use Livewire\Component;

/**
 * Review-gate UI for PlatformOperationsAgent pending tool approvals.
 *
 * Builds Decisions keyed by pending approval IDs and resumes via the same
 * PlatformOpsConversationService used by the Complete Approval Flow POST route.
 */
class PlatformApprovalGate extends Component
{
    public string $conversationId;

    /** @var array<string, array{action?: string, result?: string|null, arguments_json?: string, arguments?: array}> */
    public array $decisionDrafts = [];

    public ?string $flashStatus = null;

    public ?string $flashMessage = null;

    public function mount(Conversation $conversation): void
    {
        Gate::authorize('view', $conversation);

        $this->conversationId = $conversation->id;
        $this->resetDrafts($conversation);
    }

    public function approve(string $approvalId): void
    {
        $this->submitMappedDecisions([
            $approvalId => Decision::approve(),
        ]);
    }

    public function reject(string $approvalId): void
    {
        $result = trim((string) ($this->decisionDrafts[$approvalId]['result'] ?? ''));

        $this->submitMappedDecisions([
            $approvalId => Decision::reject($result !== '' ? $result : null),
        ]);
    }

    public function editAndApprove(string $approvalId): void
    {
        $json = (string) ($this->decisionDrafts[$approvalId]['arguments_json'] ?? '{}');
        $arguments = json_decode($json, true);

        if (! is_array($arguments)) {
            $this->addError("decisionDrafts.{$approvalId}.arguments_json", 'Arguments must be valid JSON.');

            return;
        }

        $this->submitMappedDecisions([
            $approvalId => Decision::edit($arguments),
        ]);
    }

    /**
     * @param  array<string, Decision>  $explicit
     */
    protected function submitMappedDecisions(array $explicit): void
    {
        $conversation = Conversation::query()->findOrFail($this->conversationId);
        Gate::authorize('view', $conversation);

        $ops = app(PlatformOpsConversationService::class);
        $pending = $ops->pendingApprovals($conversation);

        $map = [];
        foreach ($pending as $approval) {
            $map[$approval->id] = $explicit[$approval->id]
                ?? Decision::reject('Not reviewed in this batch.');
        }

        if ($map === []) {
            $this->addError('submit', 'No pending approvals to resolve.');

            return;
        }

        try {
            $response = $ops->prompt(
                auth()->user(),
                $conversation,
                Decisions::from($map),
            );
        } catch (\Throwable $e) {
            $this->addError('submit', $e->getMessage());

            return;
        }

        $this->flashStatus = $response->hasPendingApprovals() ? 'awaiting_approval' : 'complete';
        $this->flashMessage = $response->text;

        $conversation->refresh();
        $this->resetDrafts($conversation);
    }

    protected function resetDrafts(Conversation $conversation): void
    {
        $this->decisionDrafts = [];

        foreach (app(PlatformOpsConversationService::class)->pendingApprovals($conversation) as $approval) {
            $this->decisionDrafts[$approval->id] = [
                'action' => 'approve',
                'result' => '',
                'arguments_json' => json_encode($approval->arguments, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ];
        }
    }

    public function render()
    {
        $conversation = Conversation::query()
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')])
            ->findOrFail($this->conversationId);

        $ops = app(PlatformOpsConversationService::class);

        return view('livewire.superadmin.toshi.platform-approval-gate', [
            'conversation' => $conversation,
            'pendingApprovals' => $ops->pendingApprovals($conversation),
            'hasPendingApprovals' => $ops->hasPendingApprovals($conversation),
        ]);
    }
}
