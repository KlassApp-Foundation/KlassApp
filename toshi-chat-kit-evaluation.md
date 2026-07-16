# Toshi Chat Kit Evaluation: `kauffinger/livewire-chat-kit`

**Date**: 2026-07-14
**Evaluator**: Sisyphus
**Status**: Recommendation only — no code changed.

---

## Part 1 — What the package provides

`kauffinger/livewire-chat-kit` is a **Laravel starter kit** (not a composer library you
require into an existing app). It scaffolds a new Laravel app with:

| Layer | Technology | Version |
|---|---|---|
| Chat UI | Livewire v4 + FluxUI v2 | `livewire/livewire ^4.1.2` |
| Design system | FluxUI (Tailwind-based component library) | `livewire/flux ^2.11.1` |
| AI SDK | Prism | `prism-php/prism ^0.99.19` |

### How it renders tool calls

The kit has a dedicated `Chats/Show.php` Livewire component that:

1. Accepts a user message via text input
2. Calls `Prism::text()->withTools([...])->asStream()` — Prism handles the LLM
   request/response cycle end-to-end
3. Streams the response into a `StreamData` DTO
4. The Blade view (`resources/views/components/chat/assistant-message.blade.php`)
   renders a "Using tools..." indicator during tool execution, then displays tool
   call names and results in styled panels

The tool-display UI is a **Blade/Livewire view layer** — it reads from a `StreamData`
object that contains `toolCalls` and `toolResults`. It does NOT own or control the
confirmation-before-execution flow. There is **no Yes/No confirmation step** anywhere
in the kit — Prism executes tool calls immediately on the LLM's decision.

### FluxUI dependency

FluxUI is a Tailwind CSS component library (similar to DaisyUI or Preline). It provides
pre-styled components (buttons, modals, headings, tooltips) that can coexist with
KlassApp's existing Tailwind classes. However:
- FluxUI has its own color/branding system that may conflict with KlassApp's
  `--d-blue`/`--d-green`/`--d-amber` brand tokens
- It's not a replacement for the entire design system — it's a component library that
  would layer on top of the existing ds-* classes
- Coexistence is possible but would require careful scoping to avoid style conflicts

### Prism vs. `laravel/ai` for LLM communication

**Correction (July 14): The evaluation initially misidentified the AI SDK.** KlassApp
does not use LarAgent. The actual stack is:

| Concern | Current (laravel/ai) | Kit (Prism) |
|---|---|---|
| AI SDK | `laravel/ai ^0.9.0` — the official Laravel AI SDK | `prism-php/prism ^0.99.19` |
| Tool interface | `Laravel\Ai\Contracts\Tool` with `#[Tool]` attributes and `handle(Request)` | Prism's `Tool::as('name')->using(fn)` |
| Tool request object | `Laravel\Ai\Tools\Request` (ArrayAccess with `->get()` helper) | Prism's own message/result types |
| NVIDIA support | Working via `openai-compatible` provider driver in `config/ai.php` | Unknown — Prism has `OpenAI` driver but custom base URL support needs verification |
| Confirmation flow | **Yes** — `$pendingToolConfirm` + `awaitingConfirm` in AgentToshi.php | **No** — executes immediately on LLM tool decision |
| Tool routing | Via `ToshiOrchestrator` / `ToshiSdkV2Service` | Via Prism's own `withMaxSteps()` tool loop |

The core issue remains: the kit's tool-display UI is built on Prism's `StreamData` format
and event-streaming architecture. `laravel/ai` uses a different tool-calling pattern
(attribute-based tool classes with `handle(Request)` returning `string`). These are
incompatible at the data-model level — the kit's Blade components expect Prism-specific
data structures that don't exist in Toshi's message pipeline.



---

## Part 2 — Integration boundary analysis

### Can the tool-display UI be reused standalone?

**No — not cleanly.** The tool-display Blade components depend on Prism's `StreamData`
format (`toolCalls`, `toolResults`, `toolCallResultId`, `toolName`). Toshi's current
output is a plain string from `$result['message']` returned by `ToshiActionService` —
there's no structured tool-call metadata preserved through the pipeline.

To reuse the kit's tool UI, you would need to either:

