# Convergence index — landing work, 2026-09-27

Read these in order. **Where two files disagree, the later one in this list wins.**

1. `landing-polish-audit.md`: contrast (`--text-muted` → `--text-secondary`, `.pillar-note` → `#B45309`, **still awaiting the owner's approval**: the fallback is `--text-secondary`), hero device framing (window ≥901, phone ≤900), and the `x-landing.app-shot` component.
2. `handoff-2026-09-27-toshi-orbit.md`: orbit fixes 1–8, **9 (globe)** and **10 (one centre shape, the K riding the globe)**. Plus debox: remove the `.toshi-tower` card (the SVG sits on `.toshi-depth`).
3. `handoff-2026-09-27-toshi-tower-v2.md`: **read the superseded box at the top first.** Placement is "How it works", replacing the hub pill, with no container.
4. `landing-debox-catalog.md`: what stays boxed, what floats, and the reconciliation table.
5. `concepts/landing-debox/index.html`: the float treatment for every section. Ledger hairline `rgba(120,95,60,.14)`, ~28px gaps, depth on the icon tile. FAQ as a 56px divider list; Provable stays dashed amber; compare as one band with a single rule; HITL with a glow and no stripe; hero "Connected" as a status dot and wells.

**Concepts are the reference builds:** `concepts/toshi-orbit/index.html`, `concepts/toshi-tower/v2.html`, `concepts/landing-hero-frame/index.html`, `concepts/landing-debox/index.html`. They load assets via `<img>`/`<image>`; production should use the `<x-brand.*>` components and `asset()`.

**Never verified from Claude Design, so it's on the implementer:**
- Frame rate. Use 4× CPU slowdown at 390px and 1440px on the orbit core and the tower signals, and confirm no frame over 16.7ms.
- The real OS reduced-motion setting.
- Staging.

The orbit handoff's decision column says "approved for review" on item 9. That item was reviewed and extended (item 10); treat both as approved.

**Honesty lines, word for word:**
- "Stated direction. Not a live feature yet."
- "Illustrative of real product capability. Not sourced from a named school."
- "…four live platform primitives, and one honest forward direction."
