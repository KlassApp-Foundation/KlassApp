<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * A sender address that Symfony will accept on the message.
 *
 * A blank MAIL_FROM_ADDRESS is present in the environment, so env()'s default
 * never applies. Sending with that blank value omits the From header and the
 * log mailer throws "An email must have a From or a Sender header."
 */
final class MailSender
{
    public const DEFAULT_ADDRESS = 'connect@klassapp.xyz';

    public const DEFAULT_NAME = 'KlassApp';

    private static bool $warned = false;

    /**
     * @return array{0: string, 1: string}
     */
    public static function resolve(): array
    {
        $address = trim((string) config('mail.from.address'));
        $name = trim((string) config('mail.from.name'));

        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            self::warn('MAIL_FROM_ADDRESS is missing or invalid; using the default sender.');
            $address = self::DEFAULT_ADDRESS;
        }

        if ($name === '') {
            $name = self::DEFAULT_NAME;
        }

        return [$address, $name];
    }

    public static function warnIfConfiguredAddressWasReplaced(): void
    {
        if (config('mail.from.used_default_address') === true) {
            self::warn('MAIL_FROM_ADDRESS is missing or invalid; using the default sender.');
        }
    }

    private static function warn(string $message): void
    {
        if (self::$warned) {
            return;
        }

        self::$warned = true;

        Log::warning($message, [
            'exception' => InvalidArgumentException::class,
            'message' => $message,
        ]);
    }
}
