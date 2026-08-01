<?php

namespace App\Ai\Concerns;

use App\Ai\Approvals\Approval;
use Laravel\Ai\Tools\Request;

/**
 * Polyfill of Laravel\Ai\Concerns\InteractsWithApprovals (laravel/ai ^0.10).
 */
trait InteractsWithApprovals
{
    protected Approval|bool|null $approvalRequirement = null;

    public function requireApproval(?string $reason = null): static
    {
        $this->approvalRequirement = Approval::required($reason);

        return $this;
    }

    public function withoutApproval(): static
    {
        $this->approvalRequirement = false;

        return $this;
    }

    public function shouldRequestApproval(Request $request): ?Approval
    {
        $result = $this->approvalRequirement ?? $this->needsApproval($request);

        return match (true) {
            $result === false => null,
            $result === true => Approval::required(),
            default => $result,
        };
    }

    protected function needsApproval(Request $request): Approval|bool
    {
        return true;
    }
}
