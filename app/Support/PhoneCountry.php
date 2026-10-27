<?php

namespace App\Support;

use App\Helpers\WhatsAppPhoneHelper;
use App\Models\Country;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

/**
 * Country-aware phone normalisation for onboarding forms.
 *
 * Digit rules live in WhatsAppPhoneHelper::normaliseForCountry(), which follows
 * WhatsAppPhoneHelper::normalise() (the canonical phone normaliser). The calling
 * code comes from config/phone_countries.php for the invite's or school's
 * country, not from a Uganda-only branch.
 */
class PhoneCountry
{
    /**
     * @return array{code: string, min: int, max: int}|null
     */
    public static function spec(?string $countryName): ?array
    {
        $name = strtolower(trim((string) $countryName));
        if ($name === '') {
            return null;
        }

        $aliases = config('phone_countries.aliases', []);
        if (isset($aliases[$name])) {
            $name = strtolower((string) $aliases[$name]);
        }

        foreach (config('phone_countries.countries', []) as $label => $spec) {
            if (strtolower((string) $label) !== $name) {
                continue;
            }

            return [
                'code' => (string) $spec['code'],
                'min' => (int) $spec['min'],
                'max' => (int) $spec['max'],
            ];
        }

        try {
            $prefix = Country::query()->whereRaw('LOWER(name) = ?', [$name])->value('tel_prefix');
        } catch (\Throwable) {
            return null;
        }

        $code = preg_replace('/\D/', '', (string) $prefix) ?? '';
        if ($code === '') {
            return null;
        }

        return ['code' => $code, 'min' => 8, 'max' => 12];
    }

    /**
     * E.164 for any valid number. $countryName only supplies the region for a
     * number typed without a country code. A number that already has a country
     * code is accepted even when it is not that country.
     */
    public static function normalise(?string $input, ?string $countryName): ?string
    {
        $raw = trim((string) $input);
        if ($raw === '') {
            return null;
        }

        return WhatsAppPhoneHelper::toE164($raw, self::region($countryName));
    }

    /**
     * Stored values to treat as the same number: E.164, digits, and the
     * national form with and without a leading 0.
     *
     * @return list<string>
     */
    public static function keys(?string $input, ?string $countryName = 'Uganda'): array
    {
        $raw = trim((string) $input);
        if ($raw === '') {
            return [];
        }

        $e164 = self::normalise($raw, $countryName);
        if ($e164 === null) {
            return [$raw];
        }

        $util = PhoneNumberUtil::getInstance();

        try {
            $parsed = $util->parse($e164, self::region($countryName));
        } catch (NumberParseException) {
            return array_values(array_unique([$e164, $raw]));
        }

        $national = $util->getNationalSignificantNumber($parsed);

        return array_values(array_unique(array_filter([
            $e164,
            ltrim($e164, '+'),
            $national,
            '0'.$national,
            $raw,
        ])));
    }

    public static function dial(?string $countryName): string
    {
        $spec = self::spec($countryName);

        return $spec === null ? '+256' : '+'.$spec['code'];
    }

    public static function countryFromAuth(): string
    {
        $name = auth()->user()?->school?->registration_country;

        return is_string($name) && trim($name) !== '' ? trim($name) : 'Uganda';
    }

    public static function region(?string $countryName): string
    {
        $spec = self::spec($countryName);
        if ($spec !== null) {
            $region = PhoneNumberUtil::getInstance()->getRegionCodeForCountryCode((int) $spec['code']);
            if (is_string($region) && $region !== '' && $region !== 'ZZ') {
                return $region;
            }
        }

        return 'UG';
    }

    public static function example(?string $countryName): ?string
    {
        $spec = self::spec($countryName);
        if ($spec === null) {
            return null;
        }

        return WhatsAppPhoneHelper::example($spec['code'], $spec['max']);
    }

    public static function hint(?string $countryName, bool $whatsapp = true, ?string $label = null): string
    {
        $example = self::example($countryName);
        $label = $label ?? ($whatsapp ? 'Your WhatsApp number' : 'Your phone number');
        if ($example === null) {
            return $label.', including the country code.';
        }

        return $label.', e.g. '.$example;
    }

    public static function error(?string $countryName, bool $whatsapp = true): string
    {
        $example = self::example($countryName);
        $label = $whatsapp ? 'your WhatsApp number' : 'your phone number';
        if ($example === null) {
            return 'Enter '.$label.' with the country code.';
        }

        return 'Enter '.$label.' with the country code, e.g. '.$example.'.';
    }
}
