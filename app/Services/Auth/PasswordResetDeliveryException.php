<?php

namespace App\Services\Auth;

use RuntimeException;
use Throwable;

/**
 * A password-reset code was not emailed. The user-facing sentence stays
 * generic; the exception class and message are logged by the issuer.
 */
class PasswordResetDeliveryException extends RuntimeException
{
    public const TEMPORARY = 'temporary';

    public const CONFIGURATION = 'configuration';

    public function __construct(
        public readonly string $kind,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function userMessage(): string
    {
        if ($this->kind === self::CONFIGURATION) {
            return 'Password reset email is not set up. Please contact your school.';
        }

        return 'We could not send a reset code right now. Please try again shortly.';
    }
}
