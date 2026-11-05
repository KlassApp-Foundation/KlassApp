<?php

namespace App\Support;

use Throwable;

/**
 * Removes teacher and co-admin invite tokens from text that may be logged,
 * reported, or sent to analytics. The raw token is only for the invite email
 * and the invite page the teacher opens.
 */
class InviteTokenScrubber
{
    public const REDACTED = '[redacted]';

    public static function scrub(string $value): string
    {
        $scrubbed = preg_replace(
            '#(/invite/(?:teacher|co-admin)/)[A-Za-z0-9]+#',
            '$1'.self::REDACTED,
            $value
        );

        if (! is_string($scrubbed)) {
            $scrubbed = $value;
        }

        $scrubbed = preg_replace(
            '/((?:invite[_-]?token|token)=)[^&\s]+/i',
            '$1'.self::REDACTED,
            $scrubbed
        );

        return is_string($scrubbed) ? $scrubbed : $value;
    }

    public static function scrubMixed(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::scrub($value);
        }

        if ($value instanceof Throwable) {
            self::scrubThrowable($value);

            return $value;
        }

        if (! is_array($value)) {
            return $value;
        }

        $out = [];
        foreach ($value as $key => $item) {
            if (is_string($key) && self::isSecretKey($key)) {
                $out[$key] = self::REDACTED;

                continue;
            }

            $out[$key] = self::scrubMixed($item);
        }

        return $out;
    }

    public static function scrubThrowable(Throwable $exception): void
    {
        $current = $exception;
        $guard = 0;
        while ($current !== null && $guard < 8) {
            $message = $current->getMessage();
            $scrubbed = self::scrub($message);
            if ($scrubbed !== $message) {
                $property = new \ReflectionProperty($current, 'message');
                $property->setValue($current, $scrubbed);
            }
            $current = $current->getPrevious();
            $guard++;
        }
    }

    private static function isSecretKey(string $key): bool
    {
        return in_array(strtolower($key), [
            'token',
            'invite_token',
            'raw_token',
            'invite_url',
            'inviteurl',
        ], true);
    }
}
