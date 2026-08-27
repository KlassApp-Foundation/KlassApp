# Proposal: Brand Identity & Assets for Public Launch

**Author:** KlassApp Team (AI-assisted analysis)
**Date:** 2026-08-28
**Status:** Proposed — awaiting team review
**Companion to:** `landing-page-reframe-proposal.md`

---

## Executive Summary

KlassApp is preparing for a public launch as an open-source agentic protocol for education. The current brand identity — logo, colors, typography, and assets — was built for a regional WhatsApp-first tool. To compete globally alongside products like n8n, CrewAI, and LangChain, the brand needs a deliberate upgrade.

This proposal audits every existing brand asset, identifies gaps, and recommends a complete brand identity system for launch.

---

## 1. Current Logo Audit

### 1.1 Logo Variants Found

| File | Format | Dimensions | Usage |
|------|--------|------------|-------|
| `klassapp-logo-primary.svg/png` | Horizontal lockup | 610×409 | Primary brand mark |
| `klassapp-logo.svg` | Icon only (square) | 500×500 | App icon, favicon base |
| `klassapp-logo-dark.svg/png` | Horizontal on dark bg | 1696×624 | Dark backgrounds |
| `klassapp-logo-stacked.svg/png` | Vertical lockup | — | Social cards, tight spaces |
| `klassapp-k-white.png` | White "K" letterform | — | Dark backgrounds, watermarks |
| `klassapp-app-icon.png` | App icon | — | Mobile app, PWA |
| `klassapplogo-dark.png` | Alternate dark logo | — | Used in `landing-layout` |

### 1.2 Logo Analysis

#### The Mark (Icon)
The KlassApp icon is a **stylized "K" letterform** constructed from geometric shapes — a vertical stroke with two diagonal arms extending to the right. It uses a **green-to-blue gradient** (green on the left/vertical, blue on the right/diagonals).

**Strengths:**
- ✅ Clean geometric construction
- ✅ Recognizable as a "K"
- ✅ Green-to-blue gradient suggests growth + technology
- ✅ Works at small sizes (favicon, app icon)

**Weaknesses:**
- ⚠️ **Feels generic** — similar to many "K" logos (Khan Academy, Kajabi). No unique differentiator.
- ⚠️ **No agentic/AI signal** — looks like a school management tool, not an AI protocol
- ⚠️ **Gradient is dated** — the green-to-blue gradient feels like 2018 SaaS, not 2026 agentic
- ⚠️ **No motion or intelligence cue** — static geometric mark doesn't suggest agents, connections, or protocol
- ⚠️ **The "K" alone doesn't communicate "KlassApp"** — at small sizes, it's just a letter

#### The Wordmark
The wordmark uses a **clean sans-serif** (appears to be a custom or modified geometric sans). "KlassApp" is set in medium weight with standard letter spacing.

**Strengths:**
- ✅ Readable at all sizes
- ✅ Professional, not playful — appropriate for education + enterprise

**Weaknesses:**
- ⚠️ **"KlassApp" spelling** — the double-s is a barrier to pronunciation and search (people will type "ClassApp")
- ⚠️ **No typographic personality** — could be any SaaS product
- ⚠️ **The wordmark doesn't hint at the product category** — could be a classroom app, a class scheduler, anything

#### The Lockup (Horizontal)
The horizontal lockup places the icon left, wordmark right. Standard layout.

**Weaknesses:**
- ⚠️ **Too much whitespace** between icon and wordmark in the SVG — feels disconnected
- ⚠️ **The dark version** (`klassapp-logo-dark.svg`) has a solid dark rectangle background — looks like a badge, not a logo. This is unusual and limits placement flexibility.

### 1.3 Logo Verdict

The current logo is **functional but not distinctive**. It works for a regional tool but won't stand out alongside n8n (bold red), CrewAI (purple gradient), or LangChain (blue chain link). For a global agentic protocol, the logo needs to signal:
1. **Intelligence** (AI/agent)
2. **Connection** (protocol/connector)
3. **Education** (school/learning)
4. **Openness** (open source/community)

---

## 2. Color Palette Audit

### 2.1 Current Colors (Extracted from CSS/SVG)

