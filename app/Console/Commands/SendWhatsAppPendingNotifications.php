<?php

namespace App\Console\Commands;

use App\Services\OutboundWhatsAppService;
use Illuminate\Console\Command;

class SendWhatsAppPendingNotifications extends Command
{
    protected $signature = 'whatsapp:send-pending
                            {--limit=100 : Max pending records to process}
                            {--flush-open : Also flush pending for users with open windows}';

    protected $description = 'Send queued WhatsApp notifications that have expired deadlines';

    public function handle(OutboundWhatsAppService $outbound): int
    {
        $this->info('Processing pending WhatsApp notifications...');

        // 1) Flush pending for users who have an open window (free sends)
        if ($this->option('flush-open')) {
            $flushed = $outbound->flushAllOpenWindows($this->option('limit'));
            $this->info("Flushed {$flushed} pending notifications to users with open windows.");
        }

        // 2) Send expired queue items (cold sends — cost $0.004 each)
        $coldSent = $outbound->sendExpiredQueue($this->option('limit'));
        $this->info("Cold-sent {$coldSent} expired notifications.");

        return Command::SUCCESS;
    }
}
