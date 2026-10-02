<?php

namespace App\Enums;

/**
 * Per-school Toshi surface mode (soft-launch switch).
 *
 * - Preview (configured default): panel collapsed, Coming soon, no scripted guide, no AI
 * - Onboarding: scripted setup guide, no AI/MCP
 * - Assistant: free-form AI assistant (requires AI key + this mode; never a default)
 */
enum ToshiMode: string
{
    case Preview = 'preview';
    case Onboarding = 'onboarding';
    case Assistant = 'assistant';

    /**
     * Mode applied to a new school when the caller does not choose one.
     *
     * Reads config('toshi.default_mode') / TOSHI_DEFAULT_MODE so ops can switch
     * preview ↔ onboarding without a code change. Assistant is never accepted.
     */
    public static function configuredDefault(): self
    {
        $raw = config('toshi.default_mode', self::Preview->value);
        if ($raw instanceof self) {
            $mode = $raw;
        } else {
            $mode = self::tryFrom(is_string($raw) ? strtolower(trim($raw)) : '');
        }

        if ($mode === null || $mode === self::Assistant) {
            return self::Preview;
        }

        return $mode;
    }
}
