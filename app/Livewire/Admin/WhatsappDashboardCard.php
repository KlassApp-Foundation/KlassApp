<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ActsOnLinkRequests;
use App\Services\WhatsApp\LinkRequestBoard;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Admin dashboard: the WhatsApp card near the top. Pending request count,
 * the latest three requests with Approve / Reject, and a link to the
 * WhatsApp page. Polls like the WhatsApp page.
 */
class WhatsappDashboardCard extends Component
{
    use ActsOnLinkRequests;

    public const LATEST = 3;

    public function mount(): void
    {
        $this->linkRequestAdmin();
    }

    public function render(): View
    {
        $schoolId = (int) $this->linkRequestAdmin()->school_id;
        $board = app(LinkRequestBoard::class);
        $requests = $board->pending($schoolId, self::LATEST);
        $this->announceNewRequests($requests);
        $this->dispatch('link-request-count', count: $board->pendingCount($schoolId));

        return view('livewire.admin.whatsapp-dashboard-card', [
            'count' => $board->pendingCount($schoolId),
            'requests' => $requests,
            'isDemoSchool' => (int) (auth()->user()->school?->is_demo ?? 0) === 1,
            'pollSeconds' => WhatsappLinkRequests::POLL_SECONDS,
        ]);
    }
}
