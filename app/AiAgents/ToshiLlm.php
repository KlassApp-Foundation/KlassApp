<?php

namespace App\AiAgents;

/**
 * Single resolution path for Toshi's openai-compatible provider + model.
 *
 * Live agents, toshi:adversarial-live, and toshi:llm-status must call this
 * (or UsesToshiLlm) so monthly live runs cannot silently drift from production chat config.
 */
final class ToshiLlm
{
    public static function provider(): string
    {
        return 'openai-compatible';
    }

    public static function model(): string
    {
        return (string) config(
            'toshi.model',
            config('ai.providers.openai-compatible.models.text.default', 'deepseek-chat')
        );
    }

    public static function url(): string
    {
        return (string) config('ai.providers.openai-compatible.url', '');
    }

    public static function urlHost(): string
    {
        $host = parse_url(self::url(), PHP_URL_HOST);

        return is_string($host) ? $host : '';
    }

    /**
     * Whether an API key is present in config (never returns the key itself).
     */
    public static function keyConfigured(): bool
    {
        return (string) config('ai.providers.openai-compatible.key', '') !== '';
    }

    /**
     * Short fingerprint of non-secret resolution inputs (provider + model + host).
     * Safe to log / paste into ops chat — no credentials.
     */
    public static function configChecksum(): string
    {
        return substr(hash('sha256', implode("\0", [
            self::provider(),
            self::model(),
            self::urlHost(),
        ])), 0, 16);
    }
}
