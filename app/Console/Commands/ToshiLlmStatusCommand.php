<?php

namespace App\Console\Commands;

use App\AiAgents\ToshiLlm;
use Illuminate\Console\Command;

/**
 * Lightweight runtime diagnostic for the Toshi LLM resolution path.
 *
 * Ops (VPS): docker exec sms-app php artisan toshi:llm-status
 * Never prints API keys, bearer tokens, or full URLs that may embed credentials.
 */
class ToshiLlmStatusCommand extends Command
{
    protected $signature = 'toshi:llm-status';

    protected $description = 'Report live Toshi LLM provider/model/host (no secrets) via ToshiLlm resolver';

    public function handle(): int
    {
        $this->info('Toshi LLM status (runtime)');
        $this->line('  Provider       : '.ToshiLlm::provider());
        $this->line('  Model          : '.ToshiLlm::model());
        $this->line('  URL host       : '.(ToshiLlm::urlHost() !== '' ? ToshiLlm::urlHost() : '(unresolved)'));
        $this->line('  API key        : '.(ToshiLlm::keyConfigured() ? 'configured' : 'missing'));
        $this->line('  Config checksum: '.ToshiLlm::configChecksum().' (provider|model|host)');
        $this->newLine();
        $this->comment('Same path as agents / UsesToshiLlm. No secrets printed.');

        return self::SUCCESS;
    }
}
