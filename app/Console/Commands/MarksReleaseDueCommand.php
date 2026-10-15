<?php

namespace App\Console\Commands;

use App\Services\MarksRelease\MarksReleaseService;
use Illuminate\Console\Command;

/**
 * Runs every scheduled marks release that is due (Task 9 Tier 2). Safe to
 * run twice: each release is claimed once and never sends again.
 */
class MarksReleaseDueCommand extends Command
{
    protected $signature = 'marks:release-due';

    protected $description = 'Send the marks releases whose time has come (demo-bound numbers only)';

    public function handle(MarksReleaseService $releases): int
    {
        $ran = $releases->runDue();
        $this->info("Marks releases run: {$ran}");

        return self::SUCCESS;
    }
}
