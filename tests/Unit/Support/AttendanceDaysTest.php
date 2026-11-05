<?php

namespace Tests\Unit\Support;

use App\Support\AttendanceDays;
use PHPUnit\Framework\TestCase;

class AttendanceDaysTest extends TestCase
{
    public function test_two_present_sessions_are_one_day(): void
    {
        $rows = [
            ['date' => '2026-10-05', 'status' => 1],
            ['date' => '2026-10-05', 'status' => 1],
            ['date' => '2026-10-06', 'status' => 1],
            ['date' => '2026-10-06', 'status' => 1],
            ['date' => '2026-10-07', 'status' => 1],
            ['date' => '2026-10-07', 'status' => 1],
            ['date' => '2026-10-08', 'status' => 1],
            ['date' => '2026-10-08', 'status' => 1],
        ];

        $this->assertEquals(4.0, AttendanceDays::present($rows));
        $this->assertSame('4 days present', AttendanceDays::phrase(4));
    }

    public function test_one_present_session_is_a_half_day(): void
    {
        $rows = [
            ['date' => '2026-10-05', 'status' => 1],
            ['date' => '2026-10-05', 'status' => 0],
        ];

        $this->assertEquals(0.5, AttendanceDays::present($rows));
        $this->assertSame('½ day present', AttendanceDays::phrase(0.5));
        $this->assertSame('1 day present', AttendanceDays::phrase(1));
    }

    public function test_whole_days_and_a_half_day_read_as_a_mixed_total(): void
    {
        $rows = [
            ['date' => '2026-10-05', 'status' => 1],
            ['date' => '2026-10-05', 'status' => 1],
            ['date' => '2026-10-06', 'status' => 1],
            ['date' => '2026-10-06', 'status' => 1],
            ['date' => '2026-10-07', 'status' => 1],
            ['date' => '2026-10-07', 'status' => 1],
            ['date' => '2026-10-08', 'status' => 1],
            ['date' => '2026-10-08', 'status' => 1],
            ['date' => '2026-10-09', 'status' => 1],
        ];

        $this->assertEquals(4.5, AttendanceDays::present($rows));
        $this->assertSame('4½ days present', AttendanceDays::phrase(4.5));
        $this->assertSame('1½ days present', AttendanceDays::phrase(1.5));
    }
}