| Color | Hex | Usage |
|-------|-----|-------|
| Green (primary) | `#26AE60` / `#22C55E` / `#219C5A` | Logo, CTAs, success states |
| Blue (secondary) | `#1E6FD9` / `#216FC5` / `#185DA8` | Logo, links, primary actions |
| Navy/Dark | `#141413` / `#0C1528` | Dark backgrounds, text |
| Amber | `#D97706` | Accent bars, highlights |
| Surface | `#FAFAF5` / `#F5F4ED` | Light backgrounds |
| White | `#FEFEFE` / `#FAFAF5` | Cards, text on dark |

### 2.2 Color Analysis

**Strengths:**
- ✅ Green = growth, education, positive — good semantic fit
- ✅ Blue = trust, technology, reliability — good semantic fit
- ✅ The green+blue combination is distinctive in edtech (most use blue alone)

**Weaknesses:**
- ⚠️ **Too many green variants** — `#26AE60`, `#22C55E`, `#219C5A`, `#29BF5D`, `#1F9A54`, `#199D52` — at least 6 greens across assets. No single source of truth.
- ⚠️ **Too many blue variants** — `#1E6FD9`, `#216FC5`, `#185DA8`, `#004093`, `#0273D4`, `#1E6BC5`, `#175CA5` — at least 7 blues.
- ⚠️ **No violet/purple** — the color that signals "AI" and "intelligence" in 2026 (CrewAI, Anthropic, many AI products use purple/violet)
- ⚠️ **Amber feels disconnected** — used as an accent but doesn't relate to the green/blue system
- ⚠️ **No dark-mode palette defined** — the `landing-layout` dark variant uses ad-hoc colors

### 2.3 Industry Comparison

