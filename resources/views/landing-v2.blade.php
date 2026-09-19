<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KlassApp: The school platform for tools educationists already use</title>
    <meta name="description" content="KlassApp is an open-source agentic school protocol. It operates in the tools educationists already use: WhatsApp, Drive, Slack, email, and more.">
    <link rel="canonical" href="{{ url()->current() }}">
    @include('layouts.partials.favicon')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700&display=swap" rel="stylesheet">

    @vite(['resources/css/landing-preview.css', 'resources/js/landing-preview.js'])
</head>
<body>
{{-- Live cutover: Open Design klassapp-landing-v3 on /. Legacy /landing-preview redirects here. --}}
<nav class="navbar" id="navbar">
  <div class="navbar-inner">
    <a href="{{ url('/') }}#hero" class="navbar-logo" aria-label="KlassApp">
      <img src="{{ asset('images/klassapp-logo-primary.svg') }}" alt="KlassApp" class="navbar-logo-img" width="36" height="36" />
    </a>
    <ul class="navbar-links">
      <li><a href="#connectors">Integrations</a></li>
      <li><a href="#toshi">Toshi</a></li>
      <li><a href="#how-it-works">How it works</a></li>
      <li><a href="#trust">Protocol Cores</a></li>
      <li><a href="#compare">What we address</a></li>
      <li><a href="#faq">FAQ</a></li>
    </ul>
    <a href="{{ url('/register') }}" class="navbar-cta">Start free</a>
    <button type="button" class="navbar-mobile-toggle" id="navbarMobileToggle" aria-label="Menu" aria-expanded="false" aria-controls="navbarMobilePanel">
      <svg class="icon-open" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
      <svg class="icon-close" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  </div>
  <div class="navbar-mobile-panel" id="navbarMobilePanel" hidden>
    <ul class="navbar-mobile-links">
      <li><a href="#connectors">Integrations</a></li>
      <li><a href="#toshi">Toshi</a></li>
      <li><a href="#how-it-works">How it works</a></li>
      <li><a href="#trust">Protocol Cores</a></li>
      <li><a href="#compare">What we address</a></li>
      <li><a href="#faq">FAQ</a></li>
      <li><a href="{{ url('/register') }}" class="navbar-mobile-cta">Start free</a></li>
    </ul>
  </div>
</nav>

