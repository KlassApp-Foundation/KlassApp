<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KlassApp : The school platform for tools educationists already use</title>
    <meta name="description" content="KlassApp is an open-source agentic school protocol. It operates in the tools educationists already use: WhatsApp, Drive, Slack, email, and more.">
    <meta name="robots" content="noindex,nofollow">

    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/klassapp-logo.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/landing-preview.css', 'resources/js/landing-preview.js'])
</head>
<body>
{{-- Preview-only port of locked Open Design klassapp-landing-v3.html. Route: /landing-preview only : no live cutover. --}}
<nav class="navbar" id="navbar">
  <div class="navbar-inner">
    <a href="{{ route('landing.preview') }}#hero" class="navbar-logo">Klass<span>App</span></a>
    <ul class="navbar-links">
      <li><a href="#connectors">Integrations</a></li>
      <li><a href="#toshi">Toshi</a></li>
      <li><a href="#how-it-works">How it works</a></li>
      <li><a href="#trust">Trust</a></li>
      <li><a href="#compare">What we address</a></li>
      <li><a href="#community">Community</a></li>
      <li><a href="#faq">FAQ</a></li>
    </ul>
    <a href="{{ url('/register') }}" class="navbar-cta">Start free</a>
    <button class="navbar-mobile-toggle" aria-label="Menu">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
  </div>
</nav>

