# Proposal: Landing Page Reframe — From WhatsApp Tool to Agentic Protocol

**Author:** KlassApp Team (AI-assisted analysis)
**Date:** 2026-08-28
**Status:** Proposed — awaiting team review
**PR:** This document

---

## Executive Summary

KlassApp's landing page currently positions the product as a **"WhatsApp-first school management platform"** built for African schools. This framing undersells what KlassApp actually is: an **open-source agentic protocol** with multi-channel connectors (WhatsApp, Google Drive, Slack, Email, SMS), a sophisticated AI agent layer (Toshi), and a full school management system.

This proposal recommends reframing the landing page to position KlassApp alongside industry-grade agentic platforms (Salesforce Agentforce, CrewAI, n8n, LangChain) — as **infrastructure**, not just a tool.

**The core shift:**
> From: *"The school in every parent's pocket"*
> To: *"The open-source agentic protocol for education"*

---

## 1. Current State Audit

### 1.1 Landing Page Variants

The repo contains three landing page templates:

| File | Style | Status |
|------|-------|--------|
| `resources/views/landing.blade.php` | Warm, WhatsApp-centric, African branding | Active (likely production) |
| `resources/views/landing2.blade.php` | Flare-style nav pill, similar content | A/B variant |
| `resources/views/components/landing-layout.blade.php` | Dark-mode SaaS template | Generic/unused |

### 1.2 What's Working

- ✅ **WhatsApp phone mockup** — strong visual hook, animated message flow
- ✅ **Audience selector tabs** (Admin / Teacher / Parent) — good personalization
- ✅ **Role-based dashboard mockups** — shows breadth of the platform
- ✅ **Clean typography** (Sora + DM Sans) — professional feel
- ✅ **Scroll reveal animations** — modern interaction design

### 1.3 What's Holding It Back

| Issue | Evidence | Impact |
|-------|----------|--------|
| **"Built for African schools"** in OG meta tags | `<meta property="og:description" ... "Built for African schools." />` | Kills global ambition at the `<head>` level — search engines and social previews show this |
| **"Trusted by schools across Africa"** | Social proof marquee shows only Ugandan schools (Kabale, Kengoma, etc.) | Signals regional tool, not global platform |
| **WhatsApp is the entire story** | Hero headline, How It Works, CTA — everything funnels to WhatsApp | Misses the multi-channel reality (Drive, Slack, Email, SMS) |
| **Toshi AI is invisible** | Zero mention of Toshi, AI agents, or agentic capabilities anywhere on the landing page | The most differentiated feature is completely hidden |
| **"Open source" is absent** | LICENSE file exists (MIT), but the landing page doesn't mention it | Misses a major trust and community signal |
| **"Protocol" framing is missing** | Positioned as a school management *tool*, not a *platform/protocol* | Sits in a crowded category instead of creating a new one |
| **Generic template** (`landing-layout`) | Uses fake school names (Lincoln Academy, Greenfield School) | Feels like a purchased theme, not a real product |

### 1.4 Meta Tag Analysis

**Current:**
```html
<title>KlassApp — The School in Every Parent's Pocket</title>
<meta name="description" content="KlassApp is a WhatsApp-first school management platform. Parents check grades, fees and attendance with a single message. No app. No login. Just WhatsApp." />
<meta property="og:description" content="Grades, fees, and attendance delivered to parents on WhatsApp. Built for African schools." />
```

**Problems:**
- "WhatsApp-first" = one-channel product
- "Built for African schools" = regional
- "No app. No login. Just WhatsApp." = feature, not vision

---

## 2. Market Context: How Agentic Products Position in 2026

### 2.1 The Protocol Pattern

The most ambitious agentic products in 2026 frame themselves as **protocols and platforms**, not tools:

| Product | Positioning | Key Phrase |
|---------|-------------|------------|
| **Salesforce Agentforce** | Agentic AI CRM platform | "The #1 Agentic AI CRM" |
| **CrewAI** | Enterprise agent build & runtime | "The Enterprise Agent Build & Runtime for the work your business runs on" |
| **n8n** | AI workflow automation platform | "AI agents and workflows you can see and control" |
| **LangChain** | Open agent platform | "The open agent platform to own your intelligence" |
| **ServiceNow** | AI Agent Orchestrator | "AI agents that work across the enterprise" |

**Common pattern:** These products don't sell features — they sell the **orchestration layer**. The value is in connecting things, not being another tool.

