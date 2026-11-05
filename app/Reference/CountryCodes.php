<?php

namespace App\Reference;

/**
 * Country short names, ISO codes, and dialling prefixes.
 *
 * These triples are the same values as PR #1249
 * (branch cursor/t064-country-codes-c495, migration
 * 2026_10_27_120000_correct_country_codes_by_name.php):
 *   corrected()    === that migration's codes()
 *   previousSeed() === that migration's previousCodes()
 *
 * This class is the seeder's source of truth so a fresh
 * migrate + seed writes the corrected codes directly.
 * #1249's migration remains the correction for databases
 * that were already seeded with previousSeed(). It does
 * not live in this branch. Merge either PR first; keep
 * these two arrays identical to that migration when the
 * other branch lands.
 *
 * @phpstan-type CodeTriple array{short_name: string, iso_code: string, tel_prefix: string}
 */
final class CountryCodes
{
    /**
     * @return array<string, CodeTriple>
     */
    public static function corrected(): array
    {
        return [
            'Uganda' => ['short_name' => 'UG', 'iso_code' => 'UGA', 'tel_prefix' => '256'],
            'Kenya' => ['short_name' => 'KE', 'iso_code' => 'KEN', 'tel_prefix' => '254'],
            'Tanzania' => ['short_name' => 'TZ', 'iso_code' => 'TZA', 'tel_prefix' => '255'],
            'Rwanda' => ['short_name' => 'RW', 'iso_code' => 'RWA', 'tel_prefix' => '250'],
            'Burundi' => ['short_name' => 'BI', 'iso_code' => 'BDI', 'tel_prefix' => '257'],
            'South Sudan' => ['short_name' => 'SS', 'iso_code' => 'SSD', 'tel_prefix' => '211'],
            'DRC' => ['short_name' => 'CD', 'iso_code' => 'COD', 'tel_prefix' => '243'],
            'Nigeria' => ['short_name' => 'NG', 'iso_code' => 'NGA', 'tel_prefix' => '234'],
            'Egypt' => ['short_name' => 'EG', 'iso_code' => 'EGY', 'tel_prefix' => '20'],
            'Other' => ['short_name' => 'OT', 'iso_code' => 'OTH', 'tel_prefix' => ''],
        ];
    }

    /**
     * The scrambled values the original country seeder wrote.
     * #1249's migration replaces these. A row that still holds
     * one of these triples is a row that correction would change.
     *
     * @return array<string, CodeTriple>
     */
    public static function previousSeed(): array
    {
        return [
            'Uganda' => ['short_name' => 'AF', 'iso_code' => 'AFG', 'tel_prefix' => '256'],
            'Kenya' => ['short_name' => 'AL', 'iso_code' => 'ALB', 'tel_prefix' => '355'],
            'Tanzania' => ['short_name' => 'AR', 'iso_code' => 'ARG', 'tel_prefix' => '54'],
            'Rwanda' => ['short_name' => 'AU', 'iso_code' => 'AUS', 'tel_prefix' => '61'],
            'Burundi' => ['short_name' => 'CH', 'iso_code' => 'CHN', 'tel_prefix' => '86'],
            'South Sudan' => ['short_name' => 'IN', 'iso_code' => 'IND', 'tel_prefix' => '91'],
            'DRC' => ['short_name' => 'MA', 'iso_code' => 'MYS', 'tel_prefix' => '60'],
            'Nigeria' => ['short_name' => 'SW', 'iso_code' => 'CHE', 'tel_prefix' => '41'],
            'Egypt' => ['short_name' => 'EG', 'iso_code' => 'EGY', 'tel_prefix' => '20'],
            'Other' => ['short_name' => 'OT', 'iso_code' => 'OT', 'tel_prefix' => '20'],
        ];
    }

    /**
     * @param  object|array<string, mixed>  $row
     * @return array{0: string, 1: string, 2: string}
     */
    public static function triple(object|array $row): array
    {
        $short = is_array($row) ? ($row['short_name'] ?? '') : ($row->short_name ?? '');
        $iso = is_array($row) ? ($row['iso_code'] ?? '') : ($row->iso_code ?? '');
        $tel = is_array($row) ? ($row['tel_prefix'] ?? '') : ($row->tel_prefix ?? '');

        return [(string) $short, (string) $iso, (string) $tel];
    }

    /**
     * @param  CodeTriple  $spec
     * @return array{0: string, 1: string, 2: string}
     */
    public static function specTriple(array $spec): array
    {
        return [$spec['short_name'], $spec['iso_code'], $spec['tel_prefix']];
    }
}
