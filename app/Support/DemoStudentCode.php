<?php

namespace App\Support;

/**
 * Short, stable student codes for the public demo follow-along page.
 *
 * A code is the number inside the student's existing KLS number written in
 * base 36 (digits and capital letters). KLS0420017 -> 420017 -> "9035".
 * The mapping is one to one, so a code always leads back to exactly one KLS
 * number and no new column is needed. Codes are read case-insensitively.
 */
final class DemoStudentCode
{
    public static function fromKls(?string $kls): ?string
    {
        $kls = strtoupper(trim((string) $kls));
        if (! preg_match('/^KLS(\d{7,8})$/', $kls, $m)) {
            return null;
        }

        $number = (int) $m[1];
        if ($number <= 0) {
            return null;
        }

        return strtoupper(base_convert((string) $number, 10, 36));
    }

    public static function toKls(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));
        if (! preg_match('/^[0-9A-Z]{1,6}$/', $code)) {
            return null;
        }

        $number = (int) base_convert(strtolower($code), 36, 10);
        if ($number <= 0 || $number > 99999999) {
            return null;
        }

        return 'KLS' . str_pad((string) $number, 7, '0', STR_PAD_LEFT);
    }
}
