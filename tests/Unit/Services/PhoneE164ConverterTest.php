<?php

namespace Tests\Unit\Services;

use App\Services\Phones\PhoneE164Converter;
use Tests\TestCase;

class PhoneE164ConverterTest extends TestCase
{
    private function convert(?string $raw, ?string $country = 'Uganda'): array
    {
        return (new PhoneE164Converter)->convert($raw, $country);
    }

    public function test_a_local_number_gets_the_school_country_code(): void
    {
        $result = $this->convert('0772123456');
        $this->assertSame('changed', $result['status']);
        $this->assertSame('+256772123456', $result['value']);
    }

    public function test_spaces_and_dashes_are_removed(): void
    {
        $this->assertSame('+256772123456', $this->convert('0772 123 456')['value']);
        $this->assertSame('+256772123456', $this->convert('0772-123-456')['value']);
        $this->assertSame('+256772123456', $this->convert('+256 772 123 456')['value']);
    }

    public function test_an_e164_number_is_left_alone(): void
    {
        $result = $this->convert('+256772123456');
        $this->assertSame('already', $result['status']);
        $this->assertSame('+256772123456', $result['value']);
    }

    public function test_256_without_a_plus_is_completed(): void
    {
        $this->assertSame('+256772123456', $this->convert('256772123456')['value']);
        $this->assertSame('+256772123456', $this->convert('256 772 123 456')['value']);
    }

    public function test_another_country_uses_its_own_code(): void
    {
        $this->assertSame('+254712345678', $this->convert('0712345678', 'Kenya')['value']);
    }

    public function test_an_international_number_of_another_country_is_kept(): void
    {
        $result = $this->convert('+254712345678', 'Uganda');
        $this->assertSame('already', $result['status']);
    }

    public function test_things_that_do_not_parse_are_not_changed(): void
    {
        foreach (['abc', '12345', '0772', '000000000', '0', 'tel: none'] as $raw) {
            $result = $this->convert($raw);
            $this->assertSame('unparseable', $result['status'], $raw);
            $this->assertNull($result['value'], $raw);
        }
    }

    public function test_a_long_digit_string_with_no_country_sign_is_not_guessed(): void
    {
        // 12 digits that do not start with Uganda's 256: could be anyone's number.
        $result = $this->convert('447700900123');
        $this->assertSame('unparseable', $result['status']);
    }

    public function test_a_local_number_with_no_school_country_is_not_guessed(): void
    {
        $this->assertSame('unparseable', $this->convert('0772123456', null)['status']);
        $this->assertSame('already', $this->convert('+256772123456', null)['status']);
    }

    public function test_empty_values_are_skipped(): void
    {
        $this->assertSame('empty', $this->convert(null)['status']);
        $this->assertSame('empty', $this->convert('   ')['status']);
    }
}