<section class="hero" id="hero">
  {{-- Vintage paper v2 (Open Design klassapp-landing-v3-hero-vintage-paper-v2): fainter grain + ledger rules --}}
  <div class="hero-bg-vintage" aria-hidden="true"></div>
  <div class="hero-deckle" aria-hidden="true">
    <svg viewBox="0 0 1200 700" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0,12 C18,4 32,22 48,10 C64,-2 78,18 96,8 C112,0 128,20 148,6 C168,-4 184,16 204,10 C224,2 240,22 260,8 C280,-2 296,18 316,12 C336,4 352,24 372,10 C392,0 408,20 428,8 C448,-2 464,18 484,12 C504,4 520,22 540,10 C560,0 576,18 596,8 C616,-2 632,20 652,10 C672,2 688,22 708,8 C728,-2 744,16 764,12 C784,4 800,24 820,10 C840,0 856,18 876,8 C896,-2 912,20 932,12 C952,4 968,22 988,10 C1008,0 1024,18 1044,8 C1064,-2 1080,20 1100,10 C1120,2 1136,22 1156,8 C1172,0 1188,14 1200,6 L1200,0 L0,0 Z" fill="rgba(180,155,110,0.11)"/>
      <path d="M0,700 L0,688 C12,692 24,680 40,690 C56,696 72,682 88,690 C104,698 120,684 136,690 C152,696 168,682 184,690 C200,698 216,684 232,690 C248,696 264,682 280,690 C296,698 312,684 328,690 C344,696 360,682 376,690 C392,698 408,684 424,690 C440,696 456,682 472,690 C488,698 504,684 520,690 C536,696 552,682 568,690 C584,698 600,684 616,690 C632,696 648,682 664,690 C680,698 696,684 712,690 C728,696 744,682 760,690 C776,698 792,684 808,690 C824,696 840,682 856,690 C872,698 888,684 904,690 C920,696 936,682 952,690 C968,698 984,684 1000,690 C1016,696 1032,682 1048,690 C1064,698 1080,684 1096,690 C1112,696 1128,682 1144,690 C1160,698 1176,684 1192,692 L1200,688 L1200,700 Z" fill="rgba(160,135,95,0.1)"/>
      <path d="M0,0 L8,0 C4,40 14,80 6,120 C-2,160 12,200 8,240 C4,280 14,320 6,360 C-2,400 12,440 8,480 C4,520 14,560 6,600 C0,640 10,680 4,700 L0,700 Z" fill="rgba(140,115,75,0.07)"/>
    </svg>
  </div>
  <div class="container">
    <div class="hero-inner">
      <div class="hero-content reveal">
        <div class="hero-kicker">Open-source agentic school protocol</div>
        <h1>The school platform that operates in the tools educationists already use.</h1>
        <p class="hero-sub">Toshi orchestrates WhatsApp, Drive, Slack, and email: one AI agent built for how schools actually work.</p>
        <div class="hero-actions">
          <a href="{{ url('/register') }}" class="btn btn-primary">Start free <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></a>
          <a href="#protocol" class="btn btn-secondary">Get notified when we open source</a>
        </div>
        <div class="hero-trust">
          <span>Free and open (MIT)</span><span class="dot"></span>
          <span>Self-hostable</span><span class="dot"></span>
          <span>MCP-compatible</span>
        </div>
      </div>
      {{-- Rotating role preview — dense product-proof panel (WS-1). Parent / Teacher / Admin. --}}
      <div class="hero-preview reveal reveal-delay-2">
        <div class="hero-device">
          <div class="hero-device-frame">
            <div class="hero-device-chrome" aria-hidden="true"><span></span><span></span><span></span><em>Toshi · live preview</em></div>
            <div class="hero-screen">
        <div class="hero-stage" id="heroRoleStage">
          <span class="hero-layer hero-layer-back" aria-hidden="true"></span>
          <span class="hero-layer hero-layer-mid" aria-hidden="true"></span>
          <div class="hero-deck" id="heroRoleDeck" data-auto="1">

            <article class="hero-role-card wa is-active" data-role="parent" aria-label="Parent WhatsApp preview">
              <div class="hero-role-hd">
                <span class="hero-role-avatar"><img src="{{ asset('images/klassapp-icon.svg') }}" alt="" width="24" height="24"></span>
                <span class="hero-role-title">Parent · WhatsApp</span>
                <span class="hero-chip live"><span class="hero-chip-dot"></span>Live</span>
              </div>
              <div class="hero-role-body">
                <div class="hero-bubble">Good morning! P.4 attendance is in — Amina is present today.</div>
                <div class="hero-bubble out">Thanks. Fee reminder for next week too?</div>
                <div class="hero-bubble">Scheduled on WhatsApp for Monday 8:00 AM.<span class="hero-meta">8:14</span></div>
                <div class="hero-row"><span>P.4 attendance · Amina</span><span class="hero-tag ok">Present</span></div>
                <div class="hero-row"><span>Absence follow-up</span><span class="hero-tag wait">Draft ready</span></div>
                <div class="hero-ledger">
                  <div class="hero-ledger-row"><span>Term 2 balance</span><strong>UGX 85,000</strong></div>
                  <div class="hero-bar"><span style="width:46%"></span></div>
                  <div class="hero-ledger-foot"><span class="hero-chip">Reminder set</span><span class="hero-meta">Due Friday</span></div>
                </div>
              </div>
            </article>

            <article class="hero-role-card drive" data-role="teacher" aria-label="Teacher Drive preview" aria-hidden="true">
              <div class="hero-role-hd">
                <span class="hero-role-avatar"><img src="{{ asset('images/klassapp-icon.svg') }}" alt="" width="24" height="24"></span>
                <span class="hero-role-title">Teacher · Drive</span>
                <span class="hero-chip sync">Syncing</span>
              </div>
              <div class="hero-role-body">
                <div class="hero-row"><span>P.4 Midterm report.pdf</span><span class="hero-tag ok">Ready</span></div>
                <div class="hero-row"><span>Marks sheet · Term 2</span><span class="hero-tag ok">Synced</span></div>
                <div class="hero-row"><span>Share to parents</span><span class="hero-tag wait">Queued</span></div>
                <div class="hero-row"><span>Class photo album</span><span class="hero-tag ok">Filed</span></div>
                <div class="hero-row"><span>P.5 marks · Term 2</span><span class="hero-tag wait">Needs entry</span></div>
                <div class="hero-progress">
                  <div class="hero-progress-top"><span>Report pack</span><span>3 / 4 uploaded</span></div>
                  <div class="hero-bar"><span style="width:75%"></span></div>
                  <div class="hero-progress-top"><span>Attendance sheet</span><span>Uploaded</span></div>
                  <div class="hero-bar blue"><span style="width:100%"></span></div>
                </div>
                <p class="hero-meta">Toshi filed the PDF to the class Drive folder after you confirmed.</p>
              </div>
            </article>

            <article class="hero-role-card slack" data-role="admin" aria-label="Admin Slack preview" aria-hidden="true">
              <div class="hero-role-hd">
                <span class="hero-role-avatar"><img src="{{ asset('images/klassapp-icon.svg') }}" alt="" width="24" height="24"></span>
                <span class="hero-role-title">Admin · Slack</span>
                <span class="hero-chip wait">Needs Yes</span>
              </div>
              <div class="hero-role-body">
                <div class="hero-row"><span>#school-ops</span><span class="hero-meta">Today</span></div>
                <div class="hero-bubble">Fee collection is at 86% — 3 reminders left for overdue families.</div>
                <div class="hero-row"><span>Overdue · 3 families</span><span class="hero-tag wait">Reminder queued</span></div>
                <div class="hero-progress">
                  <div class="hero-progress-top"><span>Fee collection</span><span>86%</span></div>
                  <div class="hero-bar"><span style="width:86%"></span></div>
                  <div class="hero-progress-top"><span>Attendance</span><span>94%</span></div>
                  <div class="hero-bar blue"><span style="width:94%"></span></div>
                  <div class="hero-progress-top"><span>Staff mark entry</span><span>2 / 12 left</span></div>
                  <div class="hero-bar"><span style="width:83%"></span></div>
                  <div class="hero-progress-top"><span>Transport routes</span><span>5 / 6 confirmed</span></div>
                  <div class="hero-bar blue"><span style="width:83%"></span></div>
                </div>
                <p class="hero-meta">Posted by Toshi · needs your Yes to escalate.</p>
              </div>
            </article>
          </div>
        </div>
            </div>
          </div>
          <div class="hero-device-base" aria-hidden="true"></div>
        </div>
        <div class="hero-role-dots" id="heroRoleDots" role="tablist" aria-label="Preview role"></div>
        <div class="connector-float">
          <h4>Connected</h4>
          <div class="connector-icons">
            <span class="connector-icon brand-well" role="img" aria-label="WhatsApp"><x-brand.whatsapp /></span>
            <span class="connector-icon brand-well" role="img" aria-label="Google Drive"><x-brand.google-drive /></span>
            <span class="connector-icon brand-well" role="img" aria-label="Slack"><x-brand.slack /></span>
            <span class="connector-icon" role="img" aria-label="Email"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E6FD9" stroke-width="2" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg></span>
            <span class="connector-icon" role="img" aria-label="SMS"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></span>
            <span class="connector-icon" role="img" aria-label="Calendar"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E6FD9" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
          </div>
        </div>
      </div>
      </div>
    </div>
  </div>
