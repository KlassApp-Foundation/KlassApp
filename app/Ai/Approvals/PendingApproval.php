<?php

namespace App\Ai\Approvals;

/**
 * Polyfill of Laravel\Ai\Approvals\PendingApproval (laravel/ai ^0.10).
 */
class PendingApproval
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public function __construct(
        public readonly string $id,
        public readonly string $tool,
        public readonly array $arguments,
        public readonly ?string $reason = null,
    ) {}
}
