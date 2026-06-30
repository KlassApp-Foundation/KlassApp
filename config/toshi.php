<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Toshi LLM Assistant Configuration
    |--------------------------------------------------------------------------
    |
    | Controls the AI-powered assistant mode. When disabled, Toshi falls back
    | to the built-in keyword router (no API cost).
    |
    | Supports any OpenAI-compatible API (OpenAI, DeepSeek, Nvidia NIM, etc.)
    | by setting the base_url to the provider's endpoint.
    |
    | Cost guard: daily_llm_limit caps how many LLM queries a single user can
    | make per day. Once exhausted, Toshi falls back to the keyword router.
    |
    */

    'llm_enabled' => env('TOSHI_LLM_ENABLED', false),

    /*
    | The OpenAI-compatible API endpoint.
    | OpenAI:      https://api.openai.com/v1
    | DeepSeek:    https://api.deepseek.com
    | Nvidia NIM:  https://api.nvcf.nvidia.com/v1
    */
    'base_url' => env('TOSHI_LLM_BASE_URL', 'https://api.openai.com/v1'),

    /*
    | API key for the LLM provider.
    */
    'api_key' => env('TOSHI_LLM_API_KEY', ''),

    /*
    | Model name to use.
    | OpenAI:      gpt-4o-mini (recommended — cheap & fast)
    | DeepSeek:    deepseek-chat
    | Nvidia NIM:  meta/llama-3.1-8b-instruct
    */
    'model' => env('TOSHI_LLM_MODEL', 'gpt-4o-mini'),

    /*
    | Fallback model — used when the primary model fails (404, timeout, etc.).
    | Useful with NIM where specific models may be temporarily unavailable.
    | Set to empty to disable fallback behaviour.
    */
    'fallback_model' => env('TOSHI_LLM_FALLBACK_MODEL'),

    /*
    | Maximum number of LLM queries per user per day.
    | After this limit, Toshi falls back to the keyword router silently.
    | Set to 0 for unlimited (not recommended during free phase).
    */
    'daily_llm_limit' => env('TOSHI_DAILY_LLM_LIMIT', 100),

    /*
    | Request timeout in seconds for the LLM API call.
    */
    'request_timeout' => env('TOSHI_REQUEST_TIMEOUT', 15),

    /*
    | Work hours for query budget bonuses.
    | During work hours, users get 2x the per-window query budget.
    | Outside work hours, the base budget applies.
    | Premium schools also get 5x the window budget regardless of time.
    */
    'work_hours_start' => env('TOSHI_WORK_HOURS_START', 8),
    'work_hours_end' => env('TOSHI_WORK_HOURS_END', 16),

    /*
    | Max tokens for the LLM response.
    */
    'max_tokens' => env('TOSHI_MAX_TOKENS', 500),

    /*
    | System prompt template — injected with context data before each query.
    | Available placeholders:
    |   {role}         — "platform" or "school"
    |   {context_data} — injected stats and information
    */
    'system_prompt_template' => env('TOSHI_SYSTEM_PROMPT_TEMPLATE',
        "You are Toshi, the AI assistant for KlassApp — a school management platform.\n"
        . "Your role: {role}\n\n"
        . "Current context data:\n{context_data}\n\n"
        . "Rules:\n"
        . "- Be concise and helpful. Use markdown sparingly.\n"
        . "- Only answer from the context data above. Do not hallucinate numbers.\n"
        . "- If asked something you cannot answer from this data, say so.\n"
        . "- For actionable tasks (reports, settings), direct the user to the admin sidebar.\n"
        . "- Keep responses under 3-4 paragraphs."
    ),
];
