<?php

namespace App\Services\Toshi;

use App\Models\User;

/**
 * Proof that a person confirmed one specific write.
 *
 * Passed explicitly from the confirming call site into the tool that performs
 * the write. It replaces ToshiActionService::$bypassConfirm, which was a
 * process-wide mutable flag: any code path that set it true disabled the
 * confirmation gate for everything that ran afterwards in the same request,
 * including code that never saw a confirmation.
 *
 * A context is bound to one tool and one argument set. It carries the actor and
 * school so the write can be recorded against the person who approved it.
 */
final class WriteContext
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __construct(
        public readonly bool $confirmed = false,
        public readonly string $tool = '',
        public readonly array $args = [],
        public readonly ?int $actorId = null,
        public readonly ?int $schoolId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     */
    public static function confirmed(string $tool, array $args, ?User $actor = null): self
    {
        return new self(
            confirmed: true,
            tool: $tool,
            args: $args,
            actorId: $actor?->id,
            schoolId: $actor?->school_id !== null ? (int) $actor->school_id : null,
        );
    }

    public static function none(): self
    {
        return new self();
    }

    /**
     * Stable hash of the exact arguments that were shown in the preview and
     * confirmed. Two confirmations of the same write share a hash; a changed
     * argument set does not.
     */
    public function argsHash(): string
    {
        return hash('sha256', $this->tool.'|'.json_encode($this->args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
