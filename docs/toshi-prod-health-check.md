# Toshi production LLM health check

## Incident class (2026-08-02)

Production Toshi never completed turns (`agent_conversations` stayed empty) because dual provider env silently resolved to an invalid model:

| Signal | Prod value (broken) |
|---|---|
| `OPENAI_COMPATIBLE_URL` / default | DeepSeek (`api.deepseek.com`) |
| `OPENAI_COMPATIBLE_MODEL` | **unset** |
| `TOSHI_LLM_MODEL` | `meta/llama-3.1-8b-instruct` (NVIDIA NIM leftover) |

`config/toshi.php` resolves `model → env('OPENAI_COMPATIBLE_MODEL', env('TOSHI_LLM_MODEL', 'deepseek-chat'))`, so agents sent an NVIDIA model name to DeepSeek → HTTP 400. Live ops fixed with `OPENAI_COMPATIBLE_MODEL=deepseek-v4-flash` (OpenCode). This PR is the **structural code fix**.

## Detection logic (`ToshiLlm::assertConfigConsistent()`)

Fails loudly when **any** of these hold:

1. **Both models set and differ** — `OPENAI_COMPATIBLE_MODEL` and `TOSHI_LLM_MODEL` non-empty with different values (split-brain with `ai.providers…smartest` still on `TOSHI_LLM_MODEL`).
2. **Both URLs set with different hosts** — `OPENAI_COMPATIBLE_URL` vs `TOSHI_LLM_BASE_URL`.
3. **Model family incompatible with URL host** — e.g. `meta/llama-*` / `nvidia/*` against `*.deepseek.com` (exact incident shape when only the legacy model drives resolution).

Not a conflict: preferred `OPENAI_COMPATIBLE_*` alone; legacy `TOSHI_LLM_MODEL` alone when family matches host; identical dual model values; unknown custom hosts (no false positive).

Raw env snapshots live in `config('toshi.llm_env')` so detection works under `config:cache`.

## Failure mode

- **Throw** `AmbiguousToshiLlmConfigException` on first `ToshiLlm::model()` / `provider()` resolve (agents / `UsesToshiLlm`) and from `toshi:llm-health`.
- **Do not** fail AppServiceProvider boot — unrelated HTTP routes must keep working.
- **Alerting evidence**: no Sentry/Bugsnag package in this app; `ActivityLog` is user-action audit, not infra monitoring. Monitored signal = exception + `Log::critical` (health command) + **non-zero Artisan exit** for cron/k8s.

## Commands

| Command | Purpose |
|---|---|
| `php artisan toshi:llm-status` | Config dump only (provider/model/host/key/checksum) — no live call |
| `php artisan toshi:llm-health` | **One cheap live completion** via openai-compatible `chat/completions`; verifies assistant content; exit `1` + `Log::critical` on failure |

Ops (VPS):

```bash
docker exec sms-app php artisan toshi:llm-status
docker exec sms-app php artisan toshi:llm-health
```

## Schedule

**Not wired** in `app/Console/Kernel.php`. OpenCode/ops owns production cadence (cron/k8s watching exit code of `toshi:llm-health`).

## Post-deploy cleanup

If prod still has leftover `TOSHI_LLM_MODEL=meta/llama-…` alongside `OPENAI_COMPATIBLE_MODEL=deepseek-v4-flash`, assert will throw until ops **unsets or aligns** `TOSHI_LLM_MODEL`. Prefer a single source of truth: `OPENAI_COMPATIBLE_*`.
