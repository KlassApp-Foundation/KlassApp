<?php

namespace App\Services\Google;

use RuntimeException;

class GoogleApiException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly ?string $googleError = null,
    ) {
        parent::__construct($message);
    }
}
