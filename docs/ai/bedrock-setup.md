# Toshi on Claude through Amazon Bedrock

Proof of concept. Production stays on the current provider until `TOSHI_LLM_PROVIDER` is set. Do not put real keys in this file, in git, or in a pull request.

`laravel/ai` v0.10.2 (locked) ships a Bedrock provider that calls the Converse API, including tool calls. This app's `config/ai.php` `bedrock` entry is what that provider reads. The notes below were checked against that lockfile commit's `BedrockProvider` and `CreatesBedrockClient`. A live call was not made: this checkout has no Bedrock credentials.

## Env vars

Leave these unset to keep today's behaviour (openai-compatible, model from `OPENAI_COMPATIBLE_MODEL`, default `deepseek-chat`).

```bash
TOSHI_LLM_PROVIDER=bedrock
TOSHI_LLM_MODEL=us.anthropic.claude-sonnet-4-5-20250929-v1:0
AWS_BEDROCK_REGION=us-east-1
```

`TOSHI_LLM_MODEL` is the Bedrock model ID or inference-profile ID when the provider is `bedrock`. The value above is a placeholder shape, not a model this repo has called. Pick an ID that the account has enabled.

Authentication, first match wins inside laravel/ai:

| Mode | Variables |
| --- | --- |
| Bearer token | `AWS_BEARER_TOKEN_BEDROCK` |
| Static keys | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, optional `AWS_SESSION_TOKEN` |
| AWS SDK default chain | `AWS_USE_DEFAULT_CREDENTIALS=true` (this is the config default) |

The default chain covers environment keys, a shared credentials file, and a role on the machine. No API key is required in that case. Set `AWS_USE_DEFAULT_CREDENTIALS=false` only when you are supplying a bearer token or static keys. Do not commit any of those values.

`OPENAI_COMPATIBLE_API_KEY` is not used while the provider is `bedrock`.

Assume-role (`assume_role.arn`) exists in laravel/ai's Bedrock client and is not wired in this app's `config/ai.php`. Not verified here.

## Access

The caller needs permission to invoke the model in that region. AWS documents `bedrock:InvokeModel`, `bedrock:Converse`, and `bedrock:ConverseStream` on the model or inference-profile ARN. Anthropic models also need model access turned on in the Bedrock console for the account. Those two requirements were not checked against a KlassApp account.

## One local call

Config only (no network):

```bash
php artisan toshi:llm-health
```

With `TOSHI_LLM_PROVIDER=bedrock` that command prints the provider, model, region, and which credential mode is set. It does not call Bedrock unless you pass `--live`.

One Converse request (needs real credentials; not run in CI):

```bash
php artisan toshi:llm-health --live
```

The same probe from tinker:

```bash
php artisan tinker --execute="echo (new App\AiAgents\ToshiLlmProbe)->prompt('Reply with exactly: TOSHI_HEALTH_OK')->text;"
```

`toshi:llm-status` prints the resolved provider and model and never prints a key.

## Not verified

- A successful live Bedrock response.
- Which Claude ID is enabled for any real account.
- Assume-role, or a bearer token, against AWS.
- Tool-call behaviour of Claude on Bedrock inside Toshi's agent loop. The SDK supports tool calls; this change only selects the provider and model.