</section>

<section class="connectors" id="connectors">
  <div class="container">
    <div class="connectors-header reveal">
      <h2>Works with what you already use</h2>
      <p>KlassApp doesn't replace your tools. It connects them into a single intelligent layer.</p>
    </div>

    <div class="orchestration-panel reveal">
      <div class="panel-chrome">
        <div class="panel-chrome-left">
          <div class="panel-dots"><span></span><span></span><span></span></div>
          <div class="panel-title">Toshi · Connector Orchestration</div>
        </div>
        <div class="panel-status"><span class="status-dot"></span> 6 active</div>
      </div>
      <div class="panel-body">
        <div class="panel-table-header">
          <span>Connector</span>
          <span>Latest activity</span>
          <span>Today</span>
          <span>Status</span>
        </div>
        <div class="panel-row">
          <div class="panel-connector"><div class="panel-connector-icon whatsapp brand-well"><x-brand.whatsapp /></div> WhatsApp</div>
          <div class="panel-flow"><strong>Fee reminder</strong> sent to 24 parents · P.4 group</div>
          <div class="panel-metric">142 msg</div>
          <span class="panel-badge live">Live</span>
        </div>
        <div class="panel-row">
          <div class="panel-connector"><div class="panel-connector-icon drive brand-well"><x-brand.google-drive /></div> Google Drive</div>
          <div class="panel-flow"><strong>Weekly report</strong> uploaded to /reports/term-2/</div>
          <div class="panel-metric">8 files</div>
          <span class="panel-badge sync">Sync</span>
        </div>
        <div class="panel-row">
          <div class="panel-connector"><div class="panel-connector-icon slack brand-well"><x-brand.slack /></div> Slack</div>
          <div class="panel-flow"><strong>#staff-alerts</strong> · 3 absences flagged for review</div>
          <div class="panel-metric">12 posts</div>
          <span class="panel-badge live">Live</span>
        </div>
        <div class="panel-row">
          <div class="panel-connector"><div class="panel-connector-icon email"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-blue)" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg></div> Email</div>
          <div class="panel-flow"><strong>Newsletter draft</strong> ready for headteacher approval</div>
          <div class="panel-metric">1 draft</div>
          <span class="panel-badge idle">Queue</span>
        </div>
        <div class="panel-row">
          <div class="panel-connector"><div class="panel-connector-icon sms"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-green)" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></div> SMS</div>
          <div class="panel-flow"><strong>SMS fallback</strong> for 2 parents without WhatsApp</div>
          <div class="panel-metric">2 sent</div>
          <span class="panel-badge live">Live</span>
        </div>
        <div class="panel-row">
          <div class="panel-connector"><div class="panel-connector-icon calendar"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-blue)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div> Calendar</div>
          <div class="panel-flow"><strong>Parent-teacher meetings</strong> scheduled next week</div>
          <div class="panel-metric">18 events</div>
          <span class="panel-badge sync">Sync</span>
        </div>
      </div>
    </div>

    <div class="connector-grid-compact reveal reveal-delay-1">
      <div class="connector-chip ka-node n-green"><x-brand.whatsapp /> WhatsApp</div>
      <div class="connector-chip ka-node n-amber"><x-brand.google-drive /> Drive</div>
      <div class="connector-chip ka-node n-violet"><x-brand.slack /> Slack</div>
      <div class="connector-chip ka-node n-blue"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-blue)" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg> Email</div>
      <div class="connector-chip ka-node n-green"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-green)" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg> SMS</div>
      <div class="connector-chip ka-node n-blue"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-blue)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Calendar</div>
    </div>
  </div>
