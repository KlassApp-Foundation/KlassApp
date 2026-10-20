<?php

namespace Tests\Unit\Helpers;

use App\Helpers\WhatsAppPhoneHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Country-aware normalisation follows WhatsAppPhoneHelper::normalise(),
 * the canonical phone normaliser. normalise() itself stays the Uganda default
 * used by WhatsApp sending.
 */
class WhatsAppPhoneHelperCountryTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function ugandaLocalsThatMatchCanonicalNormalise(): array
    {
        return [
            'local 076' => ['0765274289'],
            'local 070' => ['0701234567'],
            'local with spaces' => ['0701 234 567'],
            'international without plus' => ['256701234567'],
            'international with plus' => ['+256701234567'],
            'national without trunk zero' => ['772000104'],
        ];
    }

    #[DataProvider('ugandaLocalsThatMatchCanonicalNormalise')]
    public function test_uganda_numbers_match_the_canonical_normalise(string $input): void
    {
        $this->assertSame(
            WhatsAppPhoneHelper::normalise($input),
            WhatsAppPhoneHelper::normaliseForCountry($input, '256', 9, 9),
        );
    }

    public function test_local_076_becomes_the_uganda_e164_number(): void
    {
        $this->assertSame('+256765274289', WhatsAppPhoneHelper::normaliseForCountry('0765274289', '256', 9, 9));
        $this->assertSame('+256765274289', WhatsAppPhoneHelper::normalise('0765274289'));
    }

    public function test_international_formats_keep_their_country_code(): void
    {
        $this->assertSame('+256765274289', WhatsAppPhoneHelper::normaliseForCountry('+256 765 274 289', '256', 9, 9));
        $this->assertSame('+256765274289', WhatsAppPhoneHelper::normaliseForCountry('+256-765-274-289', '256', 9, 9));
        $this->assertSame('+256765274289', WhatsAppPhoneHelper::normaliseForCountry('256765274289', '256', 9, 9));
        $this->assertSame('+256765274289', WhatsAppPhoneHelper::normaliseForCountry('256 765 274 289', '256', 9, 9));
        $this->assertSame('+256765274289', WhatsAppPhoneHelper::normaliseForCountry('256-765-274-289', '256', 9, 9));
        $this->assertSame('+254712345678', WhatsAppPhoneHelper::normaliseForCountry('+254 712 345 678', '256', 9, 9));
    }

    public function test_kenya_local_uses_the_supplied_calling_code(): void
    {
        $this->assertSame('+254712345678', WhatsAppPhoneHelper::normaliseForCountry('0712345678', '254', 9, 9));
        $this->assertSame('+254712345678', WhatsAppPhoneHelper::normaliseForCountry('254712345678', '254', 9, 9));
    }

    public function test_invalid_numbers_are_rejected(): void
    {
        $this->assertNull(WhatsAppPhoneHelper::normaliseForCountry('not-a-phone', '256', 9, 9));
        $this->assertNull(WhatsAppPhoneHelper::normaliseForCountry('12345', '256', 9, 9));
        $this->assertNull(WhatsAppPhoneHelper::normaliseForCountry('076527428', '256', 9, 9));
        $this->assertNull(WhatsAppPhoneHelper::normaliseForCountry('+++', '256', 9, 9));
    }

    public function test_uganda_example_matches_readable(): void
    {
        $this->assertSame('+256 765 274 289', WhatsAppPhoneHelper::example('256', 9));
        $this->assertSame(
            WhatsAppPhoneHelper::readable('+256765274289'),
            WhatsAppPhoneHelper::example('256', 9),
        );
    }
}
