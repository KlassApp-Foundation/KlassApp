<?php

namespace App\Services\Toshi;

use Illuminate\Http\Client\RequestException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

/**
 * One reading of a provider rate-limit response.
 * Callers show the admin message and stop. They do not retry.
 */
class ToshiRateLimit
{
    public static function isLimited(Throwable $error): bool
    {
        $current = $error;
        while ($current instanceof Throwable) {
            if ($current instanceof RateLimitedException) {
                return true;
            }

            if ($current instanceof RequestException && $current->response?->status() === 429) {
                return true;
            }

            $message = strtolower($current->getMessage());
            if (str_contains($message, 'resource_exhausted') || str_contains($message, 'rate limit')) {
                return true;
            }

            $current = $current->getPrevious();
        }

        return false;
    }

    public static function adminMessage(): string
    {
        return 'The free model limit was hit. Wait before trying again. Nothing was written, and this was not retried.';
    }
}
