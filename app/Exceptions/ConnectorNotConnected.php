<?php

namespace App\Exceptions;

use RuntimeException;

class ConnectorNotConnected extends RuntimeException
{
    public function __construct(
        public readonly int $schoolId,
        public readonly string $connectorType,
    ) {
        parent::__construct(
            "School {$schoolId} has no active {$connectorType} connector. "
            .'Connect it in Settings → Integrations.'
        );
    }
}
