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
     * Longest phone string a form, import, or API field may accept.
     * E.164 is at most 16 characters; 20 leaves room for spaces and punctuation.
     */
    public const MAX_INPUT_LENGTH = 20;

    /**
     * @return array{code: string, min: int, max: int}|null
     */
    public static function spec(?string $countryName): ?array
    {
        $name = self::canonicalName($countryName);
        if ($name === null) {
            return null;
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

        $iso = self::directIsoRegion($countryName);
        if ($iso !== null) {
            $code = PhoneNumberUtil::getInstance()->getCountryCodeForRegion($iso);
            foreach (config('phone_countries.countries', []) as $label => $listed) {
                if (self::regionForCallingCode((int) $listed['code'], (string) $label) !== $iso) {
                    continue;
                }

                return [
                    'code' => (string) $listed['code'],
                    'min' => (int) $listed['min'],
                    'max' => (int) $listed['max'],
                ];
            }

            if ($code > 0) {
                return ['code' => (string) $code, 'min' => 8, 'max' => 12];
            }
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
        if ($raw === '' || strlen($raw) > self::MAX_INPUT_LENGTH) {
            return null;
        }

        return WhatsAppPhoneHelper::toE164($raw, self::region($countryName));
    }

    /**
     * Unambiguous forms of one full international number: E.164 and its digits.
     * National forms are not included; those collide across countries.
     *
     * @return list<string>
     */
    public static function internationalKeys(?string $input, ?string $countryName = 'Uganda'): array
    {
        $e164 = self::normalise($input, $countryName);
        if ($e164 === null) {
            return [];
        }

        return array_values(array_unique([$e164, ltrim($e164, '+')]));
    }

    /**
     * Legacy local forms (712345678 and 0712345678). Safe only together with a
     * same-country constraint: the same digits belong to more than one country.
     *
     * @return list<string>
     */
    public static function legacyLocalKeys(?string $input, ?string $countryName = 'Uganda'): array
    {
        $e164 = self::normalise($input, $countryName);
        if ($e164 === null) {
            return [];
        }

        try {
            $parsed = PhoneNumberUtil::getInstance()->parse($e164, self::regionOfE164($e164));
        } catch (NumberParseException) {
            return [];
        }

        $national = PhoneNumberUtil::getInstance()->getNationalSignificantNumber($parsed);
        if ($national === '') {
            return [];
        }

        return array_values(array_unique([$national, '0'.$national]));
    }

    /**
     * @return list<string>
     */
    public static function keys(?string $input, ?string $countryName = 'Uganda'): array
    {
        return self::internationalKeys($input, $countryName);
    }

    /**
     * Match $column to the same full international number. Legacy local values
     * match only when the stored row's country is the number's country.
     * $schoolId, when set, limits the whole lookup to that school.
     */
    public static function applyLookup(
        $query,
        string $column,
        ?string $raw,
        ?string $countryName = 'Uganda',
        ?int $schoolId = null,
        ?string $countryColumn = null,
        string $schoolRelation = 'school',
    ): void {
        $international = self::internationalKeys($raw, $countryName);
        if ($international === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $legacy = self::legacyLocalKeys($raw, $countryName);
        $region = self::regionOfE164($international[0]);

        $query->where(function ($outer) use ($column, $international, $legacy, $region, $countryColumn, $schoolRelation) {
            $outer->whereIn($column, $international);
            if ($legacy === []) {
                return;
            }

            $outer->orWhere(function ($local) use ($column, $legacy, $region, $countryColumn, $schoolRelation) {
                $local->whereIn($column, $legacy);
                self::constrainSameCountry($local, $region, $countryColumn, $schoolRelation);
            });
        });

        if ($schoolId !== null) {
            $query->where($query->getModel()->getTable().'.school_id', $schoolId);
        }
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
        $direct = self::directIsoRegion($countryName);
        if ($direct !== null) {
            return $direct;
        }

        $spec = self::spec($countryName);
        if ($spec !== null) {
            $region = self::regionForCallingCode((int) $spec['code'], $countryName);
            if ($region !== null) {
                return $region;
            }
        }

        return 'UG';
    }

    public static function regionOfE164(string $e164): string
    {
        try {
            $parsed = PhoneNumberUtil::getInstance()->parse($e164, 'UG');
        } catch (NumberParseException) {
            return 'UG';
        }

        $region = PhoneNumberUtil::getInstance()->getRegionCodeForNumber($parsed);
        if (is_string($region) && $region !== '' && $region !== 'ZZ') {
            return $region;
        }

        return 'UG';
    }

    /**
     * Names and codes a school may have stored for this ISO region.
     *
     * @return list<string>
     */
    public static function labelsForRegion(string $region): array
    {
        $region = strtoupper($region);
        $labels = [strtolower($region)];

        foreach (config('phone_countries.countries', []) as $label => $spec) {
            if (self::regionForCallingCode((int) $spec['code'], (string) $label) === $region) {
                $labels[] = strtolower((string) $label);
            }
        }

        foreach (config('phone_countries.aliases', []) as $alias => $target) {
            if (in_array(strtolower((string) $target), $labels, true)) {
                $labels[] = strtolower((string) $alias);
            }
        }

        return array_values(array_unique($labels));
    }

    private static function canonicalName(?string $countryName): ?string
    {
        $name = strtolower(trim((string) $countryName));
        if ($name === '') {
            return null;
        }

        $aliases = config('phone_countries.aliases', []);
        if (isset($aliases[$name])) {
            $name = strtolower((string) $aliases[$name]);
        }

        return $name;
    }

    private static function directIsoRegion(?string $countryName): ?string
    {
        $name = self::canonicalName($countryName);
        if ($name === null || preg_match('/^[a-z]{2}$/', $name) !== 1) {
            return null;
        }

        $region = strtoupper($name);
        $code = PhoneNumberUtil::getInstance()->getCountryCodeForRegion($region);

        return $code > 0 ? $region : null;
    }

    private static function regionForCallingCode(int $code, ?string $countryName): ?string
    {
        $name = strtolower(trim((string) $countryName));
        if ($code === 1) {
            return str_contains($name, 'canada') ? 'CA' : 'US';
        }

        $region = PhoneNumberUtil::getInstance()->getRegionCodeForCountryCode($code);
        if (! is_string($region) || $region === '' || $region === 'ZZ') {
            return null;
        }

        return $region;
    }

    private static function constrainSameCountry($query, string $region, ?string $countryColumn, string $schoolRelation): void
    {
        $labels = self::labelsForRegion($region);
        $apply = function ($countryQuery) use ($labels, $region) {
            $countryQuery->where(function ($match) use ($labels, $region) {
                foreach ($labels as $label) {
                    $match->orWhereRaw('LOWER(TRIM(registration_country)) = ?', [$label]);
                }
                if ($region === 'UG') {
                    $match->orWhereNull('registration_country')
                        ->orWhereRaw("TRIM(COALESCE(registration_country, '')) = ''");
                }
            });
        };

        if ($countryColumn !== null) {
            $apply($query);

            return;
        }

        $query->whereHas($schoolRelation, $apply);
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
