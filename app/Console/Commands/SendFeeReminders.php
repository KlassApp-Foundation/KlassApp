<?php

namespace App\Console\Commands;

use App\Models\StudentAcademic;
use App\Models\FeesCategories;
use App\Services\OutboundWhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendFeeReminders extends Command
{
    protected $signature = 'whatsapp:send-fee-reminders
                            {--type=reminder : reminder or overdue}
                            {--school-id= : Filter by school ID}
                            {--dry-run : Log what would be sent without sending}';

    protected $description = 'Send WhatsApp fee reminders to parents of students with pending fees';

    public function handle(OutboundWhatsAppService $outbound): int
    {
        $type = $this->option('type');
        $schoolId = $this->option('school-id');
        $dryRun = $this->option('dry-run');

        $this->info("Running fee reminder dispatch (type: {$type})" . ($dryRun ? ' [DRY RUN]' : ''));

        // Get distinct standard_ids that have fee categories
        $feeStandards = FeesCategories::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNotNull('amount')
            ->where('amount', '>', 0)
            ->pluck('standard_id')
            ->unique()
            ->filter();

        if ($feeStandards->isEmpty()) {
            $this->warn('No fee categories found with amounts.');
            return 0;
        }

        // Get students in those standards, with parents preloaded
        $students = StudentAcademic::with(['user.parents', 'standard'])
            ->whereIn('standard_id', $feeStandards)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get()
            ->pluck('user')
            ->filter();

        $bar = $this->output->createProgressBar($students->count());
        $bar->start();

        $totalSent = 0;

        foreach ($students as $student) {
            if ($dryRun) {
                $phoneCount = count($outbound->getParentPhones($student));
                if ($phoneCount > 0) {
                    $this->line(" [DRY] Would notify {$phoneCount} parent(s) of student #{$student->id} ({$student->name})");
                }
            } else {
                try {
                    $sent = $outbound->notifyFeeReminder($student->id, $type);
                    $totalSent += $sent;
                } catch (\Throwable $e) {
                    Log::error("Fee reminder failed for student {$student->id}: " . $e->getMessage());
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Total WhatsApp messages sent: {$totalSent}");

        return 0;
    }
}
