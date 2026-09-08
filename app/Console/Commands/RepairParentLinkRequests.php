<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\ParentLinkRequestService;
use Illuminate\Console\Command;

/**
 * Backfill school_id + Approval for pending ParentLinkRequests that failed school-name match.
 */
class RepairParentLinkRequests extends Command
{
    protected $signature = 'whatsapp:repair-parent-link-requests
                            {--id= : Repair a single parent_link_requests.id}
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Re-resolve pending ParentLinkRequests with null school_id and create missing Approvals';

    public function handle(ParentLinkRequestService $service): int
    {
        $id = $this->option('id') !== null && $this->option('id') !== ''
            ? (int) $this->option('id')
            : null;
        $dryRun = (bool) $this->option('dry-run');

        $stats = $service->repairUnresolvedPending($id, $dryRun);

        $this->table(
            ['metric', 'count'],
            collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );

        if ($dryRun) {
            $this->warn('Dry run — no rows were updated.');
        }

        return self::SUCCESS;
    }
}
