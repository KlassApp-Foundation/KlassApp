<?php

namespace App\Services\Approvals;

/**
 * The result of one approve or reject: whether it went through and the
 * message the admin sees (the Approvals page flashes it; the WhatsApp page
 * and the dashboard card show it on the card).
 */
final class ApprovalDecision
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
    ) {}

    public static function ok(string $message): self
    {
        return new self(true, $message);
    }

    public static function fail(string $message): self
    {
        return new self(false, $message);
    }
}