</section>

<section class="toshi" id="toshi">
  <div class="container">
    <div class="toshi-header reveal">
      <div class="toshi-badge">AI Agent</div>
      <h2>Meet <span class="toshi-name">Toshi</span>. Your school's AI agent.</h2>
      <p>Not a chatbot. An action-taking agent that understands education workflows and orchestrates across every connected tool.</p>
    </div>

    @include('partials.landing-toshi-tower')

    <div class="toshi-hitl reveal" role="note">
      <div class="toshi-hitl-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M9 12l2 2 4-4"/><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/></svg>
      </div>
      <div class="toshi-hitl-body">
        <strong>Human in the loop</strong>
        <p>Before consequential writes, Toshi asks for confirmation. You approve, then it acts. Creation tools stay on an explicit Yes/No gate so a person remains in control.</p>
      </div>
    </div>

    <div class="toshi-grid">
      <div class="toshi-card reveal reveal-delay-1"><div class="toshi-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="8" r="4"/><path d="M6 20v-1a6 6 0 0112 0v1"/></svg></div><h3>Role-Aware</h3><p>Toshi adapts its responses based on who's asking: teacher, parent, admin, or student. Each role gets exactly what they need.</p></div>
      <div class="toshi-card reveal reveal-delay-2"><div class="toshi-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg></div><h3>Multi-Channel</h3><p>Works across WhatsApp, email, Slack, SMS, and the dashboard simultaneously. One agent, every channel your school uses.</p></div>
      <div class="toshi-card violet-accent reveal reveal-delay-3"><div class="toshi-card-icon" style="color: var(--violet-accent);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></div><h3>Action-Taking</h3><p>Doesn't just answer questions: sends messages, generates reports, updates records, and schedules follow-ups automatically.</p></div>
      <div class="toshi-card amber-accent reveal reveal-delay-4"><div class="toshi-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 2a7 7 0 017 7c0 2.5-1.5 4.5-3 6l-1.5 2.5a1 1 0 01-1.7 0L11 15c-1.5-1.5-3-3.5-3-6a7 7 0 017-7z"/><circle cx="12" cy="9" r="2"/></svg></div><h3>Context-Aware</h3><p>Remembers term dates, student histories, school policies, and communication patterns. Every interaction builds on the last.</p></div>
      <div class="toshi-card reveal reveal-delay-5"><div class="toshi-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22v-5"/><path d="M9 8V2"/><path d="M15 8V2"/><path d="M18 8v5a6 6 0 01-12 0V8z"/></svg></div><h3>Extensible</h3><p>Connect new tools through MCP servers or custom connectors. Toshi's capabilities grow with your school's needs.</p></div>
      <div class="toshi-card violet-accent reveal reveal-delay-5"><div class="toshi-card-icon" style="color: var(--violet-accent);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div><h3>Safe by Design</h3><p>Role-based access, audit trails, and human-in-the-loop approvals. Toshi never acts without clear boundaries and oversight.</p></div>
    </div>
  </div>
