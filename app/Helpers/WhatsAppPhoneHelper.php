<?php
namespace App\Helpers;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class WhatsAppPhoneHelper
{
    /**
     * E.164. A number with no country code is read as Uganda. A number that
     * already has a country code is kept, including a US number.
     */
    public static function normalise(string $input): string
    {
        $e164 = self::toE164($input, 'UG');
        if ($e164 !== null) {
            return $e164;
        }

        $digits = preg_replace('/\D/', '', $input) ?? '';

        return $digits === '' ? $input : '+'.$digits;
    }

    public static function toE164(string $input, string $region): ?string
    {
        $util = PhoneNumberUtil::getInstance();

        try {
            $parsed = $util->parse($input, $region);
        } catch (NumberParseException) {
            return null;
        }

        if (! $util->isValidNumber($parsed)) {
            return null;
        }

        return $util->format($parsed, PhoneNumberFormat::E164);
    }

    /**
     * Same digit-stripping and trunk-prefix rule as {@see normalise()}, with the
     * calling code supplied by the caller. `normalise()` stays the Uganda-default
     * used by WhatsApp sending and is not changed.
     *
     * Local numbers (leading 0, or a national number without a country code) are
     * prefixed with $callingCode. Numbers that already include a country code
     * — with or without '+', or with a 00 international prefix — are kept.
     * Returns null when the input cannot be a plausible E.164 number.
     */
    public static function normaliseForCountry(string $input, string $callingCode, int $nationalMin = 8, int $nationalMax = 12): ?string
    {
        $callingCode = preg_replace('/\D/', '', $callingCode) ?? '';
        if ($callingCode === '') {
            return self::normaliseInternational($input);
        }

        [$digits, $international] = self::digits($input);
        if ($digits === null) {
            return null;
        }

        if ($international) {
            if (str_starts_with($digits, $callingCode)) {
                $national = substr($digits, strlen($callingCode));
                if (! self::nationalOk($national, $nationalMin, $nationalMax)) {
                    return null;
                }
            }

            return self::e164($digits);
        }

        if (str_starts_with($digits, '0')) {
            $national = substr($digits, 1);
            if (! self::nationalOk($national, $nationalMin, $nationalMax)) {
                return null;
            }

            return self::e164($callingCode.$national);
        }

        if (str_starts_with($digits, $callingCode)) {
            $national = substr($digits, strlen($callingCode));
            if (self::nationalOk($national, $nationalMin, $nationalMax)) {
                return self::e164($digits);
            }
        }

        if (self::nationalOk($digits, $nationalMin, $nationalMax)) {
            return self::e164($callingCode.$digits);
        }

        // A full international number typed without '+'.
        if (strlen($digits) >= 11 && strlen($digits) <= 15) {
            return self::e164($digits);
        }

        return null;
    }

    /**
     * A number that already carries its country code, when no country is known
     * to apply to a local number.
     */
    public static function normaliseInternational(string $input): ?string
    {
        [$digits, $international] = self::digits($input);
        if ($digits === null) {
            return null;
        }

        if (! $international && (strlen($digits) < 11 || strlen($digits) > 15)) {
            return null;
        }

        return self::e164($digits);
    }

    /**
     * Display example for a calling code. Uganda's 9-digit example matches
     * {@see readable()} ("+256 765 274 289").
     */
    public static function example(string $callingCode, int $nationalLength): string
    {
        $callingCode = preg_replace('/\D/', '', $callingCode) ?? '';
        $nationalLength = max(1, $nationalLength);
        $national = '';
        while (strlen($national) < $nationalLength) {
            $national .= '765274289';
        }
        $national = substr($national, 0, $nationalLength);

        if ($nationalLength % 3 === 1 && $nationalLength > 4) {
            $head = substr($national, 0, $nationalLength - 4);
            $groups = $head === '' ? [] : str_split($head, 3);
            $groups[] = substr($national, -4);
        } else {
            $groups = str_split($national, 3);
        }

        return '+'.$callingCode.' '.implode(' ', $groups);
    }

    /**
     * @return array{0: ?string, 1: bool}
     */
    private static function digits(string $input): array
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            return [null, false];
        }

        $international = str_starts_with($trimmed, '+');
        $digits = preg_replace('/\D/', '', $trimmed) ?? '';
        if ($digits === '') {
            return [null, false];
        }

        if (! $international && str_starts_with($digits, '00')) {
            $international = true;
            $digits = substr($digits, 2);
            if ($digits === '') {
                return [null, false];
            }
        }

        return [$digits, $international];
    }

    private static function nationalOk(string $national, int $nationalMin, int $nationalMax): bool
    {
        $length = strlen($national);
        if ($length < $nationalMin || $length > $nationalMax) {
            return false;
        }

        return preg_match('/^0+$/', $national) !== 1;
    }

    private static function e164(string $digits): ?string
    {
        if (preg_match('/^[1-9]\d{7,14}$/', $digits) !== 1) {
            return null;
        }

        return '+'.$digits;
    }

    /**
     * The sender of a Meta webhook message. Meta sends the full international
     * number as digits ("15550100004", "256770000001"), so it is kept as is;
     * only a local-looking number falls back to normalise(). Replies then go
     * back to the number that wrote, whatever its country.
     */
    public static function fromWaId(string $waId): string
    {
        $digits = preg_replace('/\D/', '', $waId);

        if ($digits === '' || str_starts_with($digits, '0') || strlen($digits) < 11) {
            return self::normalise($waId);
        }

        return '+' . $digits;
    }

    /** "+256 700 000 941" for a Uganda number; other countries grouped in threes. */
    // FLAGGED(product-decision): display grouping is special-cased for a 12-digit 256 number. see docs/onboarding-engine-plan.md
    public static function readable(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if ($digits === '') {
            return $phone;
        }
        if (str_starts_with($digits, '256') && strlen($digits) === 12) {
            return '+256 '.substr($digits, 3, 3).' '.substr($digits, 6, 3).' '.substr($digits, 9, 3);
        }

        $rest = $digits;
        $groups = [];
        while (strlen($rest) > 3) {
            array_unshift($groups, substr($rest, -3));
            $rest = substr($rest, 0, -3);
        }
        if ($rest !== '') {
            array_unshift($groups, $rest);
        }

        return '+'.implode(' ', $groups);
    }

    /**
     * A number safe to write to a log. The country calling code and the last
     * three digits stay; the rest is a fixed mask. "+256700000123" becomes
     * "+256*****123". Shorter input is fully masked.
     */
    public static function mask(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 6) {
            return '***';
        }

        return '+'.self::callingCode($digits).'*****'.substr($digits, -3);
    }

    private static function callingCode(string $digits): string
    {
        foreach (['256', '255', '254', '253', '252', '251', '250', '257', '260', '234', '233', '27', '44', '91', '1'] as $code) {
            $national = strlen($digits) - strlen($code);
            if (str_starts_with($digits, $code) && $national >= 4 && $national <= 12) {
                return $code;
            }
        }

        return substr($digits, 0, max(1, min(3, strlen($digits) - 3)));
    }

    // FLAGGED(product-decision): accepts only +256 numbers matching 07xx mobile prefixes. see docs/onboarding-engine-plan.md
    public static function validate(string $input): bool
    {
        return self::toE164($input, 'UG') !== null;
    }

    public static function formatMessage(string $studentName, string $title, array $rows, string $footer = ''): string
    {
        $message = "*{$title}*\n";
        $message .= "_{$studentName}_\n\n";

        foreach ($rows as $row) {
            $message .= $row . "\n";
        }

        if ($footer) {
            $message .= "\n_{$footer}_";
        }

        return $message;
    }
}