| Product | Primary | Secondary | AI Signal Color |
|---------|---------|-----------|-----------------|
| **n8n** | Red (#FF6D5A) | Dark (#141413) | — |
| **CrewAI** | Purple (#7C3AED) | Blue (#3B82F6) | Purple |
| **LangChain** | Blue (#1E6FD9) | Purple (#A78BFA) | Blue+Purple gradient |
| **Salesforce Agentforce** | Blue (#00A1E0) | Purple (#7B61FF) | Purple |
| **Vercel** | Black (#000) | White (#FFF) | — |
| **Linear** | Purple (#5E6AD2) | Dark (#0A0A0F) | Purple |

**Pattern:** Modern agentic products use **blue + purple** as their primary system. Purple signals AI/intelligence. Green is rare in agentic SaaS — it's more associated with growth/sustainability/health.

---

## 3. Typography Audit

### 3.1 Current Fonts

| Font | Weight | Usage |
|------|--------|-------|
| **Sora** | 400, 600, 700, 800 | Headlines, display text |
| **DM Sans** | 400, 500, 600, 700 | Body text, UI elements |
| **Plus Jakarta Sans** | 400–800 | Used in `landing-layout` dark variant |

### 3.2 Typography Analysis

**Strengths:**
- ✅ Sora is a modern geometric sans — good for tech products
- ✅ DM Sans is highly readable — good for body text
- ✅ Plus Jakarta Sans (in the dark variant) is excellent — warm, modern, distinctive

**Weaknesses:**
- ⚠️ **Three font families across variants** — inconsistency. Pick one system.
- ⚠️ **No monospace font** — agentic/technical products use monospace for code, data, and credibility (n8n uses `JetBrains Mono`, Vercel uses `Geist Mono`)
- ⚠️ **No variable font** — modern products use variable fonts for performance and flexibility

### 3.3 Industry Comparison

| Product | Display | Body | Mono |
|---------|---------|------|------|
| **n8n** | Inter | Inter | JetBrains Mono |
| **CrewAI** | Inter | Inter | JetBrains Mono |
| **LangChain** | Plus Jakarta Sans | Plus Jakarta Sans | Geist Mono |
| **Vercel** | Geist | Geist | Geist Mono |
| **Linear** | Inter | Inter | JetBrains Mono |
| **Stripe** | Stripe font (custom) | Stripe font | — |

**Pattern:** Modern SaaS uses **one font family** for everything (usually Inter or Plus Jakarta Sans) with a **monospace companion** for technical credibility.

---

## 4. Brand Asset Gaps

### 4.1 Missing Assets (Critical for Launch)

| Asset | Status | Priority |
|-------|--------|----------|
| **Favicon (SVG)** | ✅ Exists | — |
| **Apple Touch Icon** | ❌ Missing | High |
| **Open Graph Image** | ❌ Missing | Critical |
| **Social Card Template** | ❌ Missing | Critical |
| **Brand Guidelines PDF** | ❌ Missing | High |
| **Logo Usage Guide** | ❌ Missing | High |
| **Color Palette Document** | ❌ Missing | High |
| **Typography Scale** | ❌ Missing | Medium |
| **Icon Set** | ❌ Missing | Medium |
| **Illustration Style** | ❌ Missing | Medium |
| **Email Template** | ❌ Missing | High |
| **GitHub Social Card** | ❌ Missing | High |
| **README Banner** | ❌ Missing | High |
| **Press Kit** | ❌ Missing | Medium |
| **Merchandise Templates** | ❌ Missing | Low |
| **Presentation Template** | ❌ Exists (PPTX in docs/) | — |
| **Video Intro/Outro** | ❌ Missing | Medium |
| **Animated Logo** | ❌ Missing | Medium |

### 4.2 Existing Assets That Need Updates

| Asset | Issue |
|-------|-------|
| **All logo variants** | Need refresh for agentic positioning |
| **Favicon** | Current green "K" — needs to work on dark backgrounds |
| **OG meta tags** | Point to `klassapp-logo-stacked.svg` — should be a purpose-built 1200×630 social card |
| **Email templates** | No branded email templates found in codebase |
| **Dashboard branding** | Uses generic dark header — needs brand integration |

---

## 5. Proposed Brand Identity System

### 5.1 Brand Positioning

> **KlassApp** — The open-source agentic protocol for education.

**Brand Personality:**
- **Intelligent** — not just smart, but agentically intelligent
- **Open** — transparent, community-driven, extensible
- **Global** — works everywhere, for every school
- **Warm** — education is human; the brand should feel approachable
- **Technical** — protocol-level credibility, not just another app

### 5.2 Proposed Logo Direction

#### Option A: "The Agent Node"
The "K" mark is reimagined as a **node in a network** — the vertical stroke becomes a central hub, and the diagonal arms become **connection lines** with small dots at the endpoints (representing connectors/channels). This signals:
- Protocol (network topology)
- Agent (central intelligence)
- Connection (multi-channel)

#### Option B: "The Protocol Ring"
A circular mark where the "K" is formed by **intersecting arcs** — suggesting a protocol handshake. The circle represents:
- Global (works everywhere)
- Protocol (standard/specification)
- Community (open, inclusive)

#### Option C: "The Intelligent K" (Evolution, not revolution)
Keep the current "K" geometry but:
- Add a **subtle glow/pulse** effect (suggests AI/agent activity)
- Shift from green-to-blue gradient to **blue-to-purple** gradient (signals AI)
- Add a small **dot/circle** at the intersection point (represents the agent brain)

**Recommendation:** Option C — it preserves brand recognition while signaling the new positioning.

### 5.3 Proposed Color System

```css
:root {
    /* ── Primary: Intelligence ── */
    --ka-blue:          #1E6FD9;  /* Trust, technology, protocol */
    --ka-blue-dark:     #1558B5;  /* Hover states */
    --ka-blue-light:    #60A5FA;  /* Links, accents */

    /* ── Secondary: Intelligence (AI Signal) ── */
    --ka-violet:        #7C3AED;  /* AI, agents, intelligence */
    --ka-violet-dark:   #6D28D9;  /* Hover states */
    --ka-violet-light:  #A78BFA;  /* Accents, gradients */

    /* ── Accent: Action ── */
    --ka-green:         #22C55E;  /* CTAs, success, growth */
    --ka-green-dark:    #16A34A;  /* Hover states */

    /* ── Neutral: Foundation ── */
    --ka-dark:          #0C1528;  /* Dark backgrounds */
    --ka-surface:       #0F1A3A;  /* Card backgrounds (dark mode) */
    --ka-text:          #F1F5F9;  /* Primary text (dark mode) */
    --ka-text-muted:    rgba(241, 245, 249, 0.62);
    --ka-text-faint:    rgba(241, 245, 249, 0.35);
    --ka-border:        rgba(255, 255, 255, 0.09);

    /* ── Gradients ── */
    --ka-gradient-ai:   linear-gradient(135deg, #1E6FD9 0%, #7C3AED 50%, #22C55E 100%);
    --ka-gradient-hero: linear-gradient(135deg, #60A5FA 0%, #A78BFA 50%, #4ADE80 100%);
}
```

**Key change:** Add **violet/purple** as the AI signal color. The green stays (it's distinctive in edtech), but purple becomes the "this is agentic" signal.

### 5.4 Proposed Typography System

```css
/* ── Primary: Plus Jakarta Sans ── */
/* Warm, modern, distinctive. Used for everything. */
--font-display: 'Plus Jakarta Sans', system-ui, sans-serif;
--font-body:    'Plus Jakarta Sans', system-ui, sans-serif;

/* ── Monospace: JetBrains Mono ── */
/* Technical credibility. Code, data, terminal mockups. */
--font-mono:    'JetBrains Mono', 'Fira Code', monospace;

/* ── Type Scale ── */
--text-xs:   0.75rem;    /* 12px */
--text-sm:   0.875rem;   /* 14px */
--text-base: 1rem;       /* 16px */
--text-lg:   1.125rem;   /* 18px */
--text-xl:   1.25rem;    /* 20px */
--text-2xl:  1.5rem;     /* 24px */
--text-3xl:  1.875rem;   /* 30px */
--text-4xl:  2.25rem;    /* 36px */
--text-5xl:  3rem;       /* 48px */
--text-6xl:  3.75rem;    /* 60px */
--text-hero: clamp(2.5rem, 5vw, 4.5rem); /* Responsive hero */
```

**Recommendation:** Consolidate to **Plus Jakarta Sans** (already used in the dark variant) as the single font family. It's warm, modern, and works for both display and body. Add **JetBrains Mono** for technical credibility.

### 5.5 Proposed Logo Usage Guidelines

#### Minimum Sizes
- Icon only: 24×24px minimum
- Horizontal lockup: 120px wide minimum
- Stacked lockup: 80px wide minimum

#### Clear Space
- Minimum clear space around the logo = height of the "K" mark on all sides

#### Backgrounds
- ✅ Light backgrounds (#FAFAF5, #FFFFFF) — use primary logo
- ✅ Dark backgrounds (#0C1528, #000000) — use white logo
- ✅ Photo backgrounds — use logo with subtle drop shadow
- ❌ Never place on busy/patterned backgrounds without a container

#### Don'ts
- ❌ Don't stretch or distort
- ❌ Don't change the colors
- ❌ Don't add effects (drop shadow, glow, 3D)
- ❌ Don't place on low-contrast backgrounds
- ❌ Don't rotate
- ❌ Don't rearrange the lockup

---

## 6. Brand Assets to Create

### 6.1 Critical (Pre-Launch)

#### Open Graph / Social Card
```
Dimensions: 1200×630px
Content: Logo + tagline + subtle background
Usage: Twitter, LinkedIn, Slack, Discord previews
```

**Design:**
- Dark background (#0C1528) with subtle aurora orbs
- KlassApp logo centered
- Tagline: "The open-source agentic protocol for education"
- Connector icons (WhatsApp, Drive, Slack) as subtle watermark

#### GitHub README Banner
```
Dimensions: 1280×640px
Content: Logo + value prop + tech stack badges
Usage: GitHub README, repo social preview
```

**Design:**
- Dark background with gradient
- Large KlassApp logo
- "The open-source agentic protocol for education"
- Badges: Laravel · Vue · Toshi AI · MIT License
- GitHub stars badge

#### Apple Touch Icon
```
Dimensions: 180×180px
Content: App icon (no transparency)
Usage: iOS home screen bookmark
```

#### Email Template
```
Width: 600px max
Content: Branded header + content area + footer
Usage: Transactional emails, newsletters, notifications
```

**Design:**
- Header: Logo on dark background (#0C1528)
- Body: White background, DM Sans/Plus Jakarta Sans text
- Footer: Logo, social links, unsubscribe
- Accent: Green CTA buttons (#22C55E)

### 6.2 Important (Post-Launch, Month 1)

| Asset | Description |
|-------|-------------|
| **Brand Guidelines PDF** | 10-15 page document covering logo, colors, typography, voice, imagery |
| **Icon Set** | 50+ custom icons for connectors, features, roles |
| **Illustration Style** | Custom illustration system for onboarding, empty states, marketing |
| **Presentation Template** | Google Slides / Keynote template for demos and investor meetings |
| **Press Kit** | ZIP with logos (all formats), brand guidelines, screenshots, boilerplate |
| **Video Intro/Outro** | 3-second animated logo for video content |
| **Animated Logo** | Lottie/SVG animation for loading states, onboarding |

### 6.3 Nice-to-Have (Month 2+)

| Asset | Description |
|-------|-------------|
| **Merchandise Templates** | T-shirt, sticker, mug designs |
| **Swag Store** | Branded merchandise for community |
| **Certificate Template** | For contributor recognition |
| **Community Badges** | GitHub profile badges for contributors |

---

## 7. Email Brand Identity

### 7.1 Transactional Email Template

```
┌─────────────────────────────────────────┐
│  ┌─────────────────────────────────┐    │
│  │     [KlassApp Logo]             │    │
│  │     Dark bg (#0C1528)           │    │
│  └─────────────────────────────────┘    │
│                                         │
│  Hi [Name],                             │
│                                         │
│  [Email content here]                   │
│                                         │
│  ┌─────────────────────────────────┐    │
│  │     [CTA Button]                │    │
│  │     Green (#22C55E)             │    │
│  └─────────────────────────────────┘    │
│                                         │
│  [Secondary content if needed]          │
│                                         │
│  ─────────────────────────────────────  │
│  KlassApp — The agentic protocol for    │
│  education.                             │
│  [GitHub] [Twitter] [Website]           │
│  Unsubscribe · Preferences              │
└─────────────────────────────────────────┘
```

### 7.2 Email Types to Template

| Email | Trigger | Priority |
|-------|---------|----------|
| **Welcome** | New school signup | Critical |
| **Verification** | Email confirmation | Critical |
| **Password Reset** | Forgot password | Critical |
| **Invitation** | Admin invites teacher/parent | Critical |
| **Weekly Digest** | Summary of school activity | High |
| **Fee Reminder** | Outstanding fees | High |
| **Exam Results** | Results published | High |
| **Absence Alert** | Student absent | High |
| **Newsletter** | Product updates | Medium |
| **Onboarding Drip** | Day 1, 3, 7 tips | Medium |

### 7.3 Email Design Principles

1. **Mobile-first** — 60%+ of parents read on mobile
2. **Single-column** — no complex layouts
3. **High contrast** — text must be readable on all clients
4. **Fallback fonts** — system fonts for email clients that don't load web fonts
5. **Dark mode support** — test in dark mode email clients
6. **Accessibility** — alt text, semantic HTML, sufficient contrast

---

## 8. Brand Voice & Messaging

### 8.1 Voice Attributes

| Attribute | Description | Example |
|-----------|-------------|---------|
| **Clear** | No jargon, no fluff | "Connect your tools" not "Leverage our integrated ecosystem" |
| **Confident** | We know what we're building | "The agentic protocol for education" not "A school management tool" |
| **Warm** | Education is human | "Every parent, every child" not "End-to-end stakeholder management" |
| **Technical** | Protocol credibility | "MCP-compatible, self-hostable" not "Easy to set up" |
| **Open** | Community-driven | "Built in the open. Extended by community." not "Our proprietary platform" |

### 8.2 Tagline Options

| Option | Style | Best For |
|--------|-------|----------|
| "Your school's tools, connected by intelligence." | Descriptive | Landing page hero |
| "The agentic protocol for education." | Positioning | Meta tags, social cards |
| "Open source. Global. Agentic." | Punchy | GitHub, press |
| "One agent. Every channel. Every school." | Rhythmic | Marketing campaigns |
| "Stop switching apps. Start orchestrating." | Action | CTAs |

### 8.3 Messaging Framework

**For Administrators:**
> "Run your entire school from one dashboard. Toshi, your AI agent, handles the busywork — attendance, reports, notifications — so you can focus on education."

**For Teachers:**
> "Your lesson plans, marks, homework, and attendance — all in one place. Toshi helps you spend less time on admin and more time teaching."

**For Parents:**
> "Check your child's grades, fees, and attendance from WhatsApp. No app to install. No password to remember. Just a message."

**For Developers:**
> "KlassApp is an open-source agentic protocol. Fork it, extend it, contribute connectors. Built on Laravel, powered by Toshi AI, compatible with MCP."

---

## 9. Competitive Brand Comparison

| Aspect | KlassApp (Current) | KlassApp (Proposed) | n8n | CrewAI | LangChain |
|--------|-------------------|---------------------|-----|--------|-----------|
| **Logo** | Green "K" | Blue-purple "K" with node | Bold red wordmark | Purple gradient | Blue chain link |
| **Primary Color** | Green | Blue + Violet | Red (#FF6D5A) | Purple (#7C3AED) | Blue (#1E6FD9) |
| **AI Signal** | None | Violet/purple | None | Purple | Blue-purple gradient |
| **Typography** | Sora + DM Sans | Plus Jakarta Sans + JetBrains Mono | Inter | Inter | Plus Jakarta Sans |
| **Dark Mode** | Ad-hoc | Systematic | ✅ Primary | ✅ Primary | ✅ Primary |
| **Social Card** | Missing | Planned | ✅ | ✅ | ✅ |
| **Brand Guidelines** | Missing | Planned | ✅ | ✅ | ✅ |
| **Open Source Signal** | None | GitHub stars, MIT badge | ✅ 202K stars | ✅ | ✅ |
| **Monospace** | None | JetBrains Mono | ✅ | ✅ | ✅ |

---

## 10. Implementation Roadmap

### Phase 1: Foundation (Week 1)
- [ ] Finalize color palette (add violet, consolidate greens/blues)
- [ ] Choose typography system (Plus Jakarta Sans + JetBrains Mono)
- [ ] Create brand color document
- [ ] Update CSS variables across codebase

### Phase 2: Logo Refresh (Week 2)
- [ ] Brief a designer (or use AI-assisted design) for logo evolution
- [ ] Create all logo variants (primary, dark, stacked, icon)
- [ ] Generate favicon set (16, 32, 96, 180, 512px)
- [ ] Create Apple Touch Icon

### Phase 3: Social & Marketing Assets (Week 3)
- [ ] Design Open Graph social card (1200×630)
- [ ] Design GitHub README banner (1280×640)
- [ ] Create email template (transactional + newsletter)
- [ ] Design press kit (logos, screenshots, boilerplate)

### Phase 4: Documentation (Week 4)
- [ ] Write brand guidelines PDF
- [ ] Create logo usage guide
- [ ] Document typography scale
- [ ] Create presentation template

### Phase 5: Polish (Month 2)
- [ ] Custom icon set
- [ ] Illustration style guide
- [ ] Animated logo (Lottie)
- [ ] Video intro/outro
- [ ] Community badges

---

## 11. Budget Estimates

| Item | DIY (AI-Assisted) | Professional Designer |
|------|-------------------|----------------------|
| Logo refresh | $0 (AI tools) | $500–2,000 |
| Brand guidelines | $0 (AI tools) | $1,000–3,000 |
| Social cards & assets | $0 (AI tools) | $200–500 |
| Email templates | $0 (AI tools) | $300–800 |
| Icon set | $0 (AI tools) | $500–1,500 |
| Illustration style | $0 (AI tools) | $1,000–3,000 |
| **Total** | **$0** | **$3,500–10,800** |

**Recommendation:** Start with AI-assisted design (Midjourney, DALL-E, Figma AI) for speed, then hire a professional designer for the logo refresh and brand guidelines once the positioning is validated.

---

## 12. Conclusion

KlassApp's current brand identity is functional but generic. For a global launch as an open-source agentic protocol, the brand needs to:

1. **Signal intelligence** — add violet/purple to the color system
2. **Signal protocol** — evolve the logo to suggest connection/network
3. **Signal openness** — leverage GitHub, MIT license, community
4. **Be consistent** — consolidate fonts, colors, and asset formats
5. **Be complete** — fill the gaps (social cards, emails, brand guidelines)

The proposed system positions KlassApp alongside n8n, CrewAI, and LangChain — as infrastructure, not just another school app.

---

## Appendix: File Inventory

### Current Logo Files
```
public/images/klassapp-app-icon.png
public/images/klassapp-k-white.png
public/images/klassapp-logo-dark.png
public/images/klassapp-logo-dark.svg
public/images/klassapp-logo-primary.png
public/images/klassapp-logo-primary.svg
public/images/klassapp-logo-stacked.png
public/images/klassapp-logo-stacked.svg
public/images/klassapp-logo.svg
public/images/klassapplogo-dark.png
public/images/favicon.png
public/favicon/favicon-16x16.png
public/favicon/favicon-32x32.png
public/favicon/favicon-96x96.png
public/favicon/favicon.ico
public/favicon/favicon.svg
docs/community/klassapp-logo.svg
docs/dev/klassapp-logo.svg
```

### Files Referencing Logo
```
resources/views/landing.blade.php
resources/views/landing2.blade.php
resources/views/components/landing-layout.blade.php
resources/views/layouts/partials/favicon.blade.php
```