</section>

<section class="how-it-works" id="how-it-works">
  <div class="container">
    <div class="how-header reveal">
      <h2>How it works</h2>
      <p>One protocol layer. Toshi orchestrates WhatsApp, Drive, and Slack as a single connected system, so every role sees the same school through the channel they already use.</p>
    </div>

    <div class="how-flow" id="how-flow">
      <div class="how-hub reveal">
        <div class="how-hub-node"><span class="hub-pulse"></span> Toshi · protocol orchestration</div>
      </div>

      <div class="how-columns">
        <!-- 01 Parent -->
        <div class="how-column reveal reveal-delay-1">
          <div class="how-preview" aria-hidden="true">
            <div class="ui-chrome">
              <span class="ui-dot"></span><span class="ui-dot"></span><span class="ui-dot"></span>
              <span class="ui-chrome-title">WhatsApp · Parent</span>
            </div>
            <div class="wa-body">
              <div class="wa-header">
                <div class="wa-avatar">KA</div>
                <div>
                  <div class="wa-name">KlassApp · Toshi</div>
                  <div class="wa-status">online</div>
                </div>
              </div>
              <div class="wa-bubble in">
                <strong>Fee reminder</strong>: Nakato has a balance of UGX 85,000 for Term 2.
                <span class="link">Pay securely →</span>
                <span class="wa-time">7:02 AM</span>
              </div>
              <div class="wa-bubble out">
                Received, thank you. Paying tonight.
                <span class="wa-time">7:14 AM ✓✓</span>
              </div>
              <div class="wa-bubble in">
                Nakato was marked absent today (P.4). Reply with a reason and I’ll notify the teacher.
                <span class="wa-chip">Absentee alert</span>
                <span class="wa-time">8:21 AM</span>
              </div>
            </div>
          </div>

          <div class="how-column-body">
            <div class="how-column-num">01</div>
            <div class="how-column-label">
              <span class="role-icon parent">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M6 20v-1a6 6 0 0112 0v1"/></svg>
              </span>
              Parent on WhatsApp
            </div>
            <div class="how-steps">
              <div class="how-step" data-delay="0">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">01</span> Fee reminder</div>
                <p>Through the WhatsApp connector, Toshi sends a gentle fee balance reminder in the parent's preferred language, with a payment link.</p>
              </div>
              <div class="how-step" data-delay="400">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">02</span> Absentee alert</div>
                <p>When a child is marked absent, Toshi messages the parent on WhatsApp and relays any reply back to the teacher dashboard, keeping both sides on the same protocol path.</p>
              </div>
              <div class="how-step orchestrate" data-delay="800">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">03</span> Protocol path</div>
                <p>One connected flow: WhatsApp → SMS fallback → Dashboard notification → Teacher update.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 02 Teacher -->
        <div class="how-column reveal reveal-delay-2">
          <div class="how-preview" aria-hidden="true">
            <div class="ui-chrome">
              <span class="ui-dot"></span><span class="ui-dot"></span><span class="ui-dot"></span>
              <span class="ui-chrome-title">KlassApp · P.4 Attendance</span>
            </div>
            <div class="teach-shell">
              <div class="teach-side">
                <div class="teach-side-mark">K</div>
                <div class="teach-nav-dot active"></div>
                <div class="teach-nav-dot"></div>
                <div class="teach-nav-dot"></div>
              </div>
              <div class="teach-main">
                <div class="teach-kpis">
                  <div class="kpi-chip">
                    <div class="label">Present</div>
                    <div class="val green">38</div>
                  </div>
                  <div class="kpi-chip">
                    <div class="label">Absent</div>
                    <div class="val amber">3</div>
                  </div>
                  <div class="kpi-chip">
                    <div class="label">Late</div>
                    <div class="val blue">1</div>
                  </div>
                </div>
                <div class="teach-table">
                  <div class="teach-table-head">
                    <span>Student</span><span>Status</span><span></span>
                  </div>
                  <div class="teach-row">
                    <span class="name">Nakato A.</span>
                    <span class="status">Absent</span>
                    <span class="action">Review</span>
                  </div>
                  <div class="teach-row">
                    <span class="name">Okello J.</span>
                    <span class="status">Absent</span>
                    <span class="action">Review</span>
                  </div>
                  <div class="teach-row">
                    <span class="name">Auma S.</span>
                    <span class="status">Absent</span>
                    <span class="action">Draft</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="how-column-body">
            <div class="how-column-num">02</div>
            <div class="how-column-label">
              <span class="role-icon teacher">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1E6FD9" stroke-width="2"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>
              </span>
              Teacher
            </div>
            <div class="how-steps">
              <div class="how-step" data-delay="200">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">01</span> Attendance summary</div>
                <p>From the dashboard, Toshi compiles daily attendance, identifies patterns, and drafts absence follow-ups for teacher review before the protocol sends them.</p>
              </div>
              <div class="how-step" data-delay="600">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">02</span> Report generation</div>
                <p>End-of-term reports are auto-generated from grade data, reviewed by the teacher, stored in Drive, then delivered to parents on email and WhatsApp.</p>
              </div>
              <div class="how-step orchestrate" data-delay="1000">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">03</span> Protocol path</div>
                <p>One connected flow: Dashboard input → Google Drive storage → Parent delivery → Archive.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 03 Admin -->
        <div class="how-column reveal reveal-delay-3">
          <div class="how-preview" aria-hidden="true">
            <div class="ui-chrome">
              <span class="ui-dot"></span><span class="ui-dot"></span><span class="ui-dot"></span>
              <span class="ui-chrome-title">Admin · Weekly digest</span>
            </div>
            <div class="admin-stage">
              <div class="admin-stack">
                <div class="admin-float">
                  <div class="float-kicker">Toshi · Ready</div>
                  <div class="float-title">Term planning pack</div>
                  <div class="float-meta">Enrolment + staffing draft · PDF → Drive · Slack ping</div>
                </div>
                <div class="admin-panel">
                  <div class="panel-title">School summary · Week 12</div>
                  <div class="admin-kpis">
                    <div class="kpi-chip">
                      <div class="label">Attend.</div>
                      <div class="val green">94%</div>
                    </div>
                    <div class="kpi-chip">
                      <div class="label">Fees</div>
                      <div class="val amber">72%</div>
                    </div>
                    <div class="kpi-chip">
                      <div class="label">Msgs</div>
                      <div class="val blue">1.2k</div>
                    </div>
                  </div>
                  <div class="admin-bar-row">
                    <div class="admin-bar-label"><span>Attendance</span><span>94%</span></div>
                    <div class="admin-bar-track"><div class="admin-bar-fill blue"></div></div>
                    <div class="admin-bar-label"><span>Fee collection</span><span>72%</span></div>
                    <div class="admin-bar-track"><div class="admin-bar-fill green"></div></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="how-column-body">
            <div class="how-column-num">03</div>
            <div class="how-column-label">
              <span class="role-icon admin">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1E6FD9" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
              </span>
              Admin on Dashboard
            </div>
            <div class="how-steps">
              <div class="how-step" data-delay="300">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">01</span> Weekly digest</div>
                <p>Toshi generates a weekly school summary (attendance, fees, communication metrics), emails leadership, and files the same packet to Drive in one orchestration.</p>
              </div>
              <div class="how-step" data-delay="700">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">02</span> Term planning</div>
                <p>Before each term, Toshi prepares enrolment projections, staffing suggestions, and fee structure recommendations from historical data, ready to share across Slack and the dashboard.</p>
              </div>
              <div class="how-step orchestrate" data-delay="1100">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">03</span> Protocol path</div>
                <p>One connected flow: Multi-source data → Analysis → Slack alert → PDF report → Calendar event.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