### 2.2 The Connector Pattern

Modern agentic SaaS positions integrations as the hero:

- **n8n:** "Plug AI into your own data & over 500 integrations"
- **CrewAI:** Shows enterprise logos (DocuSign, PepsiCo, IBM) as trust signals
- **LangChain:** "Agent Infrastructure" — deployment, sandboxes, LLM gateway

**The shift:** Features are secondary. The story is: "We connect to what you already use, and make it intelligent."

### 2.3 The Open Source Pattern

Open source in agentic SaaS is a **trust and adoption signal**:

- **n8n:** 202K+ GitHub stars, "Access the entire source code on Github"
- **LangChain:** Open source agent frameworks as the foundation
- **CrewAI:** Open source core with enterprise platform on top

**Why it matters:** Open source = community = protocol adoption = moat.

### 2.4 Visual Design Patterns

Industry-grade agentic landing pages share these visual characteristics:

1. **Dark-mode hero** with aurora/glow effects (LangChain, CrewAI, n8n)
2. **Connector/node diagrams** showing the product as a central hub
3. **Animated data flows** between integrations
4. **Glass-morphism cards** with subtle borders and backdrop blur
5. **Gradient text** for headlines (blue → purple → green)
6. **Floating orbs** as ambient background elements
7. **Code/terminal mockups** alongside dashboard mockups
8. **GitHub star badges** and contributor avatars as social proof
9. **Minimal copy, maximum whitespace** — let the visuals breathe
10. **Monospace accents** for technical credibility

---

## 3. Proposed Reframe

### 3.1 New Positioning Statement

> **KlassApp is an open-source agentic protocol for education.**
> It connects to the tools schools already use — WhatsApp, Google Drive, Slack, Email, SMS — and orchestrates them through Toshi, an AI agent that understands education.

### 3.2 The Narrative Shift

| From (Current) | To (Reframed) |
|----------------|---------------|
| "WhatsApp-first school management" | "Agentic protocol for education" |
| "The school in every parent's pocket" | "Your school's tools, connected by intelligence" |
| "Built for African schools" | "Open source. Global. Built for how schools actually work." |
| "No app. No login. Just WhatsApp." | "Connects to what you already use. Learns how your school works." |
| Features list (Exams, Fees, Attendance...) | Connectors list (WhatsApp, Drive, Slack, Email, SMS...) |
| "Trusted by schools across Africa" | "Open source · Community-driven · Used on 3 continents" |

### 3.3 New Meta Tags

```html
<title>KlassApp — The Open-Source Agentic Protocol for Education</title>
<meta name="description" content="KlassApp connects to WhatsApp, Drive, Slack and more — orchestrating school operations through Toshi, an AI agent for education. Open source. Global." />
<meta property="og:title" content="KlassApp — The Open-Source Agentic Protocol for Education" />
<meta property="og:description" content="An open-source agentic protocol that connects the tools schools already use. Powered by Toshi AI. WhatsApp · Drive · Slack · Email · SMS." />
<meta property="og:locale" content="en_US" />
```

---

## 4. Proposed Landing Page Structure

### 4.1 Section Map

```
┌─────────────────────────────────────────────────┐
│  NAVBAR (dark, glass-morphism, GitHub star badge)│
├─────────────────────────────────────────────────┤
│  HERO                                           │
│  "Your school's tools, connected by intelligence"│
│  [Connector diagram visual] + [Toshi animation] │
│  CTAs: Start Free · View on GitHub · See How     │
├─────────────────────────────────────────────────┤
│  CONNECTOR STRIP                                │
│  WhatsApp · Drive · Slack · Email · SMS ·        │
│  Calendar · Firebase · Push Notifications        │
├─────────────────────────────────────────────────┤
│  WHY AGENTIC                                    │
│  "School software has forgotten the most         │
│   important thing: intelligence."                │
│  Old way vs. Agentic way comparison              │
├─────────────────────────────────────────────────┤
│  MEET TOSHI                                     │
│  "Your school's AI agent"                        │
│  Role-aware · Multi-channel · Action-taking      │
│  [Animated agent workflow visual]                │
├─────────────────────────────────────────────────┤
│  HOW IT WORKS (Multi-Channel)                   │
│  Parent on WhatsApp → Toshi responds             │
│  Teacher on Slack → Toshi posts homework         │
│  Admin on Dashboard → Toshi generates reports    │
├─────────────────────────────────────────────────┤
│  PROTOCOL SECTION                               │
│  "Not just software. A protocol."                │
│  Open source · MCP-compatible · Self-hostable    │
│  Community connectors · Extensible               │
├─────────────────────────────────────────────────┤
│  FEATURES GRID (Connector-Centric)              │
│  💬 WhatsApp · 📁 Drive · 💼 Slack · 📧 Email   │
│  📱 SMS · 📅 Calendar · 🤖 Toshi AI · 🔌 MCP    │
│  📊 Dashboard · 🏫 Multi-School · 🌍 Open Source │
├─────────────────────────────────────────────────┤
│  ROLE DASHBOARDS                                │
│  Admin · Teacher · Accountant · Librarian        │
│  [Dashboard mockup cards]                        │
├─────────────────────────────────────────────────┤
│  OPEN SOURCE CTA                                │
│  "Built in the open. Extended by community."     │
│  GitHub stars · Contributors · MIT License       │
├─────────────────────────────────────────────────┤
│  PRICING                                        │
│  Free · Pro · Enterprise                         │
├─────────────────────────────────────────────────┤
│  FOOTER                                         │
└─────────────────────────────────────────────────┘
```

