<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GoogleDrivePreviewReverificationTest extends TestCase
{
    private const REVERIFICATION_DEADLINE = '2027-03-18';
    private const REVERIFICATION_WINDOW_DAYS = 90;

    #[Test]
    public function google_drive_preview_requires_periodic_reverification(): void
    {
        $deadline = new \DateTimeImmutable(self::REVERIFICATION_DEADLINE);
        $now = new \DateTimeImmutable('now');

        if ($now <= $deadline) {
            $this->markTestIncomplete(
                'Google Drive Developer Preview re-verification deadline not yet reached: '
                .self::REVERIFICATION_DEADLINE
            );
        }

        $verifiedAt = getenv('TOSHI_GOOGLE_DRIVE_VERIFIED_AT');

        if ($verifiedAt === false || $verifiedAt === '') {
            $this->fail(
                'Google Drive Developer Preview re-verification deadline passed ('
                .self::REVERIFICATION_DEADLINE
                .'). Set TOSHI_GOOGLE_DRIVE_VERIFIED_AT to a date within the last '
                .self::REVERIFICATION_WINDOW_DAYS
                .' days after re-verifying against current Google Workspace MCP docs.'
            );
        }

        $verifiedDate = new \DateTimeImmutable($verifiedAt);
        $windowStart = $now->modify('-'.self::REVERIFICATION_WINDOW_DAYS.' days');

        if ($verifiedDate < $windowStart) {
            $this->fail(
                'TOSHI_GOOGLE_DRIVE_VERIFIED_AT ('.$verifiedAt
                .') is older than '.self::REVERIFICATION_WINDOW_DAYS
                .' days. Re-verify against current Google Workspace MCP docs and update the date.'
            );
        }
    }
}
