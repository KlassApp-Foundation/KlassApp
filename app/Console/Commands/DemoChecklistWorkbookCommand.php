<?php

namespace App\Console\Commands;

use App\Services\Onboarding\DemoChecklistWorkbook;
use Illuminate\Console\Command;

class DemoChecklistWorkbookCommand extends Command
{
    protected $signature = 'demo:checklist-workbook {--out=docs/demo : Directory for the two fictional workbooks}';

    protected $description = 'Write the fictional Northwind Harbour checklist and its all-correct twin';

    public function handle(DemoChecklistWorkbook $workbook): int
    {
        $directory = (string) $this->option('out');
        if (! str_starts_with($directory, '/')) {
            $directory = base_path($directory);
        }

        [$problem, $clean] = $workbook->writePair($directory);
        $this->line('Wrote '.$problem);
        $this->line('Wrote '.$clean);

        return self::SUCCESS;
    }
}