1. **Adopt Prism as the LLM layer** — replace LarAgent's orchestration with Prism's
   `Prism::text()->withTools(...)` call cycle. This means rebuilding the tool dispatch
   and result handling on top of Prism's abstractions instead of the current
   `#[Tool]` attribute + `$request->get()` pattern.
2. **Or build an adapter** that converts Toshi's existing tool results into the
   `StreamData` format expected by the Blade components. This is possible but fragile —
   the kit has no documented adapter interface, and the `StreamData` DTO is tightly
   coupled to Prism's chunk-streaming event loop.

### The confirmation gate is the dealbreaker

The kit has **no concept of confirmation-before-execution**. Prism's tool loop runs
`withMaxSteps(5)` — when the LLM decides to call a tool, Prism executes it immediately.
Toshi's `$pendingToolConfirm` mechanism (which pauses execution and shows Yes/No
buttons) would need to be built on top of Prism's event stream, intercepting tool calls
before they execute.

This is architecturally possible but non-trivial — it means wrapping or forking Prism's
`asStream()` loop to emit a "tool call pending" event, waiting for user confirmation,
then either continuing or aborting. The kit provides no hook or extension point for this
today.

### FluxUI coexistence

FluxUI would introduce:
- New CSS that may conflict with the existing `dashboard-refresh.css` ds-* classes
- A component API (`flux:heading`, `flux:tooltip`, etc.) alongside the existing
  `x-*` Blade components and raw Tailwind
- Dependency on `livewire/flux ^2.11.1` — KlassApp is on Livewire v3, FluxUI v2 may
  require Livewire v4

---

## Part 3 — Recommendation

**Do not adopt `kauffinger/livewire-chat-kit` at this time.** The recommendation is
a clear "no" for three reasons:

### 1. The tool-display UI is not standalone
The kit's tool-call rendering is tightly coupled to Prism's `StreamData` format and
event-loop architecture. Using it without adopting Prism as the LLM layer requires
building an adapter that the kit doesn't provide and doesn't document. This is
essentially a fork, not an integration.

### 2. The confirmation gate cannot be preserved without forking Prism
Toshi's confirmation-before-write mechanism is the most carefully audited component
in the entire SDK v2 stack (verified across 15 write tools, 34 tests, 122 assertions).
The kit has no equivalent concept and provides no hook to add one. Building this on
top of Prism's streaming loop is a significant engineering effort that would delay
the Phase 2 rollout and introduce risk to the already-verified confirmation flow.

### 3. The dependency chain is mismatched
- **Prism** vs **LarAgent**: Toshi's oracle and tool orchestration are built on
  LarAgent, which talks to NVIDIA via an `openai-compatible` driver. Migrating to
  Prism means re-verifying the NVIDIA endpoint compatibility and potentially losing
  the existing provider configuration.
- **FluxUI v2** vs **Livewire v3**: KlassApp is on Livewire v3. FluxUI v2 may
  require Livewire v4, meaning an additional upgrade.
- **Brand tokens**: FluxUI's color system would need to be aligned with KlassApp's
  `--d-blue/#1E6FD9`, `--d-green/#22C55E` tokens — this is doable but is effort,
  not zero-cost.

### What to do instead

If the goal is to improve Toshi's message/tool-display UI, the lower-risk path is:

1. **Evolve the existing `AgentToshi` Livewire component's Blade template** — the
   message bubbles and tool-call display are just Blade views that can be styled
   without changing the backend. No new AI SDK, no new design system.
2. **Use existing ds-* classes** for any new UI elements — the `ds-card`,
   `ds-badge`, and `ds-btn` patterns are already built and can render tool calls
   and results in a consistent visual style.
3. **The Yes/No confirmation buttons already work** — they were verified in Part 3
   of the Toshi SDK v2 audit. The UI is functional, even if it's not as polished
   as FluxUI would make it.

The kit is a great starting point for a **new** Laravel + AI project. For KlassApp,
which has 34 passing tests, 15 confirmed write tools, a per-school enablement gate,
and a verified confirmation flow built on the current stack, the cost of migrating
to the kit's architecture outweighs the UI polish benefit.
