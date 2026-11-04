<?php

namespace App\Support;

/**
 * Title case for showing a person's name. Stored values are left unchanged.
 */
final class DisplayName
{
    public static function title(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, 'UTF-8');
    }
}