<section class="trust" id="trust">
  <div class="container">
    <div class="trust-header">
      <div class="reveal">
        <div class="trust-kicker">Protocol Cores</div>
        <h2>Built the way real infrastructure should be</h2>
        <p class="lead">Five commitments that shape how KlassApp is built: four live platform primitives, and one honest forward direction.</p>
      </div>
    </div>

    <div class="pillars pillars-5">
      <article class="pillar reveal">
        <div class="pillar-top">
          <div class="pillar-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          </div>
          <div>
            <div class="pillar-label">Secure</div>
            <h3>Protected by design</h3>
          </div>
        </div>
        <p class="pillar-blurb">Your school's data is protected by design, not by promise. Access controls are built into the platform itself, not added as an afterthought.</p>
      </article>

      <article class="pillar reveal reveal-delay-1">
        <div class="pillar-top">
          <div class="pillar-icon blue" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 12h4l3-9 4 18 3-9h4"/></svg>
          </div>
          <div>
            <div class="pillar-label">Scalable</div>
            <h3>Grows with your school</h3>
          </div>
        </div>
        <p class="pillar-blurb">KlassApp runs on modern cloud infrastructure that grows with your school, from a single classroom to an entire district, without anyone having to manage a server.</p>
      </article>

      <article class="pillar reveal reveal-delay-2">
        <div class="pillar-top">
          <div class="pillar-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <div>
            <div class="pillar-label">Private</div>
            <h3>Scoped by default</h3>
          </div>
        </div>
        <p class="pillar-blurb">Parents see only their own children. Teachers see only the students in their own classes. These are not settings you have to configure. They are the platform primitives.</p>
      </article>

      <article class="pillar reveal reveal-delay-3">
        <div class="pillar-top">
          <div class="pillar-icon blue" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
          </div>
          <div>
            <div class="pillar-label">Interoperable</div>
            <h3>Tools you already use</h3>
          </div>
        </div>
        <p class="pillar-blurb">KlassApp connects to the tools your community already uses: WhatsApp, Drive, Slack. No need to learn new tools.</p>
      </article>

      <article class="pillar pillar-future reveal reveal-delay-4">
        <div class="pillar-top">
          <div class="pillar-icon amber" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22v-5"/><path d="M9 8V2"/><path d="M15 8V2"/><path d="M18 8v5a6 6 0 01-12 0V8z"/></svg>
          </div>
          <div>
            <div class="pillar-label">Provable <span class="pillar-badge" title="Forward direction, not a shipped feature today">Coming</span></div>
            <h3>Cryptographically verifiable records</h3>
          </div>
        </div>
        <p class="pillar-blurb">Student records are recorded progressively, term by term, year by year. We're building toward records that are cryptographically provable, so a transcript can be verified as authentic without anyone having to take our word for it.</p>
        <p class="pillar-note">Stated direction. Not a live feature yet.</p>
      </article>
    </div>
  </div>
