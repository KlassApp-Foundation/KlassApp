<?php

namespace Tests\Unit\Services;

use App\Services\People\PersonNameNormalizer;
use PHPUnit\Framework\TestCase;

class PersonNameNormalizerTest extends TestCase
{
    private function n(?string $first, ?string $last = ''): array
    {
        return (new PersonNameNormalizer)->normalize($first, $last);
    }

    public function test_a_full_name_in_first_name_is_split_and_proper_cased(): void
    {
        $r = $this->n('AGNES KALIISA', '');
        $this->assertSame('split', $r['status']);
        $this->assertSame(['Agnes', 'Kaliisa'], [$r['first'], $r['last']]);
    }

    public function test_mixed_case_full_name_in_first_name_is_split_too(): void
    {
        $r = $this->n('Ruth Okoro', null);
        $this->assertSame('split', $r['status']);
        $this->assertSame(['Ruth', 'Okoro'], [$r['first'], $r['last']]);
    }

    public function test_all_caps_first_and_last_are_proper_cased(): void
    {
        $r = $this->n('AGNES', 'KALIISA');
        $this->assertSame('cased', $r['status']);
        $this->assertSame(['Agnes', 'Kaliisa'], [$r['first'], $r['last']]);
    }

    public function test_only_the_shouting_part_is_changed(): void
    {
        $r = $this->n('Agnes', 'KALIISA');
        $this->assertSame(['Agnes', 'Kaliisa'], [$r['first'], $r['last']]);
    }

    public function test_hyphens_and_apostrophes_get_capitals(): void
    {
        $this->assertSame('Kato-Nakamya', $this->n('JOHN', 'KATO-NAKAMYA')['last']);
        $this->assertSame("O'Brien", $this->n('SEAN', "O'BRIEN")['last']);
        $this->assertSame(['Mary-Anne', 'Okello'], [$this->n('MARY-ANNE OKELLO', '')['first'], $this->n('MARY-ANNE OKELLO', '')['last']]);
    }

    public function test_particles_stay_lower_case_after_the_first_word_of_a_surname(): void
    {
        $this->assertSame('Van der Merwe', $this->n('PIETER', 'VAN DER MERWE')['last']);
    }

    public function test_mc_gets_a_second_capital_but_mac_names_are_left_simple(): void
    {
        $this->assertSame('McDonald', $this->n('ANN', 'MCDONALD')['last']);
        $this->assertSame('Macharia', $this->n('PETER', 'MACHARIA')['last']);
    }

    public function test_one_word_is_ambiguous_and_unchanged(): void
    {
        $r = $this->n('AGNES', '');
        $this->assertSame('ambiguous', $r['status']);
        $this->assertSame('AGNES', $r['first']);
    }

    public function test_three_words_with_no_last_name_are_ambiguous(): void
    {
        $r = $this->n('MARY JANE NAKATO', '');
        $this->assertSame('ambiguous', $r['status']);
        $this->assertSame('MARY JANE NAKATO', $r['first']);
        $this->assertSame('', $r['last']);
    }

    public function test_a_name_that_is_already_fine_is_left_alone(): void
    {
        $this->assertSame('ok', $this->n('Agnes', 'Kaliisa')['status']);
        $this->assertSame('ok', $this->n('J', 'Kaliisa')['status']);
    }

    public function test_extra_spaces_are_squashed(): void
    {
        $r = $this->n('  AGNES   KALIISA ', '');
        $this->assertSame(['Agnes', 'Kaliisa'], [$r['first'], $r['last']]);
    }
}
