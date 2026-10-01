<?php

namespace App\Enums;

/**
 * Per-school Toshi surface mode (soft-launch switch).
 *
 * - Onboarding (signup default): scripted setup guide, no AI/MCP
 * - Preview (per-school fallback): panel visible, Coming soon, no scripted guide
 * - Assistant: free-form AI assistant (requires AI key + this mode)
 */
enum ToshiMode: string
{
    case Preview = 'preview';
    case Onboarding = 'onboarding';
    case Assistant = 'assistant';
}