</section>

<section class="compare" id="compare">
  <div class="container">
    <div class="compare-header reveal">
      <h2>What we actually address</h2>
      <p class="lead">Illustrative of real product capability. Not sourced from a named school.</p>
    </div>
    <div class="compare-list reveal" role="list">
      <article class="compare-card" role="listitem">
        <div class="compare-col compare-before">
          <span class="compare-label">Before</span>
          <p>Fee reminders mean phone calls and follow-up visits</p>
        </div>
        <div class="compare-col compare-after">
          <span class="compare-label">KlassApp way</span>
          <p>Automated reminders on WhatsApp, email, or Telegram, whichever channel your parents already use</p>
        </div>
      </article>
      <article class="compare-card" role="listitem">
        <div class="compare-col compare-before">
          <span class="compare-label">Before</span>
          <p>Report cards are printed and sent home, sometimes lost along the way</p>
        </div>
        <div class="compare-col compare-after">
          <span class="compare-label">KlassApp way</span>
          <p>Parents get results the moment marks are finalized, on their connected channels</p>
        </div>
      </article>
      <article class="compare-card" role="listitem">
        <div class="compare-col compare-before">
          <span class="compare-label">Before</span>
          <p>Setting up a new term means days of spreadsheet work</p>
        </div>
        <div class="compare-col compare-after">
          <span class="compare-label">KlassApp way</span>
          <p>A guided setup configures classes and streams in minutes, through any connected channel</p>
        </div>
      </article>
      <article class="compare-card" role="listitem">
        <div class="compare-col compare-before">
          <span class="compare-label">Before</span>
          <p>Every setup step means filling out forms, one field at a time</p>
        </div>
        <div class="compare-col compare-after">
          <span class="compare-label">KlassApp way</span>
          <p>Just tell Toshi what you need, in plain language, and it does the rest</p>
        </div>
      </article>
    </div>
  </div>
