<?php

namespace App\Console\Commands;

use App\Services\TrialService;
use Illuminate\Console\Command;

class DowngradeExpiredTrials extends Command
{
    protected $signature = 'trial:downgrade-expired';
    protected $description = 'Downgrade schools with expired trials to the Freemium plan';

    public function handle(): int
    {
        $expired = TrialService::getExpiredTrials();

        if ($expired->isEmpty()) {
            $this->info('No expired trials found.');
            return self::SUCCESS;
        }

        $downgraded = 0;
        foreach ($expired as $cp) {
            TrialService::downgradeToFreemium($cp->school_id);
            $downgraded++;
        }

        $this->info("Downgraded {$downgraded} school(s) from expired trial to Freemium.");
        return self::SUCCESS;
    }
}
