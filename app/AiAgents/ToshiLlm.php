<?php

namespace App\AiAgents;

use App\Exceptions\AmbiguousToshiLlmConfigException;

/**
 * Single resolution path for Toshi's openai-compatible provider + model.
 *
 * Live agents, toshi:adversarial-live, toshi:llm-status, and toshi:llm-health
 * must call this (or UsesToshiLlm) so monthly live runs cannot silently drift
 * from production chat config.
 *
 * ---------------------------------------------------------------------------
 * Dual-config conflict detection (assertConfigConsistent)
 * ---------------------------------------------------------------------------
 *
 * Incident class (2026-08-02): prod had TOSHI_LLM_MODEL=meta/llama-3.1-8b-instruct
 * (NVIDIA NIM leftover) while the live openai-compatible URL was DeepSeek
 * (api.deepseek.com). OPENAI_COMPATIBLE_MODEL was unset, so config/toshi.php
 * fell through to the NVIDIA model name → DeepSeek HTTP 400 → empty
 * agent_conversations. Live ops fixed via OPENAI_COMPATIBLE_MODEL=deepseek-v4-flash.
 *
 * What counts as a conflict (any one fails loudly):
 *  1. Both OPENAI_COMPATIBLE_MODEL and TOSHI_LLM_MODEL are non-empty and differ
 *     (split-brain: toshi.model prefers OPENAI_COMPATIBLE_MODEL; ai.smartest
 *     still reads TOSHI_LLM_MODEL).
 *  2. Both OPENAI_COMPATIBLE_URL and TOSHI_LLM_BASE_URL are non-empty with
 *     different hosts (two provider endpoints configured).
 *  3. Resolved model family is incompatible with the active openai-compatible
 *     URL host (e.g. meta/llama-* or nvidia/* against deepseek.com — exact
 *     incident shape when only the legacy TOSHI_LLM_MODEL drives resolution).
 *
 * Not a conflict: only OPENAI_COMPATIBLE_* set; legacy TOSHI_LLM_MODEL alone
 * when family matches URL host; both model envs set to the same value;
 * unknown custom hosts (no false positive on self-hosted gateways).
 *
 * Failure mode: AmbiguousToshiLlmConfigException on first model()/provider()
 * resolve and from toshi:llm-health — NOT AppServiceProvider boot (unrelated
 * HTTP routes must keep working). Alerting evidence: no Sentry/Bugsnag in
 * composer; ActivityLog is user-action audit, not infra. Monitored signal =
 * exception + Log::critical (health command) + non-zero Artisan exit for
 * cron/k8s (schedule wiring is ops/OpenCode follow-up — not in Kernel here).
 */
final class ToshiLlm
{
    public static function provider(): string
    {
        self::assertConfigConsistent();

        return 'openai-compatible';
    }

    public static function model(): string
    {
        self::assertConfigConsistent();

        return self::resolvedModel();
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

    /**
     * Fail loudly when dual LLM env sets conflict or model/host families mismatch.
     *
     * @throws AmbiguousToshiLlmConfigException
     */
    public static function assertConfigConsistent(): void
    {
        $ocModel = self::nonEmptyString(config('toshi.llm_env.openai_compatible_model'));
        $tlModel = self::nonEmptyString(config('toshi.llm_env.toshi_llm_model'));
        $ocUrl = self::nonEmptyString(config('toshi.llm_env.openai_compatible_url'));
        $tlUrl = self::nonEmptyString(config('toshi.llm_env.toshi_llm_base_url'));

        if ($ocModel !== null && $tlModel !== null && $ocModel !== $tlModel) {
            throw new AmbiguousToshiLlmConfigException(
                'Ambiguous Toshi LLM config: OPENAI_COMPATIBLE_MODEL and TOSHI_LLM_MODEL are both set and differ. '
                .'Keep a single source of truth (prefer OPENAI_COMPATIBLE_MODEL; unset or align TOSHI_LLM_MODEL).',
                [
                    'openai_compatible_model' => $ocModel,
                    'toshi_llm_model' => $tlModel,
                    'url_host' => self::urlHost(),
                ],
            );
        }

        if ($ocUrl !== null && $tlUrl !== null) {
            $ocHost = self::hostFromUrl($ocUrl);
            $tlHost = self::hostFromUrl($tlUrl);
            if ($ocHost !== '' && $tlHost !== '' && $ocHost !== $tlHost) {
                throw new AmbiguousToshiLlmConfigException(
                    'Ambiguous Toshi LLM config: OPENAI_COMPATIBLE_URL and TOSHI_LLM_BASE_URL resolve to different hosts.',
                    [
                        'openai_compatible_url_host' => $ocHost,
                        'toshi_llm_base_url_host' => $tlHost,
                    ],
                );
            }
        }

        $resolvedModel = self::resolvedModel();
        $urlHost = self::urlHost();
        $modelFamily = self::providerFamilyFromModel($resolvedModel);
        $hostFamily = self::providerFamilyFromHost($urlHost);

        if ($modelFamily !== null && $hostFamily !== null && $modelFamily !== $hostFamily) {
            throw new AmbiguousToshiLlmConfigException(
                "Ambiguous Toshi LLM config: resolved model [{$resolvedModel}] (family={$modelFamily}) "
                ."is incompatible with URL host [{$urlHost}] (family={$hostFamily}). "
                .'This is the dual-provider incident class that produced empty agent_conversations.',
                [
                    'resolved_model' => $resolvedModel,
                    'model_family' => $modelFamily,
                    'url_host' => $urlHost,
                    'host_family' => $hostFamily,
                    'openai_compatible_model' => $ocModel,
                    'toshi_llm_model' => $tlModel,
                ],
            );
        }
    }

    private static function resolvedModel(): string
    {
        return (string) config(
            'toshi.model',
            config('ai.providers.openai-compatible.models.text.default', 'deepseek-chat')
        );
    }

    private static function nonEmptyString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private static function hostFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : '';
    }

    /**
     * Best-effort provider family from URL host. Unknown hosts return null (no fail).
     */
    private static function providerFamilyFromHost(string $host): ?string
    {
        $host = strtolower($host);

        if ($host === '') {
            return null;
        }

        if (str_contains($host, 'deepseek')) {
            return 'deepseek';
        }

        if (str_contains($host, 'nvidia') || str_contains($host, 'nvcf')) {
            return 'nvidia';
        }

        if (str_contains($host, 'openai.com')) {
            return 'openai';
        }

        return null;
    }

    /**
     * Best-effort provider family from model slug. Unknown slugs return null (no fail).
     */
    private static function providerFamilyFromModel(string $model): ?string
    {
        $model = strtolower(trim($model));

        if ($model === '') {
            return null;
        }

        if (str_starts_with($model, 'deepseek')) {
            return 'deepseek';
        }

        if (
            str_starts_with($model, 'gpt-')
            || str_starts_with($model, 'o1')
            || str_starts_with($model, 'o3')
            || str_starts_with($model, 'o4')
        ) {
            return 'openai';
        }

        if (
            str_contains($model, '/')
            || str_starts_with($model, 'llama')
            || str_starts_with($model, 'meta')
            || str_starts_with($model, 'nvidia')
            || str_starts_with($model, 'nemotron')
        ) {
            return 'nvidia';
        }

        return null;
    }
}
