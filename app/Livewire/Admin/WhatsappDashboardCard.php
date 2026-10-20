<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ActsOnLinkRequests;
use App\Services\WhatsApp\LinkRequestBoard;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Admin dashboard poller. No panel: a newer link request still toasts, and
 * the sidebar badge still receives the waiting count. Approve and reject
 * stay available to the same component the public demo already allows.
 */
class WhatsappDashboardCard extends Component
{
    use ActsOnLinkRequests;

    public function mount(): void
    {
        $this->linkRequestAdmin();
    }

    public function render(): View
    {
        $schoolId = (int) $this->linkRequestAdmin()->school_id;
        $board = app(LinkRequestBoard::class);
        $this->announceNewRequests($board->pending($schoolId, 1));
        $this->dispatch('link-request-count', count: $board->pendingCount($schoolId));

        return view('livewire.admin.whatsapp-dashboard-card', [
            'pollSeconds' => WhatsappLinkRequests::POLL_SECONDS,
        ]);
    }
}
