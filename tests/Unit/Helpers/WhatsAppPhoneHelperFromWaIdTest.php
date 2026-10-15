<?php

namespace Tests\Unit\Helpers;

use App\Helpers\WhatsAppPhoneHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The sender of an inbound Meta webhook message ("from"), as stored and
 * answered. Uganda numbers come out exactly as normalise() makes them; a
 * full international number from another country keeps its own country code.
 */
class WhatsAppPhoneHelperFromWaIdTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function senders(): array
    {
        return [
            'local with a leading 0' => ['0772000104', '+256772000104'],
            '256 with no plus' => ['256772000104', '+256772000104'],
            'already +256' => ['+256772000104', '+256772000104'],
            'local without the 0' => ['772000104', '+256772000104'],
            'non-Uganda, digits as Meta sends them' => ['15550100004', '+15550100004'],
        ];
    }

    #[DataProvider('senders')]
    public function test_inbound_sender_number_is_kept_in_its_own_country(string $from, string $expected): void
    {
        $this->assertSame($expected, WhatsAppPhoneHelper::fromWaId($from));
    }

    /** @return array<string, array{string}> */
    public static function ugandaSenders(): array
    {
        return array_map(fn (array $case) => [$case[0]], array_filter(self::senders(), fn (array $case) => str_starts_with($case[1], '+256')));
    }

    #[DataProvider('ugandaSenders')]
    public function test_uganda_senders_are_unchanged_from_normalise(string $from): void
    {
        $this->assertSame(WhatsAppPhoneHelper::normalise($from), WhatsAppPhoneHelper::fromWaId($from));
    }
}
