<?php

namespace App\AiAgents;

use App\Exceptions\AmbiguousToshiLlmConfigException;
use App\Exceptions\MissingToshiLlmApiKeyException;

/**
 * Single resolution path for Toshi's LLM provider and model.
 *
 * Live agents, toshi:adversarial-live, toshi:llm-status, and toshi:llm-health
 * must call this (or UsesToshiLlm) so monthly live runs cannot silently drift
 * from production chat config.
 *
 * Provider is config('toshi.llm_provider') (TOSHI_LLM_PROVIDER). Unset or blank
 * stays on the openai-compatible provider (DeepSeek by default). `bedrock`
 * uses laravel/ai's Bedrock Converse provider. Any other value fails loudly.
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
 * Those checks apply only while the resolved provider is openai-compatible.
 * When the provider is bedrock, TOSHI_LLM_MODEL is the Bedrock model or
 * inference-profile id and is allowed to differ from OPENAI_COMPATIBLE_MODEL.
 *
 * What counts as a conflict on the openai-compatible provider (any one fails loudly):
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
 * unknown custom hosts (no false positive on self-hosted gateways);
 * TOSHI_LLM_PROVIDER=bedrock with its own model id.
 *
 * Failure mode: AmbiguousToshiLlmConfigException / MissingToshiLlmApiKeyException
 * on first model()/provider() resolve and from toshi:llm-health — NOT AppServiceProvider
 * boot (unrelated HTTP routes must keep working). Alerting evidence: no Sentry/Bugsnag in
 * composer; ActivityLog is user-action audit, not infra. Monitored signal =
 * exception + Log::critical (health command) + non-zero Artisan exit for
 * cron/k8s (schedule wiring is ops/OpenCode follow-up — not in Kernel here).
 */
final class ToshiLlm
{
    public static function provider(): string
    {
        self::assertConfigConsistent();
        self::assertApiKeyConfigured();

        return self::configuredProvider();
    }

    public static function model(): string
    {
        self::assertConfigConsistent();
        self::assertApiKeyConfigured();

        return self::resolvedModel();
    }

    /**
     * Provider name from config, before credential checks.
     * Blank or unset stays on the current openai-compatible provider.
     */
    public static function configuredProvider(): string
    {
        $raw = self::nonEmptyString(config('toshi.llm_provider'));

        return $raw !== null ? strtolower($raw) : 'openai-compatible';
    }

