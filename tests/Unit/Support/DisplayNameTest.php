<?php

namespace Tests\Unit\Support;

use App\Support\DisplayName;
use PHPUnit\Framework\TestCase;

class DisplayNameTest extends TestCase
{
    // -------------------------------------------------------------------------
    // proper()
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('properCasesProvider')]
    public function test_proper_converts_all_caps_names(string $input, string $expected): void
    {
        $this->assertSame($expected, DisplayName::proper($input));
    }

    /** @return array<string, array{string, string}> */
    public static function properCasesProvider(): array
    {
        return [
            'basic all-caps name'            => ['AMARA OPIO',          'Amara Opio'],
            'lowercase name'                 => ['beatrice atim',        'Beatrice Atim'],
            'mixed input'                    => ['comFORT NALULE',       'Comfort Nalule'],
            'single word'                    => ['HILLCREST',            'Hillcrest'],
            'empty string'                   => ['',                     ''],
            'null-like blank'                => ['   ',                  ''],

            // Mc/Mac prefixes
            'Mac prefix'                     => ['MACDONALD',            'MacDonald'],
            'Mc prefix'                      => ['MCDONALD',             'McDonald'],
            'Mc in middle'                   => ['PETER MCDONALD SMITH', 'Peter McDonald Smith'],

            // Known acronyms must stay uppercase
            'SST acronym'                    => ['SST',                  'SST'],
            'ICT acronym'                    => ['ICT',                  'ICT'],
            'PE acronym'                     => ['PE',                   'PE'],
            'RE acronym'                     => ['RE',                   'RE'],
            'CRE acronym'                    => ['CRE',                  'CRE'],
            'IRE acronym'                    => ['IRE',                  'IRE'],
            'PTC acronym'                    => ['PTC',                  'PTC'],
            'MTC acronym'                    => ['MTC',                  'MTC'],
            'HSC acronym'                    => ['HSC',                  'HSC'],
            'IST acronym'                    => ['IST',                  'IST'],

            // Acronyms embedded in a subject label
            'subject with SST acronym'       => ['PRIMARY FOUR SST',     'Primary Four SST'],
            'subject with ICT acronym'       => ['COMPUTER ICT STUDIES', 'Computer ICT Studies'],

            // Class labels stay intact (not acronyms, just title-cased)
            'class label P.4'                => ['P.4',                  'P.4'],
            'primary four label'             => ['PRIMARY FOUR',         'Primary Four'],

            // Apostrophes — MB_CASE_TITLE does not title-case after apostrophes
            "apostrophe in name"             => ["O'BRIEN",              "O'brien"],
        ];
    }

    public function test_proper_returns_empty_string_for_null(): void
    {
        $this->assertSame('', DisplayName::proper(null));
    }

    // -------------------------------------------------------------------------
    // title() – regression guard; must not break existing behaviour
    // -------------------------------------------------------------------------

    public function test_title_converts_all_caps_to_title_case(): void
    {
        $this->assertSame('Amara Opio', DisplayName::title('AMARA OPIO'));
    }

    public function test_title_returns_empty_for_null(): void
    {
        $this->assertSame('', DisplayName::title(null));
    }

    // -------------------------------------------------------------------------
    // initials() – regression guard
    // -------------------------------------------------------------------------

    public function test_initials_returns_first_two_letters(): void
    {
        $this->assertSame('AO', DisplayName::initials('Amara Opio'));
    }

    public function test_initials_returns_question_mark_for_empty(): void
    {
        $this->assertSame('?', DisplayName::initials(''));
    }
}
