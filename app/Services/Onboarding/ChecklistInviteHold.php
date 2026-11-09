<?php

namespace App\Services\Onboarding;

/**
 * While a checklist step is being applied, invite rows are still issued and
 * the email and WhatsApp sends are skipped.
 */
final class ChecklistInviteHold
{
    private static int $depth = 0;

    public static function active(): bool
    {
        return self::$depth > 0;
    }

    public static function hold(callable $callback): mixed
    {
        self::$depth++;
        try {
            return $callback();
        } finally {
            self::$depth--;
        }
    }
}
