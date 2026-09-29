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
        $this->line('  Provider       : '.$this->resolvedProvider());
        $this->line('  Model          : '.$this->resolvedModel());
        $this->line('  URL host       : '.(ToshiLlm::urlHost() !== '' ? ToshiLlm::urlHost() : '(unresolved)'));
        $this->line('  API key        : '.(ToshiLlm::keyConfigured() ? 'configured' : 'missing'));
        $this->line('  Config checksum: '.$this->resolvedChecksum().' (provider|model|host)');
        $this->newLine();
        $this->comment('Same path as agents / UsesToshiLlm. No secrets printed.');

        return self::SUCCESS;
    }

    /**
     * A status command reports; it must not throw when the key is missing.
     * Strict fail-loud checks live in ToshiLlm and toshi:llm-health.
     */
    private function resolvedProvider(): string
    {
        try {
            return ToshiLlm::provider();
        } catch (\Throwable $e) {
            return 'openai-compatible (unresolved)';
        }
    }

    private function resolvedModel(): string
    {
        try {
            return ToshiLlm::model();
        } catch (\Throwable $e) {
            return (string) config(
                'toshi.model',
                config('ai.providers.openai-compatible.models.text.default', 'deepseek-chat')
            );
        }
    }

    private function resolvedChecksum(): string
    {
        try {
            return ToshiLlm::configChecksum();
        } catch (\Throwable $e) {
            return 'unavailable (config unresolved)';
        }
    }
}
