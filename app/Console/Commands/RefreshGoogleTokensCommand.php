<?php

namespace App\Console\Commands;

use App\Services\Google\GoogleOAuthService;
use Illuminate\Console\Command;

class RefreshGoogleTokensCommand extends Command
{
    protected $signature = 'google:refresh-tokens {--within=30 : Refresh tokens that expire within this many minutes}';

    protected $description = 'Refresh school Google access tokens that are about to expire';

    public function handle(GoogleOAuthService $oauth): int
    {
        if (! $oauth->isConfigured()) {
            $this->comment('Google client is not configured.');

            return self::SUCCESS;
        }

        $count = $oauth->refreshExpiring((int) $this->option('within'));
        $this->info("Refreshed {$count} Google connection(s).");

        return self::SUCCESS;
    }
}