</section>


<section class="faq" id="faq">
  <div class="container">
    <div class="faq-header reveal">
      <h2>FAQ</h2>
    </div>
    <div class="faq-list">
      <details class="faq-item reveal">
        <summary>Do parents need to download an app?</summary>
        <p>No. Everything works through WhatsApp, a tool most families already use every day.</p>
      </details>
      <details class="faq-item reveal reveal-delay-1">
        <summary>What if our school doesn't have separate class streams?</summary>
        <p>KlassApp works the same way whether your school has one class per grade or several streams. Streams are entirely optional.</p>
      </details>
      <details class="faq-item reveal reveal-delay-2">
        <summary>Is our students' data safe?</summary>
        <p>Yes. Access is limited by design. Parents see only their own children, teachers see only their own students.</p>
      </details>
      <details class="faq-item reveal reveal-delay-3">
        <summary>Can we try it before committing?</summary>
        <p>Yes. KlassApp has a free tier so you can set up your school and see how it works before choosing a paid plan.</p>
      </details>
    </div>
  </div>
</section>

<section class="protocol" id="protocol">
  <div class="container">
    <div class="protocol-layout">
      <div class="protocol-left">
        <div class="protocol-header reveal">
          <h2>Not just software. A protocol.</h2>
          <p>KlassApp is open infrastructure: designed to be extended, self-hosted when we open source, and shaped by the education community.</p>
        </div>
      </div>
      <div class="protocol-grid">
        <div class="protocol-card ka-node n-blue reveal reveal-delay-1"><div class="protocol-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div><div class="protocol-card-body"><h3>Open Source</h3><p>MIT licensed. Source and self-hosting will open publicly after an independent security review. No vendor lock-in.</p></div></div>
        <div class="protocol-card ka-node n-violet reveal reveal-delay-2"><div class="protocol-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22v-5"/><path d="M9 8V2"/><path d="M15 8V2"/><path d="M18 8v5a6 6 0 01-12 0V8z"/></svg></div><div class="protocol-card-body"><h3>MCP Compatible</h3><p>Model Context Protocol support means any MCP-compatible AI can connect. Bring your own models.</p></div></div>
        <div class="protocol-card ka-node n-amber amber-accent reveal reveal-delay-3"><div class="protocol-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div><div class="protocol-card-body"><h3>Community-Driven</h3><p>Built by educators and engineers. Contributions welcome: connectors, translations, features.</p></div></div>
      </div>
    </div>
  </div>
</section>


{{-- Production footer block from resources/views/landing.blade.php (footer only; no Stay-in-the-loop newsletter). --}}
<footer class="site-footer">
  <div class="site-footer-wordmark" aria-hidden="true">KlassApp</div>
  <div class="container site-footer-inner">
    <div class="site-footer-row">
      <div class="site-footer-brand">
        <img src="{{ asset('images/klassapp-logo-primary.svg') }}" alt="KlassApp" class="site-footer-logo" width="56" height="56" />
        <p class="site-footer-tagline">Educationists' tools connected by intelligence.</p>
      </div>
      <nav class="site-footer-links" aria-label="Footer">
        <a href="{{ url('/terms-of-service') }}">Terms</a>
        <a href="{{ url('/privacy-policy') }}">Privacy</a>
        <a href="/docs/community/">Docs</a>
        <a href="/contact">Contact</a>
      </nav>
      <div class="site-footer-socials">
        <a href="https://x.com/Klass_App" class="site-footer-social" aria-label="KlassApp on X" rel="noopener noreferrer" target="_blank">𝕏</a>
        <a href="https://github.com/KlassApp-Foundation" class="site-footer-social" aria-label="KlassApp on GitHub" rel="noopener noreferrer" target="_blank">GH</a>
        <a href="{{ url('/contact') }}" class="site-footer-social" aria-label="Contact KlassApp">✉</a>
      </div>
    </div>
    <div class="site-footer-copy">&copy; {{ date('Y') }} KlassApp. All rights reserved.</div>
  </div>
</footer>

</body>
</html>
