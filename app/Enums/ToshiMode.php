<?php

namespace App\Enums;

/**
 * Per-school Toshi surface mode (soft-launch switch).
 *
 * - Preview: panel visible, Coming soon, no scripted onboarding, no AI/MCP
 * - Onboarding: scripted setup without requiring an AI key (#929)
 * - Assistant: free-form AI assistant (requires AI key + this mode)
 */
enum ToshiMode: string
{
    case Preview = 'preview';
    case Onboarding = 'onboarding';
    case Assistant = 'assistant';
}
