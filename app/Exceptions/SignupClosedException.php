<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when self-serve sign-up is closed (config klassapp.signup_enabled
 * is false) and something still tries to create a school or school admin
 * through the sign-up bootstrap.
 */
class SignupClosedException extends RuntimeException
{
    public static function make(): self
    {
        return new self('Sign-up is closed. Request early access instead.');
    }
}
