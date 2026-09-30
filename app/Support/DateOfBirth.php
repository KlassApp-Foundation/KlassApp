<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;
use Throwable;

/**
 * One place that decides whether a stored date of birth is a real date and how to print it.
 *
 * `date('d-m-Y', strtotime(null))` returns 01-01-1970, which read as a real birthday on
 * profile pages and exports. Blank, unparseable, zero and Unix-epoch values are all "not
 * recorded" — a school never has a real pupil or staff member born on 1970-01-01.
 */
final class DateOfBirth
{
    public const NOT_RECORDED = 'Not recorded';

    public static function parse(mixed $value): ?Carbon
    {
        if ($value instanceof DateTimeInterface) {
            $date = Carbon::instance($value);
        } else {
            $raw = trim((string) $value);

            if ($raw === '' || str_starts_with($raw, '0000-00-00')) {
                return null;
            }

            try {
                $date = Carbon::parse($raw);
            } catch (Throwable) {
                return null;
            }
        }

        // Unix epoch is what strtotime(null) / a cast of an empty value produces.
        if ($date->format('Y-m-d') === '1970-01-01') {
            return null;
        }

        return $date;
    }

    public static function isRecorded(mixed $value): bool
    {
        return self::parse($value) !== null;
    }

    /** Formatted date, or $placeholder ("Not recorded" by default; pass '' for CSV cells). */
    public static function format(mixed $value, string $format = 'd-m-Y', string $placeholder = self::NOT_RECORDED): string
    {
        $date = self::parse($value);

        return $date === null ? $placeholder : $date->format($format);
    }

    /** Formatted date or null — for JSON payloads the front end renders itself. */
    public static function formatOrNull(mixed $value, string $format = 'd-m-Y'): ?string
    {
        return self::parse($value)?->format($format);
    }

    /** Calendar-year difference (matches the existing `date('Y') - date('Y', dob)` callers). */
    public static function age(mixed $value): ?int
    {
        $date = self::parse($value);

        return $date === null ? null : (int) date('Y') - (int) $date->format('Y');
    }
}