<section class="hero" id="hero">
  <div class="container">
    <div class="hero-inner">
      <div class="hero-content reveal">
        <div class="hero-kicker">Open-source agentic school protocol</div>
        <h1>The school platform that operates in the tools educationists already use.</h1>
        <p class="hero-sub">Toshi orchestrates WhatsApp, Drive, Slack, and email: one AI agent built for how schools actually work.</p>
        <div class="hero-actions">
          <a href="{{ url('/register') }}" class="btn btn-primary">Start free <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></a>
          <a href="#community" class="btn btn-secondary">Get notified when we open source</a>
        </div>
        <div class="hero-trust">
          <span>Free and open (MIT)</span><span class="dot"></span>
          <span>Self-hostable</span><span class="dot"></span>
          <span>MCP-compatible</span>
        </div>
      </div>
      <div class="phone-wrapper reveal reveal-delay-2">
        <div class="phone-frame">
          <div class="phone-notch"></div>
          <div class="phone-screen">
            <div class="phone-header">
              <div class="phone-avatar">KA</div>
              <div>
                <div class="phone-contact">KlassApp · Toshi</div>
                <div class="phone-contact-sub">online</div>
              </div>
            </div>
            <div class="phone-messages" id="phone-messages">
              <div class="typing-indicator" id="typing-indicator"><span></span><span></span><span></span></div>
              <div class="msg msg-in">Good morning! Here's today's attendance summary for P.4.<div class="msg-time">8:12 AM</div></div>
              <div class="msg msg-out">Thanks, Toshi. Any absences I should follow up on?<div class="msg-time">8:14 AM ✓✓</div></div>
              <div class="msg msg-in">3 students marked absent. I've drafted WhatsApp messages to their parents. Ready to send?<div class="msg-time">8:14 AM</div></div>
              <div class="msg msg-out">Yes, send them<div class="msg-time">8:15 AM ✓✓</div></div>
              <div class="msg msg-in">All 3 messages delivered. Parent responses will appear here.<div class="msg-time">8:15 AM</div></div>
              <div class="msg msg-in"><strong>Weekly report</strong> is ready. I'll email it to the headteacher and upload to Google Drive.<div class="msg-time">8:16 AM</div></div>
            </div>
          </div>
        </div>
        <div class="connector-float">
          <div class="connector-line"></div>
          <h4>Connected</h4>
          <div class="connector-icons">
            <div class="connector-icon" title="WhatsApp"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg></div>
            <div class="connector-icon" title="Google Drive"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E6FD9" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
            <div class="connector-icon" title="Slack"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E6FD9" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></div>
            <div class="connector-icon" title="Email"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E6FD9" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg></div>
            <div class="connector-icon" title="SMS"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></div>
            <div class="connector-icon" title="Calendar"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E6FD9" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
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
          <div class="panel-connector"><div class="panel-connector-icon whatsapp"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-green)" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg></div> WhatsApp</div>
          <div class="panel-flow"><strong>Fee reminder</strong> sent to 24 parents · P.4 group</div>
          <div class="panel-metric">142 msg</div>
          <span class="panel-badge live">Live</span>
        </div>
        <div class="panel-row">
          <div class="panel-connector"><div class="panel-connector-icon drive"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-amber)" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div> Google Drive</div>
          <div class="panel-flow"><strong>Weekly report</strong> uploaded to /reports/term-2/</div>
          <div class="panel-metric">8 files</div>
          <span class="panel-badge sync">Sync</span>
        </div>
        <div class="panel-row">
          <div class="panel-connector"><div class="panel-connector-icon slack"><svg viewBox="0 0 24 24" fill="none" stroke="var(--violet-accent)" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></div> Slack</div>
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
      <div class="connector-chip ka-node n-green"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-green)" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg> WhatsApp</div>
      <div class="connector-chip ka-node n-amber"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-amber)" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Drive</div>
      <div class="connector-chip ka-node n-violet"><svg viewBox="0 0 24 24" fill="none" stroke="var(--violet-accent)" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg> Slack</div>
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

    <div class="toshi-visual reveal">
      <div class="toshi-visual-inner">
        <div class="toshi-visual-lines" aria-hidden="true">
          <!-- Geometry is measured from the live DOM by the layout pass at the
               foot of this file, so connectors stay welded to their nodes at
               any width. viewBox is set there too. -->
          <svg preserveAspectRatio="none">
            <defs>
              <!-- Bbox-relative so each connector carries its own gradient
                   regardless of where it sits. Inbound: faint at the channel,
                   intensifying into the hub. -->
              <linearGradient id="tInGreen" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" class="stop-green" stop-opacity="0.18" /><stop offset="0.55" class="stop-green" stop-opacity="0.42" /><stop offset="1" class="stop-green" stop-opacity="0.72" />
              </linearGradient>
              <linearGradient id="tInBlue" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" class="stop-blue" stop-opacity="0.18" /><stop offset="0.55" class="stop-blue" stop-opacity="0.42" /><stop offset="1" class="stop-blue" stop-opacity="0.72" />
              </linearGradient>
              <linearGradient id="tInViolet" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" class="stop-violet" stop-opacity="0.18" /><stop offset="0.55" class="stop-violet" stop-opacity="0.44" /><stop offset="1" class="stop-violet" stop-opacity="0.75" />
              </linearGradient>
              <linearGradient id="tInAmber" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" class="stop-amber" stop-opacity="0.16" /><stop offset="0.55" class="stop-amber" stop-opacity="0.38" /><stop offset="1" class="stop-amber" stop-opacity="0.66" />
              </linearGradient>
              <!-- Outbound: strong at the hub, easing out to the role node. -->
              <linearGradient id="tOutGreen" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" class="stop-green" stop-opacity="0.70" /><stop offset="0.5" class="stop-green" stop-opacity="0.42" /><stop offset="1" class="stop-green" stop-opacity="0.36" />
              </linearGradient>
              <linearGradient id="tOutBlue" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" class="stop-blue" stop-opacity="0.70" /><stop offset="0.5" class="stop-blue" stop-opacity="0.42" /><stop offset="1" class="stop-blue" stop-opacity="0.36" />
              </linearGradient>
              <linearGradient id="tOutAmber" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" class="stop-amber" stop-opacity="0.64" /><stop offset="0.5" class="stop-amber" stop-opacity="0.38" /><stop offset="1" class="stop-amber" stop-opacity="0.32" />
              </linearGradient>
              <filter id="tGlow" x="-150%" y="-150%" width="400%" height="400%">
                <feGaussianBlur stdDeviation="2.6" result="b" />
                <feMerge><feMergeNode in="b" /><feMergeNode in="SourceGraphic" /></feMerge>
              </filter>
            </defs>

            <!-- Channels → hub. Order matches the channel cards below. -->
            <g class="t-base-in">
              <path class="toshi-line" stroke="url(#tInGreen)" />
              <path class="toshi-line" stroke="url(#tInBlue)" />
              <path class="toshi-line" stroke="url(#tInViolet)" />
              <path class="toshi-line" stroke="url(#tInGreen)" />
              <path class="toshi-line" stroke="url(#tInAmber)" />
            </g>
            <!-- Hub → roles. Order matches the role pills below. -->
            <g class="t-base-out">
              <path class="toshi-line" stroke="url(#tOutGreen)" />
              <path class="toshi-line" stroke="url(#tOutBlue)" />
              <path class="toshi-line" stroke="url(#tOutAmber)" />
            </g>

            <!-- Energy streaks, travelling in toward the hub and out to the roles -->
            <g class="t-flow-in">
              <path class="toshi-line-flow f-green" />
              <path class="toshi-line-flow f-blue" style="animation-delay: 1.1s" />
              <path class="toshi-line-flow f-violet" style="animation-delay: 0.4s" />
              <path class="toshi-line-flow f-green" style="animation-delay: 1.8s" />
              <path class="toshi-line-flow f-amber" style="animation-delay: 0.8s" />
            </g>
            <g class="t-flow-out">
              <path class="toshi-line-flow f-green" style="animation-delay: 2.2s" />
              <path class="toshi-line-flow f-blue" style="animation-delay: 1.5s" />
              <path class="toshi-line-flow f-amber" style="animation-delay: 2.6s" />
            </g>

            <!-- Glowing packets drifting toward the centre -->
            <g class="toshi-particles">
              <circle class="toshi-particle p-green"  cx="0" cy="0" r="3.2" filter="url(#tGlow)" />
              <circle class="toshi-particle p-blue"   cx="0" cy="0" r="3"   filter="url(#tGlow)" style="animation-delay: 1.1s" />
              <circle class="toshi-particle p-violet" cx="0" cy="0" r="3.4" filter="url(#tGlow)" style="animation-delay: 0.4s" />
              <circle class="toshi-particle p-green"  cx="0" cy="0" r="3"   filter="url(#tGlow)" style="animation-delay: 1.8s" />
              <circle class="toshi-particle p-amber"  cx="0" cy="0" r="3.2" filter="url(#tGlow)" style="animation-delay: 0.8s" />
            </g>
          </svg>
        </div>
        <div class="toshi-visual-channels">
          <div class="toshi-visual-channel toshi-node n-green"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-green)" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg><span>WhatsApp</span></div>
          <div class="toshi-visual-channel toshi-node n-blue"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-blue)" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg><span>Email</span></div>
          <div class="toshi-visual-channel toshi-node n-violet"><svg viewBox="0 0 24 24" fill="none" stroke="var(--violet-accent)" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span>Slack</span></div>
          <div class="toshi-visual-channel toshi-node n-green"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-green)" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg><span>SMS</span></div>
          <div class="toshi-visual-channel toshi-node n-amber"><svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-amber)" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span>Drive</span></div>
        </div>
        <div class="toshi-visual-hub">
          <div class="toshi-visual-core">Toshi</div>
          <div class="toshi-visual-label">Control Center</div>
        </div>
        <div class="toshi-visual-roles">
          <div class="toshi-visual-role toshi-node n-green"><span class="role-dot parent"></span> Parent</div>
          <div class="toshi-visual-role toshi-node n-blue"><span class="role-dot teacher"></span> Teacher</div>
          <div class="toshi-visual-role toshi-node n-amber"><span class="role-dot admin"></span> Admin</div>
        </div>
      </div>
    </div>

    <div class="toshi-grid">
      <div class="toshi-card reveal reveal-delay-1"><div class="toshi-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="8" r="4"/><path d="M6 20v-1a6 6 0 0112 0v1"/></svg></div><h3>Role-Aware</h3><p>Toshi adapts its responses based on who's asking : teacher, parent, admin, or student. Each role gets exactly what they need.</p></div>
      <div class="toshi-card reveal reveal-delay-2"><div class="toshi-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg></div><h3>Multi-Channel</h3><p>Works across WhatsApp, email, Slack, SMS, and the dashboard simultaneously. One agent, every channel your school uses.</p></div>
      <div class="toshi-card violet-accent reveal reveal-delay-3"><div class="toshi-card-icon" style="color: var(--violet-accent);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></div><h3>Action-Taking</h3><p>Doesn't just answer questions : sends messages, generates reports, updates records, and schedules follow-ups automatically.</p></div>
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
      <p>One intelligence layer orchestrating three perspectives on the same school.</p>
    </div>

    <div class="how-flow" id="how-flow">
      <div class="how-hub reveal">
        <div class="how-hub-node"><span class="hub-pulse"></span> Toshi orchestrates</div>
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
                <strong>Fee reminder</strong> : Nakato has a balance of UGX 85,000 for Term 2.
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
                <p>Toshi sends a gentle fee balance reminder via WhatsApp in the parent's preferred language, with a payment link.</p>
              </div>
              <div class="how-step" data-delay="400">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">02</span> Absentee alert</div>
                <p>When a child is marked absent, Toshi messages the parent automatically and relays any response back to the teacher.</p>
              </div>
              <div class="how-step orchestrate" data-delay="800">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">03</span> Toshi orchestrates</div>
                <p>WhatsApp → SMS fallback → Dashboard notification → Teacher update</p>
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
                <p>Toshi compiles daily attendance, identifies patterns, and drafts absence follow-ups for teacher review before sending.</p>
              </div>
              <div class="how-step" data-delay="600">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">02</span> Report generation</div>
                <p>End-of-term reports are auto-generated from grade data, reviewed by the teacher, then distributed to parents via email and WhatsApp.</p>
              </div>
              <div class="how-step orchestrate" data-delay="1000">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">03</span> Toshi orchestrates</div>
                <p>Dashboard input → Google Drive storage → Parent delivery → Archive</p>
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
                <p>Toshi generates a weekly school summary: attendance rates, fee collection, communication metrics : emailed to leadership and saved to Drive.</p>
              </div>
              <div class="how-step" data-delay="700">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">02</span> Term planning</div>
                <p>Before each term, Toshi prepares enrolment projections, staffing suggestions, and fee structure recommendations based on historical data.</p>
              </div>
              <div class="how-step orchestrate" data-delay="1100">
                <span class="how-step-dot"></span>
                <div class="how-step-title"><span class="how-step-num">03</span> Toshi orchestrates</div>
                <p>Multi-source data → Analysis → Slack alert → PDF report → Calendar event</p>
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
        <div class="trust-kicker">Infrastructure</div>
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
    <div class="compare-table-wrap reveal">
      <table class="compare-table">
        <thead>
          <tr>
            <th scope="col">Before</th>
            <th scope="col">KlassApp way</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Fee reminders mean phone calls and follow-up visits</td>
            <td>Automated reminders on WhatsApp, email, or Telegram, whichever channel your parents already use</td>
          </tr>
          <tr>
            <td>Report cards are printed and sent home, sometimes lost along the way</td>
            <td>Parents get results the moment marks are finalized, on their connected channels</td>
          </tr>
          <tr>
            <td>Setting up a new term means days of spreadsheet work</td>
            <td>A guided setup configures classes and streams in minutes, through any connected channel</td>
          </tr>
          <tr>
            <td>Every setup step means filling out forms, one field at a time</td>
            <td>Just tell Toshi what you need, in plain language, and it does the rest</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="community" id="community">
  <div class="container">
    <div class="community-layout">
      <div class="community-copy reveal">
        <div class="community-kicker">Community-driven</div>
        <h2>Built in the open, so it grows with what your school needs</h2>
        <p class="lead">KlassApp's code is public today. Before we launch widely, we're closing it briefly for an independent security review.</p>
        <p>It will be free, open, and self-hostable: you'll be able to read the source code, and if you want, run your own copy of KlassApp yourself.</p>
        <p>We built KlassApp first for the hardest real-world constraints: limited bandwidth, everyday phones, real classrooms. If it works there, it works anywhere schools need it.</p>
      </div>
      <div class="community-panel reveal reveal-delay-1">
        <form class="notify-form" action="#" method="post" onsubmit="return false;">
          <input type="email" name="email" placeholder="your@school.edu" required autocomplete="email" aria-label="Email address">
          <button type="submit" class="btn btn-primary">Get notified when we open source</button>
        </form>
        <div class="panel-actions">
          <a href="{{ url('/docs/community') }}" class="btn btn-secondary">Read the docs</a>
        </div>
        <p class="panel-note">We'll email you when source and self-hosting open publicly. No date promised here.</p>
      </div>
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
          <p>KlassApp is open infrastructure : designed to be extended, self-hosted when we open source, and shaped by the education community.</p>
        </div>
        <div class="protocol-visual reveal reveal-delay-1">
          <div class="protocol-diagram">
            <div class="protocol-orbit"></div>
            <div class="protocol-diagram-center">KA</div>
            <div class="protocol-node"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div>
            <div class="protocol-node"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22v-5"/><path d="M9 8V2"/><path d="M15 8V2"/><path d="M18 8v5a6 6 0 01-12 0V8z"/></svg></div>
            <div class="protocol-node"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div>
          </div>
        </div>
      </div>
      <div class="protocol-grid">
        <div class="protocol-card ka-node n-blue reveal reveal-delay-1"><div class="protocol-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div><div class="protocol-card-body"><h3>Open Source</h3><p>MIT licensed. Source and self-hosting will open publicly after an independent security review. No vendor lock-in.</p></div></div>
        <div class="protocol-card ka-node n-violet reveal reveal-delay-2"><div class="protocol-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22v-5"/><path d="M9 8V2"/><path d="M15 8V2"/><path d="M18 8v5a6 6 0 01-12 0V8z"/></svg></div><div class="protocol-card-body"><h3>MCP Compatible</h3><p>Model Context Protocol support means any MCP-compatible AI can connect. Bring your own models.</p></div></div>
        <div class="protocol-card ka-node n-amber amber-accent reveal reveal-delay-3"><div class="protocol-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div><div class="protocol-card-body"><h3>Community-Driven</h3><p>Built by educators and engineers. Contributions welcome : connectors, translations, features.</p></div></div>
      </div>
    </div>
  </div>
