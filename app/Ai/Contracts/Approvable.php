<?php

namespace App\Ai\Contracts;

use Laravel\Ai\Tools\Request;

/**
 * Polyfill of Laravel\Ai\Contracts\Approvable (laravel/ai ^0.10).
 *
 * Installed package is laravel/ai 0.9 which lacks Approvable. This mirror
 * matches the 0.10 API so Plan tools can use the Tool/Approvable pattern now.
 * On bump to ^0.10, swap imports to Laravel\Ai\Contracts\Approvable.
 *
 * @see https://github.com/laravel/docs/blob/13.x/ai-sdk.md#human-tool-approval
 */
interface Approvable
{
    public function requireApproval(?string $reason = null): static;

    public function withoutApproval(): static;

    public function shouldRequestApproval(Request $request): ?\App\Ai\Approvals\Approval;
}
