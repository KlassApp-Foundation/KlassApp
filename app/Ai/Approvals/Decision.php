<?php

namespace App\Ai\Approvals;

/**
 * Polyfill of Laravel\Ai\Approvals\Decision (laravel/ai ^0.10).
 */
class Decision
{
    public function __construct(
        public readonly bool $approved,
        public readonly ?string $result = null,
        public readonly ?array $arguments = null,
    ) {}

    public static function approve(?array $arguments = null): self
    {
        return new self(true, null, $arguments);
    }

    public static function reject(?string $result = null): self
    {
        return new self(false, $result);
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    public function isRejected(): bool
    {
        return ! $this->approved;
    }
}