</section>

<section class="oss-cta" id="open-source">
  <div class="container">
    <div class="oss-cta-card reveal">
      <div class="oss-cta-inner">
        <h2>Free. Open. <em>Self-hostable.</em></h2>
        <p>KlassApp's code is public today. Before wide launch, we close briefly for an independent security review. Then it will be free, open, and self-hostable.</p>
        <div class="oss-badges">
          <span class="oss-badge amber">MIT License</span>
          <span class="oss-badge">Security review first</span>
        </div>
        <form class="oss-notify" action="#" method="post" onsubmit="return false;">
          <input type="email" name="email" placeholder="your@email.com" required autocomplete="email" aria-label="Email address">
          <button type="submit" class="btn btn-primary">Get notified when we open source</button>
        </form>
        <div class="oss-actions-secondary">
          <a href="{{ url('/docs/community') }}" class="btn btn-amber">Read the docs</a>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="footer">
  <div class="container">
    <div class="footer-inner">
      <div class="footer-logo">KlassApp</div>
      <ul class="footer-links">
        <li><a href="{{ url('/docs/community') }}">Docs</a></li>
        <li><a href="#community">Open source</a></li>
        <li><a href="{{ url('/docs/community') }}">Community</a></li>
        <li><a href="{{ url('/contact') }}">Contact</a></li>
      </ul>
      <div class="footer-copy">&copy; 2026 KlassApp</div>
    </div>
  </div>
</footer>

</body>
</html>