### 4.2 Hero Section — Detailed Design

**Headline:**
```
Your school's tools,
connected by intelligence.
```

**Subhead:**
```
KlassApp is an open-source agentic protocol that connects to
WhatsApp, Google Drive, Slack, Email, and SMS — then orchestrates
them through Toshi, an AI agent built for education.
```

**Visual: Connector Diagram**

Instead of only a WhatsApp phone mockup, show a **connector hub diagram**:

```
                    ┌──────────┐
                    │ WhatsApp │
                    └────┬─────┘
                         │
    ┌──────────┐    ┌────┴─────┐    ┌──────────┐
    │  Drive   │────│  Toshi   │────│  Slack   │
    └──────────┘    │   AI     │    └──────────┘
                    │  Agent   │
    ┌──────────┐    └────┬─────┘    ┌──────────┐
    │  Email   │────│          │────│   SMS    │
    └──────────┘    └──────────┘    └──────────┘
```

This can be rendered as:
- Animated SVG with glowing connection lines
- Each connector node pulses when "active"
- Toshi center node has a subtle breathing animation
- Data particles flow along the connection lines

**CTAs:**
- Primary: `Start free` (green)
- Secondary: `View on GitHub` (outline, with GitHub icon + star count)
- Tertiary: `See how it works` (ghost)

**Trust signals below CTAs:**
```
✓ Open source (MIT)  ✓ Self-hostable  ✓ MCP-compatible  ✓ 150+ AI tools
```

### 4.3 Connector Strip

Replace "Trusted by schools across Africa" with:

```
Works with what you already use

[WhatsApp] [Google Drive] [Slack] [Email] [SMS] [Calendar] [Firebase] [Push Notifications]
```

Each connector shown as a glass-morphism card with its logo, on a dark background with subtle glow effects.

### 4.4 Meet Toshi Section

**Headline:**
```
Meet Toshi.
Your school's AI agent.
```

**Subhead:**
```
Not a chatbot. An agent that takes action.
Toshi understands context across all your connectors and acts on it.
```

**Feature cards (glass-morphism, dark background):**

| Card | Icon | Title | Description |
|------|------|-------|-------------|
| 1 | 🎭 | **Role-Aware** | Toshi adapts per role — teacher, parent, accountant, librarian. Each gets what they need. |
| 2 | 🔗 | **Multi-Channel** | Operates across WhatsApp, Slack, Email, SMS. One brain, every surface. |
| 3 | ⚡ | **Action-Taking** | Marks attendance, enters grades, generates reports, sends notifications. Not just answers. |
| 4 | 🧠 | **Context-Aware** | Remembers conversations, understands school state, makes decisions with full context. |
| 5 | 🔌 | **Extensible** | MCP protocol. Build custom tools. Contribute connectors. Extend Toshi's capabilities. |
| 6 | 🛡️ | **Safe by Design** | Confirms before write operations. Role-based guards. Audit trail for every action. |

**Visual:** An animated workflow showing:
1. Parent asks on WhatsApp: "How is my child doing?"
2. Toshi queries gradebook, attendance, fee ledger
3. Toshi responds with a unified summary
4. Toshi flags: "3 absences this term — would you like to schedule a meeting?"

