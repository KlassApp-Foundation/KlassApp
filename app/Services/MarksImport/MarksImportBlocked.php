<?php

namespace App\Services\MarksImport;

use RuntimeException;

/**
 * The import cannot be saved as a whole (locked submission, missing correction
 * reason, class assessed by ratings, ...). The message is safe to show the user.
 */
class MarksImportBlocked extends RuntimeException
{
    public function __construct(public readonly string $reasonCode, string $message)
    {
        parent::__construct($message);
    }
}