    /**
     * Model name from config, before credential checks.
     */
    public static function configuredModel(): string
    {
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
     * Whether an API key is present in the openai-compatible config (never returns the key itself).
     */
    public static function keyConfigured(): bool
    {
        return (string) config('ai.providers.openai-compatible.key', '') !== '';
    }

    /**
     * Non-secret description of how the active provider authenticates.
     */
    public static function credentialSummary(): string
    {
        if (self::configuredProvider() === 'bedrock') {
            if ((string) config('ai.providers.bedrock.key', '') !== '') {
                return 'bearer token configured';
            }

            $access = (string) config('ai.providers.bedrock.access_key_id', '');
            $secret = (string) config('ai.providers.bedrock.secret_access_key', '');
            if ($access !== '' && $secret !== '') {
                return 'static access key configured';
            }

            if (self::bedrockUsesDefaultChain()) {
                return 'default AWS credential chain';
            }

            return 'missing';
        }

        return self::keyConfigured() ? 'api key configured' : 'api key missing';
    }

    /**
     * Short fingerprint of non-secret resolution inputs (provider + model + host or region).
     * Safe to log / paste into ops chat — no credentials.
     */
    public static function configChecksum(): string
    {
        $provider = self::provider();
        $locator = $provider === 'bedrock'
            ? (string) config('ai.providers.bedrock.region', '')
            : self::urlHost();

        return substr(hash('sha256', implode("\0", [
            $provider,
            self::model(),
            $locator,
        ])), 0, 16);
    }

    /**
     * Fail loudly when the active provider has no usable credentials.
     *
     * openai-compatible: OPENAI_COMPATIBLE_API_KEY (preferred) or TOSHI_LLM_API_KEY.
     * bedrock: bearer token, static access key, or the AWS SDK default credential
     * chain (AWS_USE_DEFAULT_CREDENTIALS, default true). No API key is required
     * when the default chain is on.
     *
     * @throws MissingToshiLlmApiKeyException
     */
    public static function assertApiKeyConfigured(): void
    {
        if (self::configuredProvider() === 'bedrock') {
            if (self::bedrockCredentialsConfigured()) {
                return;
            }

            throw new MissingToshiLlmApiKeyException(
                'Bedrock credentials are not configured. Set AWS_BEARER_TOKEN_BEDROCK, or AWS_ACCESS_KEY_ID and '
                .'AWS_SECRET_ACCESS_KEY, or leave AWS_USE_DEFAULT_CREDENTIALS enabled for the AWS credential chain. '
                .'Hardcoded keys in config are not allowed.',
                [
                    'provider' => 'bedrock',
                    'region' => (string) config('ai.providers.bedrock.region', ''),
                    'env_keys' => [
                        'AWS_BEARER_TOKEN_BEDROCK',
                        'AWS_ACCESS_KEY_ID',
                        'AWS_SECRET_ACCESS_KEY',
                        'AWS_USE_DEFAULT_CREDENTIALS',
                    ],
                ],
            );
        }

        if (self::keyConfigured()) {
            return;
        }

        throw new MissingToshiLlmApiKeyException(
            'Toshi LLM API key is not configured. Set OPENAI_COMPATIBLE_API_KEY in the environment '
            .'(legacy alias: TOSHI_LLM_API_KEY). Hardcoded keys in config are not allowed.',
            [
                'provider' => 'openai-compatible',
                'url_host' => self::urlHost(),
                'env_keys' => ['OPENAI_COMPATIBLE_API_KEY', 'TOSHI_LLM_API_KEY'],
            ],
        );
    }

    /**
     * Fail loudly when dual LLM env sets conflict or model/host families mismatch.
     *
     * @throws AmbiguousToshiLlmConfigException
     */
    public static function assertConfigConsistent(): void
    {
        $provider = self::configuredProvider();

        if ($provider === 'bedrock') {
            self::assertBedrockConfig();

            return;
        }

        if ($provider !== 'openai-compatible') {
            throw new AmbiguousToshiLlmConfigException(
                "Unknown Toshi LLM provider [{$provider}]. Use openai-compatible or bedrock.",
                ['provider' => $provider],
            );
        }

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
        if (self::configuredProvider() === 'bedrock') {
            return self::bedrockModel();
        }

        return (string) config(
            'toshi.model',
            config('ai.providers.openai-compatible.models.text.default', 'deepseek-chat')
        );
    }

    /**
     * @throws AmbiguousToshiLlmConfigException
     */
    private static function assertBedrockConfig(): void
    {
        if (self::bedrockModelOrNull() === null) {
            throw new AmbiguousToshiLlmConfigException(
                'Bedrock is selected but no model is set. Set TOSHI_LLM_MODEL to a Claude model ID or inference profile ID.',
                ['provider' => 'bedrock'],
            );
        }

        if (self::nonEmptyString(config('ai.providers.bedrock.region')) === null) {
            throw new AmbiguousToshiLlmConfigException(
                'Bedrock region is empty. Set AWS_BEDROCK_REGION.',
                ['provider' => 'bedrock'],
            );
        }
    }

    private static function bedrockModel(): string
    {
        return (string) self::bedrockModelOrNull();
    }

    private static function bedrockModelOrNull(): ?string
    {
        return self::nonEmptyString(config('toshi.llm_env.toshi_llm_model'))
            ?? self::nonEmptyString(config('ai.providers.bedrock.models.text.default'));
    }

    private static function bedrockCredentialsConfigured(): bool
    {
        if ((string) config('ai.providers.bedrock.key', '') !== '') {
            return true;
        }

        $access = (string) config('ai.providers.bedrock.access_key_id', '');
        $secret = (string) config('ai.providers.bedrock.secret_access_key', '');
        if ($access !== '' && $secret !== '') {
            return true;
        }

        return self::bedrockUsesDefaultChain();
    }

    private static function bedrockUsesDefaultChain(): bool
    {
        $value = config('ai.providers.bedrock.use_default_credential_provider', true);
        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
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
