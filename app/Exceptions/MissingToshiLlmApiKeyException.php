<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when Toshi's openai-compatible provider has no API key in the environment.
 * Hardcoded keys in config are not allowed — set OPENAI_COMPATIBLE_API_KEY (preferred)
 * or legacy TOSHI_LLM_API_KEY.
 */
class MissingToshiLlmApiKeyException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = 'Toshi LLM API key is not configured. Set OPENAI_COMPATIBLE_API_KEY in the environment (legacy alias: TOSHI_LLM_API_KEY).',
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