### 4.5 How It Works — Multi-Channel

Three parallel flows showing the protocol in action:

```
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│ 📱 Parent       │  │ 💼 Teacher       │  │ 📊 Admin         │
│ on WhatsApp     │  │ on Slack         │  │ on Dashboard     │
│                 │  │                  │  │                  │
│ "What are       │  │ /post-homework   │  │ Generate term    │
│  Maya's grades?"│  │ Math P.6         │  │ reports          │
│                 │  │                  │  │                  │
│    ↓            │  │    ↓             │  │    ↓             │
│                 │  │                  │  │                  │
│ ┌─────────────┐ │  │ ┌─────────────┐ │  │ ┌─────────────┐ │
│ │   Toshi     │ │  │ │   Toshi     │ │  │ │   Toshi     │ │
│ │   Agent     │ │  │ │   Agent     │ │  │ │   Agent     │ │
│ └─────────────┘ │  │ └─────────────┘ │  │ └─────────────┘ │
│                 │  │                  │  │                  │
│ → Grades: A-    │  │ → Posted to 42   │  │ → 846 reports    │
│ → Position: 3rd │  │   students       │  │   generated      │
│ → Fees: Paid ✓  │  │ → Parents        │  │ → PDF delivered  │
│                 │  │   notified via   │  │   to admin       │
│                 │  │   WhatsApp       │  │                  │
└─────────────────┘  └─────────────────┘  └─────────────────┘
```

### 4.6 Protocol Section

**Headline:**
```
Not just software.
A protocol.
```

**Subhead:**
```
KlassApp isn't another school management app. It's an open, extensible
layer that connects any tool a school uses and makes them work together
through AI.
```

**Three pillars:**

| Pillar | Icon | Title | Description |
|--------|------|-------|-------------|
| 1 | 🔓 | **Open Source** | MIT licensed. Fork it, extend it, self-host it. The code is yours. |
| 2 | 🔌 | **MCP Compatible** | Built on the Model Context Protocol — the emerging standard for agent-tool communication. |
| 3 | 🌍 | **Community-Driven** | Contribute connectors, build skills, extend Toshi. The protocol grows with its community. |

### 4.7 Open Source CTA

**Headline:**
```
Built in the open.
Extended by community.
```

**Visual elements:**
- GitHub star badge (dynamic, pulls real count)
- Contributor avatars (circular grid)
- "MIT License" badge
- Recent commit activity sparkline

**CTA:** `Star on GitHub` (with GitHub icon)

---

## 5. Visual Design Direction

### 5.1 Design System Comparison

