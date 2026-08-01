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

    /*
    |--------------------------------------------------------------------------
    | Laravel AI SDK Agent Mode (v2 — parallel implementation)
    |--------------------------------------------------------------------------
    |
    | When enabled, assistant-mode queries are routed through the new Laravel AI
    | SDK agent stack (ToshiOrchestrator → Skill Agents with Tool classes).
    | Uses the 'openai-compatible' provider from config/ai.php which reads
    | TOSHI_LLM_* env vars for backward compatibility.
    |
    | Internal accounts only until parity is verified with the legacy paths.
    */
    'sdk_v2_enabled' => env('TOSHI_SDK_V2_ENABLED', false),

    /*
    | Per-school Toshi enablement flag (School scope only).
    | When toshi_enabled is true on the schools table AND sdk_v2_enabled is true
    | in the environment, Toshi SDK v2 features are active for that school.
    |
    | Do NOT disable or bypass this for school-scoped calls. Platform scope uses
    | platform_gate below and never consults schools.toshi_enabled.
    */
    'per_school_gate' => true,

    /*
    |--------------------------------------------------------------------------
    | Platform-scope gate (siteadmin / usergroup 1)
    |--------------------------------------------------------------------------
    |
    | Independent of per_school_gate and school_id. Phase 0 scaffolding +
    | Phase 1 partial tools (Geo/Plans/Schools via PlatformOperationsAgent).
    | Unlock isAvailable(Platform); business tools grow on PlatformOperationsAgent.
    |
    | Capability model: config allowlist (not a users column, not FeatureToggles).
    | FeatureToggleService is school_id-scoped; users/usergroups have no capability
    | attribute. Env/config is auditable without a migration for the few operators.
    |
    | enabled  — master capability flag (TOSHI_PLATFORM_GATE_ENABLED)
    | user_ids — optional ID allowlist (TOSHI_PLATFORM_USER_IDS=1,2,3).
    |            Empty array = all usergroup 1 users when enabled=true.
    |            Non-empty = must be usergroup 1 AND listed.
    */
    'platform_gate' => [
        'enabled' => env('TOSHI_PLATFORM_GATE_ENABLED', false),
        'user_ids' => array_values(array_filter(array_map(
            'intval',
            explode(',', (string) env('TOSHI_PLATFORM_USER_IDS', '')),
        ))),
    ],

    /*
    | The OpenAI-compatible API endpoint.
    | OpenAI:      https://api.openai.com/v1
    | DeepSeek:    https://api.deepseek.com
    | Nvidia NIM:  https://api.nvcf.nvidia.com/v1
    */
    'base_url' => 'https://api.deepseek.com',

    /*
    | API key for the LLM provider.
    */
    'api_key' => 'sk-2ccccb77847d446aa75105000aafab98',

    /*
    | Model name to use.
    | OpenAI:      gpt-4o-mini (recommended — cheap & fast)
    | DeepSeek:    deepseek-chat
    | Nvidia NIM:  meta/llama-3.1-8b-instruct
    */
    'model' => 'deepseek-chat',

    /*
    | Fallback model — used when the primary model fails (404, timeout, etc.).
    | Set to empty to disable fallback behaviour.
    */
    'fallback_model' => 'meta/llama-3.1-70b-instruct',

    /*
    | Model to use for escalated (complex) queries. When set, queries classified
    | as 'escalated' by classifyComplexity() use this model instead of the default.
    | Leave null to use the default model for all queries (no escalation).
    | Example: 'meta/llama-3.1-70b-instruct' (if available on your provider).
    */
    'escalated_model' => env('TOSHI_ESCALATED_MODEL'),

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
        . "About the user you're speaking with:\n{persona}\n\n"
        . "Rules:\n"
        . "- Be concise and helpful. Use markdown sparingly.\n"
        . "- Only answer from the context data above. Do not hallucinate numbers.\n"
        . "- If asked something you cannot answer from this data, say so.\n"
         . "- For actionable tasks (creating exams, adding parents, entering marks, etc.), USE the available tools (toolCreateExam, toolAddParent, toolEnterMark) to execute the action — do not just tell the user to use the sidebar.\n"
         . "- During onboarding (when the school has no classes, subjects, or terms): start by CONFIRMING the school's curriculum/board from the context data above. Say something like \"I see your school is set to [curriculum]. Is that correct?\" before asking about classes. Classes depend on the curriculum — don't ask about them until curriculum is confirmed.\n"
         . "- Keep responses under 3-4 paragraphs.\n"
        . "- Adapt your tone and detail level to match the user's communication style as described above."
    ),

    /*
    |--------------------------------------------------------------------------
    | Persona Memory
    |--------------------------------------------------------------------------
    |
    | When enabled, Toshi builds a persistent persona profile for each user
    | — their communication style, what they care about, their vibe — and
    | injects it into the system prompt so responses feel personal.
    |
    | The persona is updated periodically via a lightweight LLM call that
    | reads recent conversation history.
    |
    */
    'persona_enabled' => env('TOSHI_PERSONA_ENABLED', true),

    /*
    | How many LLM interactions between persona updates.
    | After this many queries, Toshi runs a small prompt to refresh the
    | user's persona summary. Higher = less API cost, slower adaptation.
    */
    'persona_update_interval' => env('TOSHI_PERSONA_UPDATE_INTERVAL', 5),

    /*
    | Model used for persona extraction (separate from the main chat model).
    | Use a cheaper model here since the task is simple.
    | Falls back to the main model if not set.
    */
    'persona_model' => env('TOSHI_PERSONA_MODEL', 'gpt-4o-mini'),

    /*
    |--------------------------------------------------------------------------
    | Streaming
    |--------------------------------------------------------------------------
    |
    | When enabled, LLM response tokens are streamed to the browser in real-time
    | via Livewire's $this->stream() + Alpine x-stream, instead of appearing all
    | at once after the full response is generated.
    |
    | Uses the package's Agent::stream() under the hood — tool-calling loop and
    | tier 2 confirmations still work; only plain text responses are streamed.
    |
    | Requires sdk_v2_enabled to also be true to take effect.
    |
    | KNOWN ISSUES (disabled by default — not actively fixed):
    | 1. Tool-serialization bug (Phase 3, item 4): The Agent::stream() path
    |    serializes tool call contexts in a way that can break the tool-calling
    |    loop under certain LLM response patterns. The non-streaming path
    |    (the default) handles all tool confirmations correctly.
    | 2. Blank-message artifact (Phase 3, item 1): When streaming is enabled,
    |    a blank streaming placeholder message can remain visible in the Toshi
    |    panel if the stream terminates unexpectedly before any content is
    |    delivered. Cosmetic — no data loss, no user impact.
    */
    'streaming_enabled' => false,
];
