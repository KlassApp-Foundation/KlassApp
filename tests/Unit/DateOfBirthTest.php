<?php

namespace Tests\Unit;

use App\Support\DateOfBirth;
use PHPUnit\Framework\TestCase;

class DateOfBirthTest extends TestCase
{
    public function test_missing_values_are_not_recorded(): void
    {
        foreach ([null, '', '   ', '0000-00-00', '1970-01-01', '1970-01-01 00:00:00', 'not-a-date'] as $missing) {
            $this->assertFalse(DateOfBirth::isRecorded($missing), var_export($missing, true));
            $this->assertSame('Not recorded', DateOfBirth::format($missing));
            $this->assertNull(DateOfBirth::formatOrNull($missing));
            $this->assertNull(DateOfBirth::age($missing));
        }
    }

    public function test_format_accepts_placeholder_and_format(): void
    {
        $this->assertSame('', DateOfBirth::format(null, 'd-m-Y', ''));
        $this->assertSame('09-03-2014', DateOfBirth::format('2014-03-09'));
        $this->assertSame('09 Mar 2014', DateOfBirth::format('2014-03-09', 'd M Y'));
        $this->assertSame('2014-03-09', DateOfBirth::format(new \DateTimeImmutable('2014-03-09'), 'Y-m-d'));
    }

    public function test_age_is_calendar_year_difference_like_existing_resources(): void
    {
        $this->assertSame((int) date('Y') - 2014, DateOfBirth::age('2014-03-09'));
    }

    public function test_real_1970_dates_other_than_epoch_are_kept(): void
    {
        $this->assertTrue(DateOfBirth::isRecorded('1970-01-02'));
    }
}