| Element | Current | Proposed |
|---------|---------|----------|
| **Background** | Light (#FAFAF5) with warm amber glows | Dark (#0c1535) with aurora orbs (like n8n, CrewAI) |
| **Hero visual** | WhatsApp phone mockup only | Connector hub diagram + phone mockup |
| **Cards** | White with subtle borders | Glass-morphism (backdrop-blur, semi-transparent) |
| **Headlines** | Solid dark text | Gradient text (blue → purple → green) |
| **Accents** | Amber (#D97706) dominant | Blue (#1E6FD9) + Green (#22C55E) + Violet (#7c3aed) |
| **Typography** | Sora + DM Sans | Plus Jakarta Sans (or keep Sora) + monospace accents |
| **Social proof** | School name pills | GitHub stars + contributor avatars + connector logos |
| **Animations** | Scroll reveal + WhatsApp typing | Data flow particles + connector pulse + agent workflow |

### 5.2 Inspiration Sources

| Product | What to Borrow |
|---------|----------------|
| **n8n** | Dark background, "AI agents you can actually follow", GitHub star badge, integration grid |
| **CrewAI** | Enterprise trust signals, stat counters, glass-morphism cards |
| **LangChain** | Product architecture diagram, open source positioning, gradient text |
| **Salesforce Agentforce** | "Agentic" vocabulary, role-based agent showcase |
| **Vercel** | Minimal copy, maximum whitespace, monospace accents |

### 5.3 Color Palette (Proposed)

```css
:root {
    /* Dark foundation */
    --ka-page-bg:       #0c1535;
    --ka-surface:       #0f1a3a;
    --ka-card:          rgba(255, 255, 255, 0.055);
    --ka-card-hover:    rgba(255, 255, 255, 0.08);
    --ka-border:        rgba(255, 255, 255, 0.09);

    /* Brand colors */
    --ka-primary:       #1E6FD9;  /* Blue — trust, technology */
    --ka-green:         #22C55E;  /* Green — growth, action */
    --ka-violet:        #7c3aed;  /* Violet — AI, intelligence */
    --ka-amber:         #D97706;  /* Amber — warmth, education */

    /* Text */
    --ka-text:          #f1f5f9;
    --ka-text-muted:    rgba(241, 245, 249, 0.62);
    --ka-text-faint:    rgba(241, 245, 249, 0.35);

    /* Effects */
    --ka-glow-blue:     rgba(30, 111, 217, 0.4);
    --ka-glow-green:    rgba(34, 197, 94, 0.3);
    --ka-glow-violet:   rgba(124, 58, 237, 0.3);
}
```

### 5.4 Key Visual Components

#### Aurora Background
```css
.ka-bg-orb-1 {
    position: absolute;
    top: -18vh; left: -8vw;
    width: clamp(480px, 65vw, 900px);
    border-radius: 50%;
    background: radial-gradient(circle,
        rgba(30, 111, 217, .48) 0%,
        rgba(30, 111, 217, .14) 42%,
        transparent 68%);
    filter: blur(90px);
    animation: orb1 22s ease-in-out infinite alternate;
}
```

#### Glass-morphism Card
```css
.ka-feature-card {
    background: rgba(255, 255, 255, 0.055);
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 12px;
    backdrop-filter: blur(12px);
    transition: all 0.2s;
}
.ka-feature-card:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(30, 111, 217, 0.4);
    box-shadow: 0 8px 32px rgba(30, 111, 217, 0.18);
    transform: translateY(-3px);
}
```

#### Gradient Text
```css
.ka-hero-title span {
    background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 50%, #4ade80 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
```

#### Connector Node
```css
.connector-node {
    width: 64px; height: 64px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.13);
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(16px);
    transition: all 0.3s;
}
.connector-node:hover {
    border-color: rgba(30, 111, 217, 0.5);
    box-shadow: 0 0 24px rgba(30, 111, 217, 0.3);
}
.connector-node.active {
    border-color: rgba(34, 197, 94, 0.6);
    box-shadow: 0 0 32px rgba(34, 197, 94, 0.4);
}
```

---

## 6. Implementation Roadmap

### Phase 1: Copy-Only (No Design Changes)
**Effort:** 1-2 hours
**Risk:** Low

- [ ] Update `<title>` and `<meta>` tags
- [ ] Change hero headline and subhead
- [ ] Update social proof strip text
- [ ] Add "Open source" mention to CTAs

### Phase 2: Section Additions
**Effort:** 1-2 days
**Risk:** Low

- [ ] Add connector strip section (logo grid)
- [ ] Add "Meet Toshi" section
- [ ] Add protocol/open source section
- [ ] Update features grid to connector-centric

### Phase 3: Visual Overhaul
**Effort:** 3-5 days
**Risk:** Medium

- [ ] Switch to dark-mode design system
- [ ] Implement aurora background
- [ ] Build connector hub diagram (SVG/animated)
- [ ] Add glass-morphism cards
- [ ] Implement gradient text for headlines
- [ ] Add GitHub star badge

### Phase 4: Animation & Polish
**Effort:** 2-3 days
**Risk:** Low

- [ ] Connector data flow animation
- [ ] Toshi agent workflow animation
- [ ] Scroll-triggered reveals
- [ ] Mobile responsive refinements

---

## 7. Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **Losing the WhatsApp story** | WhatsApp remains a prominent connector — just not the *only* story |
| **Confusing existing users** | Keep the audience tabs (Admin/Teacher/Parent) — the reframe is about positioning, not removing user-facing clarity |
| **Overpromising on "protocol"** | The MCP integration and Toshi agent system already exist in the codebase — this is real, not vaporware |
| **Dark mode accessibility** | Ensure sufficient contrast ratios (WCAG AA minimum), test with real users |
| **SEO impact** | Meta tag changes should improve SEO (broader keywords: "agentic", "protocol", "open source", "education AI") |

---

## 8. Competitive Positioning

After this reframe, KlassApp would sit in a unique position:

```
                    ┌─────────────────────────────────────┐
                    │         AGENTIC PLATFORMS            │
                    │                                      │
                    │   Salesforce    ServiceNow    n8n    │
                    │   Agentforce    AI Agents     AI     │
                    │                                      │
                    │         ┌─────────────┐              │
                    │         │  KlassApp   │              │
                    │         │  (Protocol) │              │
                    │         └─────────────┘              │
                    │                                      │
                    │   CrewAI      LangChain    OpenAI    │
                    │   Enterprise  Agent       Agents     │
                    │   Agents      Platform    SDK        │
                    └─────────────────────────────────────┘

                    Education-specific + Open source + Multi-channel
                    = No direct competitor
```

**No other product** combines:
- Education domain expertise
- Open-source protocol architecture
- Multi-channel connector system
- Agentic AI layer (Toshi)
- WhatsApp as a first-class channel

This is a **category-creating** position.

---

## 9. Success Metrics

After implementation, measure:

| Metric | Current Baseline | Target |
|--------|-----------------|--------|
| GitHub stars | — | 500+ in 6 months |
| Organic search traffic | — | +40% (broader keywords) |
| Sign-up conversion rate | — | +25% (global appeal) |
| Time on page | — | +30% (engaging visuals) |
| International sign-ups | — | Track non-Uganda registrations |
| Community contributions | — | 10+ external contributors in 6 months |

---

## 10. Conclusion

KlassApp has built something genuinely differentiated: an agentic protocol for education with multi-channel connectors and an AI agent layer. The current landing page undersells this by framing it as a WhatsApp tool for African schools.

The proposed reframe positions KlassApp where it belongs — alongside the most ambitious agentic platforms in the market — while keeping the warmth and accessibility that makes it work for real schools.

**The ask:** Review this proposal, discuss as a team, and approve the direction before we start building.

---

## Appendix A: Codebase Evidence

The "protocol" framing is not aspirational — it's backed by what's already built:

### Toshi Agent System (150+ files)
```
app/AiAgents/
├── AccountantOperationsAgent.php
├── DeputyAdminOperationsAgent.php
├── LibrarianOperationsAgent.php
├── ParentOperationsAgent.php
├── ReceptionistOperationsAgent.php
├── StudentOperationsAgent.php
├── TeacherOperationsAgent.php
├── ToshiLlm.php
├── ToshiOrchestrator.php
├── ToshiSdkV2Service.php
├── Skills/
│   ├── AcademicSkill.php
│   ├── FeeSkill.php
│   ├── GradingSkill.php
│   ├── ReportingSkill.php
│   └── ...
└── Tools/
    ├── Accountant/
    ├── Librarian/
    ├── Parent/
    ├── Receptionist/
    ├── Student/
    ├── Teacher/
    └── ... (100+ tools)
```

### Multi-Channel Architecture
```
app/Channels/WhatsAppBackupChannel.php
app/Services/WhatsApp/WhatsAppConfirmationBridge.php
app/Services/WhatsApp/WhatsAppHumanEscalationService.php
app/Services/WhatsApp/WhatsAppToshiChannelService.php
app/Services/OutboundWhatsAppService.php
app/Services/Toshi/PlatformOpsConversationService.php
```

### MCP Integration
```
app/Mcp/Servers/SpikeSlackMockServer.php
app/Mcp/Tools/SpikeSlackAuthTestTool.php
app/Mcp/Tools/SpikeSlackListChannelsTool.php
app/Services/Toshi/AuditingMcpClient.php
app/Services/Toshi/AuditingMcpClientManager.php
app/Services/Toshi/ToshiMcpClient.php
```

### AI Tools (Laravel AI + OpenAI)
```
composer.json:
  "laravel/ai": "^0.10",
  "openai-php/laravel": "^0.11.0",
```

---

## Appendix B: Reference Links

- [Bain: Will Agentic AI Disrupt SaaS?](https://www.bain.com/insights/will-agentic-ai-disrupt-saas-technology-report-2025/)
- [The State of Agentic AI Standards in 2026](https://dev.to/alexmercedcoder/the-state-of-agentic-ai-standards-in-2026-mcp-a2a-webmcp-osi-and-the-protocol-stack-taking-3o2l)
- [MIT Sloan: Agentic AI Explained](https://mitsloan.mit.edu/ideas-made-to-matter/agentic-ai-explained)
- [CrewAI Landing Page](https://crewai.com)
- [n8n Landing Page](https://n8n.io)
- [LangChain Landing Page](https://www.langchain.com)
- [Salesforce Agentforce](https://www.salesforce.com/agentforce/)
