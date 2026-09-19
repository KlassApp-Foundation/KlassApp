<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class McpTransportEraReverificationTest extends TestCase
{
    private const REVERIFICATION_DEADLINE = '2027-04-28';
    private const REVERIFICATION_WINDOW_DAYS = 90;

    #[Test]
    public function mcp_transport_era_gap_requires_periodic_reverification(): void
    {
        $deadline = new \DateTimeImmutable(self::REVERIFICATION_DEADLINE);
        $now = new \DateTimeImmutable('now');

        if ($now <= $deadline) {
            $this->markTestIncomplete(
                'MCP transport era re-verification deadline not yet reached: '
                .self::REVERIFICATION_DEADLINE
                .'. laravel/mcp 0.8.x speaks the pre-2026 (2025-11-25) MCP era; '
                .'the 2026-07-28 specification deprecates it with earliest '
                .'removal ~2027-07-28. See routes/ai.php transport marker and '
                .'plan doc R.8.'
            );
        }

        $verifiedAt = getenv('TOSHI_MCP_TRANSPORT_VERIFIED_AT');

        if ($verifiedAt === false || $verifiedAt === '') {
            $this->fail(
                'MCP transport era re-verification deadline passed ('
                .self::REVERIFICATION_DEADLINE
                .'). laravel/mcp 0.8.x still speaks the pre-2026 (2025-11-25) '
                .'MCP era, deprecated by the 2026-07-28 specification with '
                .'earliest removal ~2027-07-28. Re-verify whether mcp.slack.com '
                .'still serves the legacy era, whether laravel/ai and '
                .'laravel/boost constraints admit laravel/mcp 1.x (2026-07-28 '
                .'era), and any announced removal dates — then set '
                .'TOSHI_MCP_TRANSPORT_VERIFIED_AT to a date within the last '
                .self::REVERIFICATION_WINDOW_DAYS.' days.'
            );
        }

        $verifiedDate = new \DateTimeImmutable($verifiedAt);
        $windowStart = $now->modify('-'.self::REVERIFICATION_WINDOW_DAYS.' days');

        if ($verifiedDate < $windowStart) {
            $this->fail(
                'TOSHI_MCP_TRANSPORT_VERIFIED_AT ('.$verifiedAt
                .') is older than '.self::REVERIFICATION_WINDOW_DAYS
                .' days. Re-verify the MCP transport era gap against current '
                .'laravel/mcp releases and endpoint deprecation status, and '
                .'update the date.'
            );
        }
    }
}
