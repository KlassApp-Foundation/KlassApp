<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ActsOnLinkRequests;
use App\Services\WhatsApp\LinkRequestBoard;
use Illuminate\View\View;
use Livewire\Component;

/**
 * WhatsApp page (School > WhatsApp), top sections: "Link requests" (one
 * card per pending request, Approve / Reject) and "Linked now" (numbers
 * following a demo student, Unlink). Polls every few seconds so a new
 * BIND shows on the projector without a reload.
 */
class WhatsappLinkRequests extends Component
{
    use ActsOnLinkRequests;

    public const POLL_SECONDS = 4;

    public function mount(): void
    {
        $this->linkRequestAdmin();
    }

    public function render(): View
    {
        $schoolId = (int) $this->linkRequestAdmin()->school_id;
        $board = app(LinkRequestBoard::class);

        return view('livewire.admin.whatsapp-link-requests', [
            'requests' => $board->pending($schoolId),
            'linked' => $board->linkedNow($schoolId),
            'isDemoSchool' => (int) (auth()->user()->school?->is_demo ?? 0) === 1,
            'pollSeconds' => self::POLL_SECONDS,
        ]);
    }
}
