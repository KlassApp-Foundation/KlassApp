<?php

namespace App\Ai\Approvals;

/**
 * Polyfill of Laravel\Ai\Approvals\Approval (laravel/ai ^0.10).
 */
class Approval
{
    public function __construct(public readonly ?string $reason = null) {}

    public static function required(?string $reason = null): self
    {
        return new self($reason);
    }
}
