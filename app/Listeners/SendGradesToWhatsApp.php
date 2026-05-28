<?php

namespace App\Listeners;

use App\Events\GradesPublished;
use App\Services\OutboundWhatsAppService;
use Illuminate\Support\Facades\Log;

class SendGradesToWhatsApp
{
    public function __construct(
        protected OutboundWhatsAppService $outbound,
    ) {}

    public function handle(GradesPublished $event): void
    {
        $sent = $this->outbound->notifyGradesPublished(
            $event->student->id,
            $event->examId,
        );

        Log::info("GradesPublished: sent {$sent} WhatsApp message(s) for student {$event->student->id}");
    }
}
