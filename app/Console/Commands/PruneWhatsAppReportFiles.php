<?php

namespace App\Console\Commands;

use App\Services\WhatsAppReportCardDeliveryService;
use Illuminate\Console\Command;

class PruneWhatsAppReportFiles extends Command
{
    protected $signature = 'whatsapp:prune-report-files {--hours=2 : Delete PDFs older than this many hours}';

    protected $description = 'Delete expired private WhatsApp report-card PDF files';

    public function handle(WhatsAppReportCardDeliveryService $delivery): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $deleted = $delivery->pruneOlderThanHours($hours);
        $this->info("Pruned {$deleted} WhatsApp report file(s) older than {$hours} hour(s).");

        return self::SUCCESS;
    }
}
