<?php

namespace App\Support;

/**
 * Title case for showing a person's name. Stored values are left unchanged.
 */
final class DisplayName
{
    /**
     * Acronyms that must stay fully uppercase even after title-casing.
     *
     * @var list<string>
     */
    private const ACRONYMS = ['SST', 'ICT', 'PE', 'RE', 'CRE', 'IRE', 'PTC', 'MTC', 'HSC', 'IST'];

    /**
     * Convert a name or label to proper display case.
     *
     * Unlike title(), this method additionally:
     * - restores Mc/Mac prefixes (McDonald → McDonald)
     * - preserves known acronyms (SST, ICT, PE …)
     */
    public static function proper(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $titled = mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        // Fix Mc/Mac prefixes: "Mcdonald" → "McDonald"
        $titled = (string) preg_replace_callback(
            '/\b(Mc|Mac)([a-z])/u',
            static fn (array $m): string => $m[1] . mb_strtoupper($m[2], 'UTF-8'),
            $titled
        );

        // Split on whitespace, preserving delimiter tokens so we can rejoin.
        $parts = preg_split('/(\s+)/u', $titled, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$titled];

        return implode('', array_map(static function (string $part): string {
            return in_array(mb_strtoupper($part, 'UTF-8'), self::ACRONYMS, true)
                ? mb_strtoupper($part, 'UTF-8')
                : $part;
        }, $parts));
    }

    public static function title(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, 'UTF-8');
    }

    public static function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $letters = '';
        foreach (array_slice(array_values(array_filter($parts)), 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $letters !== '' ? $letters : '?';
    }
}
