<?php

namespace App\Services\Phones;

use App\Helpers\WhatsAppPhoneHelper;
use App\Support\PhoneCountry;

/**
 * Turns one stored phone value into E.164, using the school's country for a local number.
 *
 * It reuses the app's own normaliser (PhoneCountry / WhatsAppPhoneHelper) so there is one rule for
 * what a phone number is. On top of that it is stricter than the sign-up forms, because this runs
 * over stored data: it never invents a country. A number with no "+", no "00", no trunk "0" and not
 * starting with the school's calling code is only accepted when it is a plausible national number
 * of that country; anything else is reported, not changed.
 */
final class PhoneE164Converter
{
    public const EMPTY = 'empty';

    public const ALREADY = 'already';

    public const CHANGED = 'changed';

    public const UNPARSEABLE = 'unparseable';

    /**
     * @return array{status: string, value: ?string, reason: ?string}
     */
    public function convert(?string $raw, ?string $countryName): array
    {
        $raw = (string) $raw;
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return ['status' => self::EMPTY, 'value' => null, 'reason' => null];
        }

        $digits = preg_replace('/\D/', '', $trimmed) ?? '';
        if ($digits === '') {
            return $this->fail('no digits');
        }

        $explicit = str_starts_with($trimmed, '+') || str_starts_with($digits, '00');
        $spec = PhoneCountry::spec($countryName);

        if ($spec === null && ! $explicit) {
            return $this->fail('the school has no country, so a local number cannot be completed');
        }

        if ($spec !== null && ! $explicit) {
            $code = $spec['code'];
            $startsWithCode = str_starts_with($digits, $code) && $this->lengthOk(substr($digits, strlen($code)), $spec);
            $trunk = str_starts_with($digits, '0');
            $bareNational = $this->lengthOk($digits, $spec);
            if (! $trunk && ! $startsWithCode && ! $bareNational) {
                return $this->fail('cannot tell which country this number is for');
            }
        }

        $normalised = PhoneCountry::normalise($trimmed, $countryName);
        if ($normalised === null) {
            return $this->fail('not a valid phone number');
        }

        if ($trimmed === $normalised) {
            return ['status' => self::ALREADY, 'value' => $normalised, 'reason' => null];
        }

        return ['status' => self::CHANGED, 'value' => $normalised, 'reason' => null];
    }

    /**
     * @param  array{code: string, min: int, max: int}  $spec
     */
    private function lengthOk(string $national, array $spec): bool
    {
        $length = strlen($national);

        return $length >= $spec['min'] && $length <= $spec['max'] && preg_match('/^0+$/', $national) !== 1;
    }

    /**
     * @return array{status: string, value: ?string, reason: ?string}
     */
    private function fail(string $reason): array
    {
        return ['status' => self::UNPARSEABLE, 'value' => null, 'reason' => $reason];
    }
}
