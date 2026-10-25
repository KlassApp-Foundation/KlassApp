<?php

namespace App\Support;

use App\Helpers\WhatsAppPhoneHelper;
use App\Models\Country;

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

    // FLAGGED(product-decision): known countries come from a fixed list, not libphonenumber. see docs/onboarding-engine-plan.md
    public static function normalise(?string $input, ?string $countryName): ?string
    {
        $raw = trim((string) $input);
        if ($raw === '') {
            return null;
        }

        $spec = self::spec($countryName);
        if ($spec === null) {
            return WhatsAppPhoneHelper::normaliseInternational($raw);
        }

        return WhatsAppPhoneHelper::normaliseForCountry($raw, $spec['code'], $spec['min'], $spec['max']);
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
