<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when TOSHI_LLM_* and OPENAI_COMPATIBLE_* (or model vs URL host)
 * signal conflicting provider intent — the Aug 2026 empty-agent_conversations class.
 */
class AmbiguousToshiLlmConfigException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        private readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
