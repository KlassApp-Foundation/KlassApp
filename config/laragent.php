<?php

// config for Maestroerror/LarAgent
return [

    /**
     * Default driver — OpenAI-compatible (works with Nvidia NIM, DeepSeek, etc.)
     */
    'default_driver' => \LarAgent\Drivers\OpenAi\OpenAiCompatible::class,

    /**
     * Default chat history — in-memory (transient per-request).
     */
    'default_chat_history' => \LarAgent\History\InMemoryChatHistory::class,

    /**
     * Multi-provider fallback array.
     *
     * Primary: Nvidia NIM (current production provider via .env overrides).
     * The 'default' provider reads from TOSHI_LLM_* env vars so it stays
     * consistent with the existing ToshiAssistantService config.
     */
    'providers' => [
        'default' => [
            'label' => 'nvidia-nim',
            'api_key' => env('TOSHI_LLM_API_KEY'),
            'base_url' => env('TOSHI_LLM_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
            'model' => env('TOSHI_LLM_MODEL', 'meta/llama-3.1-8b-instruct'),
            'default_context_window' => 32000,
            'default_max_completion_tokens' => 500,
            'default_temperature' => 0.3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Toshi Agent Settings
    |--------------------------------------------------------------------------
    */
    'toshi' => [
        'model' => env('TOSHI_LLM_MODEL', 'meta/llama-3.1-8b-instruct'),
        'request_timeout' => env('TOSHI_REQUEST_TIMEOUT', 15),
        'max_tokens' => env('TOSHI_MAX_TOKENS', 500),
        'daily_llm_limit' => env('TOSHI_DAILY_LLM_LIMIT', 100),
        'fallback_model' => env('TOSHI_LLM_FALLBACK_MODEL'),
    ],
];
