# KlassApp Toshi UI

Reusable UI components for Toshi — the KlassApp AI assistant. Presentation only, no business logic.

## Components

### Suggestion Chips

A horizontal scrollable row of tappable suggestion chips shown above the chat input.

```
@include('toshi-ui::components.suggestion-chips', [
    'suggestions' => [
        ['icon' => '📋', 'label' => 'Record attendance'],
        ['icon' => '💳', 'label' => 'Check fee balance'],
    ],
    'inputId' => 'toshi-input-panel',
    'wireKey' => 'sc',
])
```

**Props:**
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `suggestions` | array | `[]` | Array of `['icon' => str, 'label' => str]` chips to display |
| `inputId` | string | `'toshi-input-panel'` | ID of the textarea/input to pre-fill on click |
| `wireKey` | string | `'sc'` | Prefix for Livewire `wire:key` attributes |

**Behavior:**
- Chips are hidden automatically when used via Alpine (`x-data="{ used: new Set() }"`)
- Clicking a chip pre-fills the target input via `input.value = label` + dispatches `input` event
- Used chips are dimmed via `.toshi-chip-used` CSS class (opacity 0.5)

### Tool Confirmation Card

A structured card displayed when a write action awaits user confirmation.

```
@include('toshi-ui::components.tool-confirm-card', [
    'toolName' => 'Add Student',
    'toolIcon' => '👤',
    'params' => [
        ['label' => 'Name', 'value' => 'Grace Nakato'],
        ['label' => 'Class', 'value' => 'Primary 4'],
    ],
    'state' => 'pending',   // 'pending' or 'cancelled'
    'wireKey' => 'tpc',
    'confirmMethod' => 'confirmYes',
    'cancelMethod' => 'cancelAction',
    'cancelParam' => md5('some message'),
])
```

**Props:**
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `toolName` | string | `'Action'` | Human-readable action name |
| `toolIcon` | string | `'⚡'` | Emoji/icon for the action |
| `params` | array | `[]` | Array of `['label' => str, 'value' => str]` parameter rows |
| `state` | string | `'pending'` | `'pending'` for active card with buttons, `'cancelled'` for dimmed state |
| `wireKey` | string | `'tcc'` | Prefix for Livewire `wire:key` |
| `confirmMethod` | string | `'confirmYes'` | Livewire method for confirm action |
| `cancelMethod` | string | `'confirmNo'` | Livewire method for cancel action |
| `cancelParam` | string | `''` | Parameter passed to cancel method |

## States

Both components have two states:
- **Default** — active/interactive
- **Used/Cancelled** — dimmed after interaction

## CSS

Both components rely on CSS classes in `public/css/dashboard-refresh.css`:
- `.toshi-chip-suggestion`, `.toshi-suggestions-*` for suggestion chips
- `.toshi-confirm-card`, `.toshi-confirm-*` for tool confirmation card

## Design Convention

All future Toshi UI components should go through **Open Design** first:
1. Write a structured brief (audience, tone, brand tokens, specs, states)
2. Send via Open Design daemon (agent: `opencode`, model: `opencode-go/deepseek-v4-flash`)
3. Review the generated artifact and iterate if needed
4. Implement from the approved design
5. Extract into this package for reuse

## No Business Logic Dependency

These components do NOT import, use, or depend on:
- `ToshiActionService`
- Laravel AI SDK (`Laravel\Ai`)
- Authorization gates
- Any model classes

All data is passed as props from the parent Livewire component. This keeps the package reusable for future AI-facing features (e.g., AsiliChain).
