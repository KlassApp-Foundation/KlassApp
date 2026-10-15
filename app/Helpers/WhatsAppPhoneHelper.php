<?php
namespace App\Helpers;

class WhatsAppPhoneHelper
{
    public static function normalise(string $input): string
    {
        $digits = preg_replace('/\D/', '', $input);

        if (str_starts_with($digits, '0')) {
            $digits = '256' . substr($digits, 1);
        }

        if (!str_starts_with($digits, '256')) {
            $digits = '256' . $digits;
        }

        return '+' . $digits;
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

    public static function validate(string $input): bool
    {
        $normalised = self::normalise($input);
        return preg_match('/^\+256(7[0578]\d{7})$/', $normalised) === 1;
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
