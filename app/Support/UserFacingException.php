<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Turns a caught exception into a sentence that can be shown on a page.
 * Stack traces, SQL, and credential-shaped text stay in the log.
 */
class UserFacingException
{
    public static function message(Throwable $e, string $fallback): string
    {
        if ($e instanceof ValidationException) {
            $detail = collect($e->errors())->flatten()->first();
            if (is_string($detail) && self::isSafe($detail)) {
                return $detail;
            }
        }

        $message = trim($e->getMessage());
        if (self::isSafe($message)) {
            return $message;
        }

        return $fallback;
    }

    public static function isSafe(string $message): bool
    {
        if ($message === '' || strlen($message) > 300) {
            return false;
        }

        if (str_contains($message, "\n") || str_contains($message, "\r")) {
            return false;
        }

        return preg_match(
            '/SQLSTATE|stack trace|#\d+ |vendor[\/\\\\]|password|secret|token|api[_-]?key|PDOException|Connection:|mysql:|pgsql:/i',
            $message,
        ) !== 1;
    }
}
