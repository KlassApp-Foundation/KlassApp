<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="KlassApp — the all-in-one school management platform for modern schools.">
    <title>KlassApp — Smarter Schools Start Here</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <style>
    /* ═══════════════════════════════════════════════════
       TOKENS
    ═══════════════════════════════════════════════════ */
    :root {
        --ka-primary:       #1E6FD9;
        --ka-primary-dark:  #1558B5;
        --ka-primary-lite:  #60a5fa;
        --ka-green:         #22C55E;
        --ka-green-dark:    #16A34A;
        --ka-violet:        #7c3aed;
        --ka-page-bg:       #0c1535;
        --ka-dark:          #0c1535;
        --ka-surface:       #0c1535;
        --ka-text:          #f1f5f9;
        --ka-text-muted:    rgba(241,245,249,.62);
        --ka-text-faint:    rgba(241,245,249,.35);
        --ka-card:          rgba(255,255,255,.055);
        --ka-card-hover:    rgba(255,255,255,.08);
        --ka-border:        rgba(255,255,255,.09);
        --ka-border-strong: rgba(255,255,255,.16);
        --ka-nav-bg:        rgba(12,21,53,.88);
        --ka-radius:        12px;
        --ka-font:          'Plus Jakarta Sans', system-ui, sans-serif;
        --ka-shadow-lg:     0 8px 40px rgba(30,111,217,.24);
    }

    /* ═══════════════════════════════════════════════════
       RESET
    ═══════════════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; scroll-padding-top: 62px; }
    body {
        font-family: var(--ka-font);
        font-size: 15px; line-height: 1.7;
        color: var(--ka-text);
        background: var(--ka-page-bg);
        -webkit-font-smoothing: antialiased;
        max-width: none;
        margin: 0;
        padding: 0;
    }
    h1, h2, h3, h4 {
        font-family: var(--ka-font);
        font-weight: 800; line-height: 1.15; color: #fff;
    }
    a { color: var(--ka-primary-lite); text-decoration: none; }
    a:hover { text-decoration: underline; }
    img { max-width: 100%; display: block; }
    ul { list-style: none; }
    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; }
        html { scroll-behavior: auto; }
    }

    /* ═══════════════════════════════════════════════════
       AURORA BACKGROUND
    ═══════════════════════════════════════════════════ */
    .ka-bg {
        position: fixed; inset: 0; z-index: -1;
        pointer-events: none; overflow: hidden;
        background: var(--ka-page-bg);
    }
    .ka-bg-orb-1 {
        position: absolute; top: -18vh; left: -8vw;
        width: clamp(480px,65vw,900px); height: clamp(480px,65vw,900px);
        border-radius: 50%;
        background: radial-gradient(circle, rgba(30,111,217,.48) 0%, rgba(30,111,217,.14) 42%, transparent 68%);
        filter: blur(90px);
        animation: orb1 22s ease-in-out infinite alternate;
    }
    .ka-bg-orb-2 {
        position: absolute; top: 28vh; right: -12vw;
        width: clamp(380px,52vw,720px); height: clamp(380px,52vw,720px);
        border-radius: 50%;
        background: radial-gradient(circle, rgba(124,58,237,.38) 0%, rgba(124,58,237,.1) 45%, transparent 68%);
        filter: blur(100px);
        animation: orb2 28s ease-in-out infinite alternate;
    }
    .ka-bg-orb-3 {
        position: absolute; bottom: 5vh; left: 12vw;
        width: clamp(300px,40vw,580px); height: clamp(300px,40vw,580px);
        border-radius: 50%;
        background: radial-gradient(circle, rgba(34,197,94,.22) 0%, rgba(34,197,94,.06) 45%, transparent 68%);
        filter: blur(110px);
        animation: orb3 19s ease-in-out infinite alternate;
    }
    .ka-bg-orb-4 {
        position: absolute; bottom: -10vh; right: 5vw;
        width: clamp(240px,35vw,500px); height: clamp(240px,35vw,500px);
        border-radius: 50%;
        background: radial-gradient(circle, rgba(30,111,217,.18) 0%, transparent 65%);
        filter: blur(80px);
        animation: orb4 24s ease-in-out infinite alternate;
    }
    .ka-bg-grid {
        position: absolute; inset: 0;
        background-image: radial-gradient(circle, rgba(255,255,255,.055) 1px, transparent 1px);
        background-size: 36px 36px;
    }
    @keyframes orb1 { 0% { transform: translate(0,0) scale(1); } 100% { transform: translate(6vw,10vh) scale(1.12); } }
    @keyframes orb2 { 0% { transform: translate(0,0) scale(1); } 100% { transform: translate(-5vw,-9vh) scale(0.9); } }
    @keyframes orb3 { 0% { transform: translate(0,0) scale(1); } 100% { transform: translate(7vw,-12vh) scale(1.1); } }
    @keyframes orb4 { 0% { transform: translate(0,0) scale(1); } 100% { transform: translate(-4vw,8vh) scale(1.15); } }

    /* ═══════════════════════════════════════════════════
       CONTAINER
    ═══════════════════════════════════════════════════ */
    .ka-container {
        width: 100%;
        max-width: 1440px;
        margin-left: auto; margin-right: auto;
        padding-left:  clamp(1.25rem, 5vw, 4rem);
        padding-right: clamp(1.25rem, 5vw, 4rem);
    }

    /* ═══════════════════════════════════════════════════
       BUTTONS
    ═══════════════════════════════════════════════════ */
    .ka-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
        min-height: 44px; padding: .625rem 1.375rem;
        border-radius: 8px; font-size: .9375rem; font-weight: 600;
        font-family: var(--ka-font); cursor: pointer;
        transition: background .16s, border-color .16s, transform .16s, box-shadow .16s;
        border: 1.5px solid transparent; text-decoration: none;
        white-space: nowrap; line-height: 1.4;
    }
    .ka-btn:hover { text-decoration: none; }
    .ka-btn-primary { background: var(--ka-primary); color: #fff; border-color: var(--ka-primary); }
    .ka-btn-primary:hover { background: var(--ka-primary-dark); border-color: var(--ka-primary-dark); transform: translateY(-1px); box-shadow: 0 4px 18px rgba(30,111,217,.45); color: #fff; }
    .ka-btn-green { background: var(--ka-green); color: #fff; border-color: var(--ka-green); }
    .ka-btn-green:hover { background: var(--ka-green-dark); border-color: var(--ka-green-dark); transform: translateY(-1px); box-shadow: 0 4px 18px rgba(34,197,94,.45); color: #fff; }
    .ka-btn-outline { background: transparent; color: var(--ka-primary-lite); border-color: rgba(96,165,250,.4); }
    .ka-btn-outline:hover { background: var(--ka-primary); color: #fff; border-color: var(--ka-primary); transform: translateY(-1px); }
    .ka-btn-glass { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.2); color: #fff; backdrop-filter: blur(8px); }
    .ka-btn-glass:hover { background: rgba(255,255,255,.16); border-color: rgba(255,255,255,.32); color: #fff; transform: translateY(-1px); }
    .ka-btn-dark-ghost { background: transparent; border-color: rgba(255,255,255,.14); color: rgba(255,255,255,.5); }
    .ka-btn-dark-ghost:hover { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.26); color: rgba(255,255,255,.82); transform: translateY(-1px); }
    .ka-btn-lg { padding: .875rem 2rem; font-size: 1rem; border-radius: 10px; min-height: 52px; }
    .ka-btn-sm { padding: .4rem 1.125rem; font-size: .875rem; min-height: 36px; }

    /* ═══════════════════════════════════════════════════
       NAVBAR
    ═══════════════════════════════════════════════════ */
    .ka-nav {
        position: sticky; top: 0; z-index: 100;
        background: var(--ka-nav-bg);
        backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255,255,255,.07);
        transition: background .22s, box-shadow .22s;
    }
    .ka-nav.scrolled { background: rgba(12,21,53,.97); box-shadow: 0 4px 32px rgba(0,0,0,.5); }
    /* Nav inner stretches edge-to-edge within the full-width nav bar */
    .ka-nav .ka-container {
        max-width: 100%;
        padding-left:  clamp(1.25rem, 4vw, 3rem);
        padding-right: clamp(1.25rem, 4vw, 3rem);
    }
    .ka-nav-inner {
        display: flex; align-items: center;
        height: 64px; gap: 1rem;
    }
    /* Brand: grows to fill left side */
    .ka-nav-brand { display: flex; align-items: center; gap: .5rem; flex: 1; min-width: 0; }
    .ka-nav-brand img { height: 36px; width: auto; flex-shrink: 0; }
    .ka-nav-brand-name {
        font-family: var(--ka-font); font-weight: 800; font-size: 1.1rem;
        color: #fff; letter-spacing: -.01em; white-space: nowrap;
    }

    /* Desktop nav links: natural width, centered via equal flanks */
    .ka-nav-links {
        display: flex; align-items: center;
        gap: clamp(.75rem, 2vw, 1.75rem);
        flex: 0 0 auto;
    }
    .ka-nav-link {
        display: flex; align-items: center; gap: .3rem;
        color: rgba(255,255,255,.62); font-size: .9rem; font-weight: 600;
        transition: color .15s; letter-spacing: .01em; padding: .4rem .5rem;
        border-radius: 6px; cursor: pointer; background: none; border: none;
        font-family: var(--ka-font);
    }
    .ka-nav-link:hover { color: #fff; text-decoration: none; }

    /* Dropdown wrapper */
    .ka-dropdown-wrap { position: relative; }
    .ka-chevron {
        width: 14px; height: 14px; flex-shrink: 0;
        transition: transform .2s; color: rgba(255,255,255,.4);
    }
    .ka-dropdown-wrap:hover .ka-chevron,
    .ka-dropdown-wrap.open .ka-chevron { transform: rotate(180deg); }

    /* Dropdown panel */
    .ka-dropdown {
        position: absolute; top: calc(100% + 10px); left: -12px;
        min-width: 210px;
        background: rgba(12,21,53,.98);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 12px; padding: .375rem;
        opacity: 0; visibility: hidden; transform: translateY(-6px);
        transition: opacity .18s ease, transform .18s ease, visibility .18s;
        backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
        box-shadow: 0 16px 48px rgba(0,0,0,.6); z-index: 200;
    }
    /* CSS hover for pointer devices */
    @media (hover: hover) and (pointer: fine) {
        .ka-dropdown-wrap:hover .ka-dropdown {
            opacity: 1; visibility: visible; transform: translateY(0);
        }
    }
    /* JS-toggled open state (touch / click) */
    .ka-dropdown-wrap.open .ka-dropdown {
        opacity: 1; visibility: visible; transform: translateY(0);
    }
    .ka-dropdown a {
        display: flex; align-items: center; gap: .625rem;
        padding: .6rem .875rem; border-radius: 8px;
        color: rgba(255,255,255,.62) !important; font-size: .9rem; font-weight: 500;
        transition: background .14s, color .14s;
    }
    .ka-dropdown a:hover { background: rgba(255,255,255,.07); color: #fff !important; text-decoration: none; }
    .ka-dropdown-icon {
        width: 30px; height: 30px; border-radius: 7px;
        background: rgba(255,255,255,.07);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; color: var(--ka-primary-lite);
    }
    .ka-dropdown-label { display: flex; flex-direction: column; }
    .ka-dropdown-label small { font-size: .75rem; color: rgba(255,255,255,.35); font-weight: 400; line-height: 1.3; }

    /* Actions: grows to fill right side, items pushed right */
    .ka-nav-actions { display: flex; align-items: center; gap: .5rem; flex: 1; justify-content: flex-end; }

    /* Hamburger */
    .ka-hamburger {
        display: none; flex-direction: column; gap: 5px;
        cursor: pointer; background: none; border: none; padding: .5rem;
    }
    .ka-hamburger span {
        display: block; width: 22px; height: 2px;
        background: rgba(255,255,255,.75); border-radius: 2px; transition: all .2s;
    }
    .ka-hamburger.active span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .ka-hamburger.active span:nth-child(2) { opacity: 0; }
    .ka-hamburger.active span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    /* Mobile menu panel */
    .ka-nav-mobile {
        display: none; flex-direction: column;
        background: rgba(12,21,53,.98);
        backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
        border-top: 1px solid rgba(255,255,255,.07);
        padding: .75rem clamp(1.25rem,4vw,2rem) 1.5rem;
    }
    .ka-nav-mobile.open { display: flex; }

    /* Mobile plain link */
    .ka-mob-link {
        color: rgba(255,255,255,.6); padding: .75rem 0;
        border-bottom: 1px solid rgba(255,255,255,.06);
        font-weight: 600; font-size: .9375rem; transition: color .15s;
        display: block;
    }
    .ka-mob-link:hover { color: #fff; text-decoration: none; }
    .ka-mob-link.ka-join { color: #4ade80; font-weight: 700; border-bottom: none; }

    /* Mobile accordion group (Home) */
    .ka-mob-group { border-bottom: 1px solid rgba(255,255,255,.06); }
    .ka-mob-group-toggle {
        display: flex; align-items: center; justify-content: space-between;
        width: 100%; background: none; border: none;
        color: rgba(255,255,255,.6); padding: .75rem 0;
        font-weight: 600; font-size: .9375rem; cursor: pointer;
        font-family: var(--ka-font); transition: color .15s;
    }
    .ka-mob-group-toggle:hover { color: #fff; }
    .ka-mob-chevron { transition: transform .22s; color: rgba(255,255,255,.35); }
    .ka-mob-group-toggle.open .ka-mob-chevron { transform: rotate(180deg); }
    .ka-mob-group-panel {
        max-height: 0; overflow: hidden;
        transition: max-height .26s ease;
    }
    .ka-mob-group-panel.open { max-height: 320px; }
    .ka-mob-sub-link {
        display: block; padding: .55rem 0 .55rem 1rem;
        color: rgba(255,255,255,.45); font-size: .875rem; font-weight: 500;
        border-bottom: 1px solid rgba(255,255,255,.04);
        transition: color .15s;
    }
    .ka-mob-sub-link:last-child { border-bottom: none; }
    .ka-mob-sub-link:hover { color: #fff; text-decoration: none; }
    .ka-mob-actions {
        display: flex; gap: .5rem; margin-top: 1.125rem; flex-wrap: wrap;
    }
    .ka-mob-actions .ka-btn { flex: 1; justify-content: center; min-width: 120px; }

    /* ═══════════════════════════════════════════════════
       HERO
    ═══════════════════════════════════════════════════ */
    .ka-hero { padding: clamp(64px,10vw,108px) 0 clamp(56px,9vw,96px); position: relative; }
    .ka-hero-layout {
        display: grid; grid-template-columns: 55fr 45fr;
        gap: clamp(2.5rem,5vw,4.5rem); align-items: center;
    }
    .ka-hero-content { max-width: 640px; }
    .ka-hero-badge {
        display: inline-flex; align-items: center; gap: .5rem;
        background: rgba(30,111,217,.18); border: 1px solid rgba(30,111,217,.35);
        color: #93c5fd; font-size: .8rem; font-weight: 700;
        padding: .375rem 1rem; border-radius: 999px;
        margin-bottom: 1.625rem; letter-spacing: .03em;
    }
    .ka-hero-title {
        font-size: clamp(2.125rem,4.8vw,3.5rem); font-weight: 800;
        color: #fff; margin-bottom: 1.25rem; letter-spacing: -.03em; line-height: 1.08;
    }
    .ka-hero-title span {
        background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    .ka-hero-sub {
        font-size: clamp(.9375rem,1.8vw,1.125rem);
        color: rgba(241,245,249,.62); margin-bottom: 2.25rem; max-width: 500px;
    }
    .ka-hero-actions { display: flex; gap: .75rem; flex-wrap: wrap; margin-bottom: 2rem; }
    .ka-hero-trust { display: flex; flex-wrap: wrap; gap: .875rem 1.5rem; }
    .ka-hero-trust-item {
        display: flex; align-items: center; gap: .4rem;
        font-size: .8rem; color: rgba(255,255,255,.38);
    }
    .ka-hero-visual { display: flex; align-items: center; justify-content: center; }

    /* Mock dashboard */
    .ka-mock {
        background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.13);
        border-radius: 16px; overflow: hidden; width: 100%; max-width: 420px;
        font-size: .8125rem;
        box-shadow: 0 32px 64px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.04);
        backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
    }
    .ka-mock-titlebar {
        display: flex; align-items: center; gap: .625rem; padding: .8rem 1rem;
        background: rgba(255,255,255,.04); border-bottom: 1px solid rgba(255,255,255,.08);
    }
    .ka-mock-dots { display: flex; gap: 5px; }
    .ka-mock-dots span { width: 10px; height: 10px; border-radius: 50%; }
    .ka-mock-dots span:nth-child(1) { background: #ff5f57; }
    .ka-mock-dots span:nth-child(2) { background: #febc2e; }
    .ka-mock-dots span:nth-child(3) { background: #28c840; }
    .ka-mock-win-title { color: rgba(255,255,255,.5); font-size: .75rem; font-weight: 600; flex: 1; margin-left: .25rem; }
    .ka-mock-win-tag { font-size: .68rem; background: rgba(34,197,94,.22); color: #4ade80; padding: .15rem .5rem; border-radius: 4px; font-weight: 600; }
    .ka-mock-body { padding: 1rem; display: flex; flex-direction: column; gap: .875rem; }
    .ka-mock-kpi-row { display: grid; grid-template-columns: repeat(3,1fr); gap: .5rem; }
    .ka-mock-kpi { background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08); border-radius: 8px; padding: .625rem .5rem; text-align: center; }
    .ka-mock-kpi-num { font-size: 1.2rem; font-weight: 800; color: #fff; line-height: 1; margin-bottom: .2rem; }
    .ka-mock-kpi-lbl { font-size: .68rem; color: rgba(255,255,255,.38); }
    .ka-mock-bars { display: flex; flex-direction: column; gap: .45rem; }
    .ka-mock-bar-row { display: flex; align-items: center; gap: .5rem; font-size: .725rem; color: rgba(255,255,255,.5); }
    .ka-mock-bar-label { width: 82px; flex-shrink: 0; }
    .ka-mock-bar-track { flex: 1; height: 6px; background: rgba(255,255,255,.08); border-radius: 3px; overflow: hidden; }
    .ka-mock-bar-fill { height: 100%; border-radius: 3px; }
    .ka-mock-bar-pct { width: 28px; flex-shrink: 0; text-align: right; color: rgba(255,255,255,.7); font-weight: 600; }
    .ka-mock-feed { display: flex; flex-direction: column; gap: .3rem; }
    .ka-mock-feed-item { display: flex; align-items: center; gap: .5rem; padding: .4rem .5rem; background: rgba(255,255,255,.03); border-radius: 6px; font-size: .725rem; color: rgba(255,255,255,.6); }
    .ka-mock-feed-text { flex: 1; }
    .ka-mock-feed-time { color: rgba(255,255,255,.28); font-size: .68rem; flex-shrink: 0; }
    .ka-mock-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .ka-mock-dot-blue { background: var(--ka-primary); }
    .ka-mock-dot-green { background: var(--ka-green); }
    .ka-mock-dot-amber { background: #f59e0b; }

    /* ═══════════════════════════════════════════════════
       TRUSTED STRIP
    ═══════════════════════════════════════════════════ */
    .ka-trusted {
        padding: 32px 0;
        background: rgba(255,255,255,.025);
        border-top: 1px solid var(--ka-border);
        border-bottom: 1px solid var(--ka-border);
    }
    .ka-trusted-label { text-align: center; font-size: .775rem; color: var(--ka-text-faint); text-transform: uppercase; letter-spacing: .09em; font-weight: 700; margin-bottom: 1.125rem; }
    .ka-trusted-badges { display: flex; justify-content: center; flex-wrap: wrap; gap: .5rem; }
    .ka-trusted-badge { background: rgba(255,255,255,.06); border: 1px solid var(--ka-border); border-radius: 8px; padding: .375rem .9rem; font-size: .8rem; font-weight: 600; color: var(--ka-text-muted); }

    /* ═══════════════════════════════════════════════════
       SECTIONS
    ═══════════════════════════════════════════════════ */
    .ka-section { padding: clamp(56px,8vw,96px) 0; }
    .ka-section-tinted { background: rgba(255,255,255,.022); }
    .ka-section-label { font-size: .775rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--ka-primary-lite); margin-bottom: .75rem; }
    .ka-section-title { font-size: clamp(1.75rem,3.5vw,2.625rem); font-weight: 800; margin-bottom: 1rem; letter-spacing: -.025em; color: #fff; }
    .ka-section-sub { font-size: clamp(.9375rem,1.6vw,1.0625rem); color: var(--ka-text-muted); max-width: 540px; }
    .ka-section-header { margin-bottom: clamp(2.25rem,4vw,3.5rem); text-align: center; }
    .ka-section-header .ka-section-sub { margin-left: auto; margin-right: auto; }
    /* Left-aligned variant for prose-style sections */
    .ka-section-header.left { text-align: left; }
    .ka-section-header.left .ka-section-sub { margin-left: 0; }

    /* ═══════════════════════════════════════════════════
       FEATURES
    ═══════════════════════════════════════════════════ */
    .ka-features-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.25rem; }
    .ka-feature-card {
        background: var(--ka-card); border: 1px solid var(--ka-border);
        border-radius: var(--ka-radius); padding: 1.625rem;
        transition: background .2s, border-color .2s, transform .2s, box-shadow .2s;
        backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
    }
    .ka-feature-card:hover { background: var(--ka-card-hover); border-color: rgba(30,111,217,.4); box-shadow: 0 8px 32px rgba(30,111,217,.18); transform: translateY(-3px); }
    .ka-feature-icon { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: rgba(30,111,217,.18); border-radius: 10px; margin-bottom: .9rem; color: var(--ka-primary-lite); flex-shrink: 0; }
    .ka-feature-title { font-size: 1rem; font-weight: 700; margin-bottom: .35rem; color: #fff; }
    .ka-feature-desc { font-size: .875rem; color: var(--ka-text-muted); line-height: 1.65; }

    /* ═══════════════════════════════════════════════════
       HOW IT WORKS
    ═══════════════════════════════════════════════════ */
    .ka-steps-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 2rem; position: relative; }
    .ka-steps-grid::before {
        content: ''; position: absolute; top: 27px;
        left: calc(100% / 6); right: calc(100% / 6); height: 2px;
        background: linear-gradient(90deg, rgba(96,165,250,.18) 0%, rgba(96,165,250,.48) 50%, rgba(96,165,250,.18) 100%);
        pointer-events: none; z-index: 0;
    }
    .ka-step { text-align: center; padding: 0 .75rem; }
    .ka-step-num { width: 54px; height: 54px; border-radius: 50%; background: linear-gradient(135deg, var(--ka-primary), #60a5fa); color: #fff; font-size: 1.375rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.125rem; box-shadow: 0 4px 18px rgba(30,111,217,.4); position: relative; z-index: 1; }
    .ka-step-title { font-size: 1.0625rem; font-weight: 700; margin-bottom: .5rem; color: #fff; }
    .ka-step-desc { font-size: .875rem; color: var(--ka-text-muted); line-height: 1.65; }
    .ka-step-body { flex: 1; }

    /* ═══════════════════════════════════════════════════
       STATS BAND
    ═══════════════════════════════════════════════════ */
    .ka-stats-band { padding: clamp(44px,6vw,68px) 0; background: rgba(30,111,217,.07); border-top: 1px solid rgba(30,111,217,.15); border-bottom: 1px solid rgba(30,111,217,.15); }
    .ka-stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 2rem; text-align: center; }
    .ka-stat-number { font-size: clamp(2.25rem,4vw,3rem); font-weight: 800; color: var(--ka-green); line-height: 1; margin-bottom: .4rem; }
    .ka-stat-label { font-size: .9375rem; color: var(--ka-text-muted); font-weight: 500; }

    /* ═══════════════════════════════════════════════════
       TESTIMONIALS
    ═══════════════════════════════════════════════════ */
    .ka-testimonials-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.25rem; }
    .ka-testimonial-card {
        background: var(--ka-card); border: 1px solid var(--ka-border);
        border-radius: var(--ka-radius); padding: 1.875rem 1.625rem; position: relative;
        backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
        transition: border-color .2s, box-shadow .2s;
    }
    .ka-testimonial-card:hover { border-color: rgba(96,165,250,.3); box-shadow: 0 8px 32px rgba(30,111,217,.12); }
    .ka-testimonial-card::before { content: '"'; position: absolute; top: .75rem; left: 1.375rem; font-family: Georgia,serif; font-size: 5rem; color: var(--ka-primary); opacity: .15; line-height: 1; pointer-events: none; }
    .ka-testimonial-text { font-size: .9375rem; color: var(--ka-text-muted); margin-bottom: 1.25rem; font-style: italic; position: relative; line-height: 1.7; }
    .ka-testimonial-meta { display: flex; align-items: center; gap: .75rem; }
    .ka-testimonial-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, var(--ka-primary), #a78bfa); color: #fff; font-size: .8125rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ka-testimonial-name { font-weight: 700; font-size: .9rem; color: #fff; }
    .ka-testimonial-role { font-size: .8125rem; color: var(--ka-text-faint); }

    /* ═══════════════════════════════════════════════════
       PRICING
    ═══════════════════════════════════════════════════ */
    .ka-plans-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.25rem; align-items: start; }
    .ka-plan-card { background: var(--ka-card); border: 1px solid var(--ka-border); border-radius: var(--ka-radius); padding: 2rem 1.75rem; position: relative; backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); transition: border-color .2s, box-shadow .2s; }
    .ka-plan-card:hover { border-color: var(--ka-border-strong); }
    .ka-plan-card.highlight { background: rgba(30,111,217,.14); border: 1.5px solid rgba(30,111,217,.55); box-shadow: 0 0 0 1px rgba(30,111,217,.18), var(--ka-shadow-lg); margin-top: -16px; padding-top: calc(2rem + 16px); }
    .ka-plan-badge { display: inline-block; background: var(--ka-primary); color: #fff; font-size: .7rem; font-weight: 700; padding: .2rem .75rem; border-radius: 999px; margin-bottom: 1rem; letter-spacing: .05em; text-transform: uppercase; }
    .ka-plan-name { font-size: 1.125rem; font-weight: 800; margin-bottom: .5rem; color: #fff; }
    .ka-plan-price-row { display: flex; align-items: baseline; gap: .25rem; margin-bottom: .375rem; }
    .ka-plan-price { font-size: 2.5rem; font-weight: 800; color: #fff; }
    .ka-plan-period { font-size: .875rem; color: var(--ka-text-muted); }
    .ka-plan-sub { font-size: .8125rem; color: var(--ka-text-faint); margin-bottom: 1.5rem; }
    .ka-plan-divider { border: none; border-top: 1px solid var(--ka-border); margin: 1.25rem 0; }
    .ka-plan-group-title { font-size: .775rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--ka-text-faint); margin-bottom: .75rem; }
    .ka-plan-features { font-size: .875rem; }
    .ka-plan-features li { display: flex; align-items: flex-start; gap: .5rem; padding: .3rem 0; color: var(--ka-text-muted); }
    .ka-plan-features li::before { content: '✓'; color: var(--ka-green); font-weight: 700; flex-shrink: 0; line-height: 1.55; }
    .ka-plan-cta { margin-top: 1.75rem; }
    .ka-plan-cta .ka-btn { width: 100%; }

    /* ═══════════════════════════════════════════════════
       FAQ
    ═══════════════════════════════════════════════════ */
    .ka-faq-list { max-width: 860px; margin: 0 auto; }
    .ka-faq-item { border: 1px solid var(--ka-border); border-radius: 8px; margin-bottom: .5rem; overflow: hidden; background: var(--ka-card); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
    .ka-faq-btn { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.125rem 1.375rem; background: transparent; border: none; cursor: pointer; font-size: .9375rem; font-weight: 600; color: var(--ka-text); text-align: left; font-family: var(--ka-font); transition: background .15s; min-height: 52px; }
    .ka-faq-btn:hover { background: rgba(255,255,255,.05); }
    .ka-faq-btn[aria-expanded="true"] { color: var(--ka-primary-lite); background: rgba(30,111,217,.12); }
    .ka-faq-icon { flex-shrink: 0; width: 22px; height: 22px; border-radius: 50%; background: rgba(255,255,255,.1); display: flex; align-items: center; justify-content: center; font-size: .95rem; font-weight: 700; color: var(--ka-text-muted); transition: transform .22s, background .15s, color .15s; }
    .ka-faq-btn[aria-expanded="true"] .ka-faq-icon { background: var(--ka-primary); color: #fff; transform: rotate(45deg); }
    .ka-faq-panel { max-height: 0; overflow: hidden; transition: max-height .28s ease; }
    .ka-faq-panel-inner { padding: 0 1.375rem 1.125rem; font-size: .9rem; color: var(--ka-text-muted); line-height: 1.7; }

    /* ═══════════════════════════════════════════════════
       CTA
    ═══════════════════════════════════════════════════ */
    .ka-cta-section { padding: clamp(64px,9vw,104px) 0; text-align: center; position: relative; overflow: hidden; }
    .ka-cta-inner { background: rgba(30,111,217,.1); border: 1px solid rgba(30,111,217,.25); border-radius: 24px; padding: clamp(48px,6vw,80px) clamp(1.5rem,5vw,4rem); position: relative; overflow: hidden; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
    .ka-cta-inner::before { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 600px; height: 400px; border-radius: 50%; background: radial-gradient(circle, rgba(30,111,217,.18) 0%, transparent 65%); pointer-events: none; }
    .ka-cta-inner > * { position: relative; z-index: 1; }
    .ka-cta-title { font-size: clamp(1.625rem,3.5vw,2.5rem); font-weight: 800; color: #fff; margin-bottom: 1rem; letter-spacing: -.025em; }
    .ka-cta-sub { color: rgba(255,255,255,.62); font-size: clamp(.9375rem,1.6vw,1.0625rem); margin-bottom: 2.125rem; max-width: 500px; margin-left: auto; margin-right: auto; }
    .ka-cta-actions { display: flex; gap: .875rem; justify-content: center; flex-wrap: wrap; }
    .ka-cta-note { margin-top: 1.25rem; font-size: .8125rem; color: rgba(255,255,255,.3); }

    /* ═══════════════════════════════════════════════════
       FOOTER  —  matches nav color
    ═══════════════════════════════════════════════════ */
    .ka-footer .ka-container {
        max-width: 100%;
        padding-left:  clamp(1.25rem, 4vw, 3rem);
        padding-right: clamp(1.25rem, 4vw, 3rem);
    }
    .ka-footer {
        background: rgba(12,21,53,.98);
        border-top: 1px solid rgba(255,255,255,.07);
        color: rgba(255,255,255,.5);
        padding: clamp(48px,6vw,64px) 0 28px;
        font-size: .875rem;
    }
    .ka-footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: clamp(1.5rem,3vw,2.5rem); margin-bottom: 2.5rem; }
    .ka-footer-brand img { height: 32px; width: auto; margin-bottom: .75rem; }
    .ka-footer-tagline { color: rgba(255,255,255,.32); font-size: .875rem; margin-top: .375rem; line-height: 1.6; max-width: 220px; }
    .ka-footer-col-title { color: rgba(255,255,255,.65); font-weight: 700; font-size: .775rem; letter-spacing: .08em; text-transform: uppercase; margin-bottom: .875rem; }
    .ka-footer-links { display: flex; flex-direction: column; gap: .45rem; }
    .ka-footer-links a { color: rgba(255,255,255,.38); transition: color .15s; font-size: .875rem; }
    .ka-footer-links a:hover { color: rgba(255,255,255,.82); text-decoration: none; }

    /* Social icons */
    .ka-footer-socials { display: flex; gap: .5rem; }
    .ka-social-btn {
        width: 38px; height: 38px; border-radius: 9px;
        background: rgba(255,255,255,.07); color: rgba(255,255,255,.45);
        border: 1px solid rgba(255,255,255,.08);
        display: flex; align-items: center; justify-content: center;
        transition: background .15s, color .15s, border-color .15s;
    }
    .ka-social-btn:hover { background: var(--ka-primary); color: #fff; border-color: var(--ka-primary); text-decoration: none; }
    .ka-social-btn svg { display: block; }

    .ka-footer-copy { border-top: 1px solid rgba(255,255,255,.07); padding-top: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem; font-size: .8125rem; color: rgba(255,255,255,.22); }
    .ka-footer-copy-links { display: flex; gap: 1.25rem; }
    .ka-footer-copy-links a { color: rgba(255,255,255,.25); transition: color .15s; }
    .ka-footer-copy-links a:hover { color: rgba(255,255,255,.6); text-decoration: none; }

    /* ═══════════════════════════════════════════════════
       RESPONSIVE
    ═══════════════════════════════════════════════════ */

    /* Intermediate desktop 900–1060px */
    @media (max-width: 1060px) and (min-width: 900px) {
        .ka-hero-layout { gap: 2rem; }
        .ka-plans-grid { gap: 1rem; }
        .ka-features-grid { gap: 1rem; }
        .ka-hero-title { font-size: clamp(2rem,4vw,2.75rem); }
        .ka-plan-card.highlight { margin-top: -12px; padding-top: calc(2rem + 12px); }
        .ka-footer-grid { gap: 1.5rem; }
    }

    /* Tablet 600–899px */
    @media (max-width: 899px) {
        .ka-nav-links, .ka-nav-actions { display: none; }
        .ka-hamburger { display: flex; }
        /* On mobile: brand fills space between logo and hamburger */
        .ka-nav-brand { flex: 1; }
        .ka-nav-brand-name { display: none; } /* logo image is enough on mobile */
        .ka-hero-layout { grid-template-columns: 1fr; gap: 3rem; }
        .ka-hero-content { text-align: center; max-width: 600px; margin: 0 auto; }
        .ka-hero-sub { margin-left: auto; margin-right: auto; }
        .ka-hero-actions { justify-content: center; }
        .ka-hero-trust { justify-content: center; }
        .ka-hero-badge { margin: 0 auto 1.625rem; }
        .ka-hero-visual { justify-content: center; }
        .ka-mock { max-width: 380px; }
        .ka-features-grid { grid-template-columns: repeat(2,1fr); }
        .ka-testimonials-grid { grid-template-columns: 1fr; max-width: 540px; margin: 0 auto; }
        .ka-plans-grid { grid-template-columns: 1fr; max-width: 460px; margin: 0 auto; }
        .ka-plan-card.highlight { order: -1; margin-top: 0; padding-top: 2rem; }
        .ka-stats-grid { grid-template-columns: repeat(2,1fr); }
        .ka-footer-grid { grid-template-columns: 1fr 1fr; }
        .ka-steps-grid::before { display: none; }
        .ka-steps-grid { grid-template-columns: 1fr; gap: 1.75rem; }
        .ka-step { text-align: left; display: flex; gap: 1.25rem; padding: 0; align-items: flex-start; }
        .ka-step-num { flex-shrink: 0; margin-bottom: 0; margin-top: .05rem; }
        /* section-header is already centered by default */
    }

    /* Mobile <600px */
    @media (max-width: 599px) {
        .ka-hero-visual { display: none; }
        .ka-features-grid { grid-template-columns: 1fr; gap: .875rem; }
        .ka-feature-card { display: flex; align-items: flex-start; gap: 1rem; padding: 1.125rem; }
        .ka-feature-icon { margin-bottom: 0; flex-shrink: 0; }
        .ka-hero-actions { flex-wrap: wrap; }
        .ka-plans-grid { max-width: 100%; }
        .ka-footer-grid { grid-template-columns: 1fr; }
        .ka-stats-grid { grid-template-columns: repeat(2,1fr); gap: 1.25rem; }
        .ka-stat-number { font-size: 2.125rem; }
        .ka-cta-actions { flex-direction: column; align-items: center; }
        .ka-cta-actions .ka-btn { width: 100%; max-width: 320px; }
        .ka-footer-copy { flex-direction: column; text-align: center; }
        .ka-footer-copy-links { justify-content: center; }
    }
    </style>
</head>
<body>

<!-- Background orbs -->
<div class="ka-bg" aria-hidden="true">
    <div class="ka-bg-orb-1"></div>
    <div class="ka-bg-orb-2"></div>
    <div class="ka-bg-orb-3"></div>
    <div class="ka-bg-orb-4"></div>
    <div class="ka-bg-grid"></div>
</div>

<!-- ══════════════════════ NAVBAR ══════════════════════════ -->
<nav class="ka-nav" id="kaMainNav">
    <div class="ka-container ka-nav-inner">

        <!-- Brand logo -->
        <a class="ka-nav-brand" href="/">
            <img src="{{ asset('images/klassapplogo-dark.png') }}" alt="KlassApp" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
            <span class="ka-nav-brand-name" style="display:none">KlassApp</span>
        </a>

        <!-- Desktop links -->
        <div class="ka-nav-links">

            <!-- Home with dropdown -->
            <div class="ka-dropdown-wrap" id="kaHomeDropdown">
                <a href="/" class="ka-nav-link ka-dropdown-toggle" id="kaHomeToggle">
                    Home
                    <svg class="ka-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </a>
                <div class="ka-dropdown" role="menu" aria-label="Site sections">
                    <a href="#features" role="menuitem">
                        <span class="ka-dropdown-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        </span>
                        <span class="ka-dropdown-label">
                            Features
                            <small>Everything your school needs</small>
                        </span>
                    </a>
                    <a href="#how" role="menuitem">
                        <span class="ka-dropdown-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                        </span>
                        <span class="ka-dropdown-label">
                            How it works
                            <small>Up and running in minutes</small>
                        </span>
                    </a>
                    <a href="#testimonials" role="menuitem">
                        <span class="ka-dropdown-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </span>
                        <span class="ka-dropdown-label">
                            Testimonials
                            <small>Schools love KlassApp</small>
                        </span>
                    </a>
                    <a href="#faq" role="menuitem">
                        <span class="ka-dropdown-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01"/></svg>
                        </span>
                        <span class="ka-dropdown-label">
                            FAQ
                            <small>Common questions answered</small>
                        </span>
                    </a>
                </div>
            </div>

            <a href="#pricing" class="ka-nav-link">Pricing</a>
        </div>

        <!-- CTA buttons -->
        <div class="ka-nav-actions">
            <a class="ka-btn ka-btn-glass ka-btn-sm" href="{{ route('login') }}">Portal</a>
            <a class="ka-btn ka-btn-green ka-btn-sm" href="{{ url('/register') }}">Join free</a>
        </div>

        <!-- Hamburger -->
        <button class="ka-hamburger" id="kaHamburger" aria-label="Toggle menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>

    <!-- Mobile menu -->
    <div class="ka-nav-mobile" id="kaNavMobile" aria-hidden="true">

        <!-- Home accordion -->
        <div class="ka-mob-group">
            <button class="ka-mob-group-toggle" id="kaMobHomeToggle" aria-expanded="false">
                Home
                <svg class="ka-mob-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="ka-mob-group-panel" id="kaMobHomePanel">
                <a href="#features" class="ka-mob-sub-link">Features</a>
                <a href="#how" class="ka-mob-sub-link">How it works</a>
                <a href="#testimonials" class="ka-mob-sub-link">Testimonials</a>
                <a href="#faq" class="ka-mob-sub-link">FAQ</a>
            </div>
        </div>

        <a href="#pricing" class="ka-mob-link">Pricing</a>

        <div class="ka-mob-actions">
            <a class="ka-btn ka-btn-glass" href="{{ route('login') }}">Portal</a>
            <a class="ka-btn ka-btn-green" href="{{ url('/register') }}">Join free</a>
        </div>
    </div>
</nav>

<!-- ════════════════════════ HERO ══════════════════════════ -->
<header class="ka-hero">
    <div class="ka-container">
        <div class="ka-hero-layout">
            <div class="ka-hero-content">
                <div class="ka-hero-badge">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    All-in-one school management platform
                </div>
                <h1 class="ka-hero-title">
                    Stop managing chaos.<br>
                    <span>Start leading with clarity.</span>
                </h1>
                <p class="ka-hero-sub">
                    One platform to run your entire school — smarter, faster, and more securely than ever before.
                </p>
                <div class="ka-hero-actions">
                    <a class="ka-btn ka-btn-green ka-btn-lg" href="{{ url('/register') }}">Start free trial</a>
                    <a class="ka-btn ka-btn-glass ka-btn-lg" href="{{ route('login') }}">Sign in to Portal</a>
                    <a class="ka-btn ka-btn-dark-ghost ka-btn-lg" href="https://calendly.com/moemucu/talk-to-mucu" target="_blank" rel="noopener noreferrer">Book a demo</a>
                </div>
                <div class="ka-hero-trust">
                    <div class="ka-hero-trust-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                        Free 30-day trial
                    </div>
                    <div class="ka-hero-trust-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                        No credit card
                    </div>
                    <div class="ka-hero-trust-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                        Up and running in under a day
                    </div>
                </div>
            </div>

            <!-- Mock dashboard -->
            <div class="ka-hero-visual" aria-hidden="true">
                <div class="ka-mock">
                    <div class="ka-mock-titlebar">
                        <div class="ka-mock-dots"><span></span><span></span><span></span></div>
                        <span class="ka-mock-win-title">KlassApp Dashboard</span>
                        <span class="ka-mock-win-tag">Live</span>
                    </div>
                    <div class="ka-mock-body">
                        <div class="ka-mock-kpi-row">
                            <div class="ka-mock-kpi">
                                <div class="ka-mock-kpi-num">847</div>
                                <div class="ka-mock-kpi-lbl">Students</div>
                            </div>
                            <div class="ka-mock-kpi">
                                <div class="ka-mock-kpi-num" style="color:#4ade80;">96%</div>
                                <div class="ka-mock-kpi-lbl">Attendance</div>
                            </div>
                            <div class="ka-mock-kpi">
                                <div class="ka-mock-kpi-num">12</div>
                                <div class="ka-mock-kpi-lbl">Messages</div>
                            </div>
                        </div>
                        <div class="ka-mock-bars">
                            <div class="ka-mock-bar-row">
                                <span class="ka-mock-bar-label">Attendance</span>
                                <div class="ka-mock-bar-track"><div class="ka-mock-bar-fill" style="width:96%;background:#1E6FD9;"></div></div>
                                <span class="ka-mock-bar-pct">96%</span>
                            </div>
                            <div class="ka-mock-bar-row">
                                <span class="ka-mock-bar-label">Assignments</span>
                                <div class="ka-mock-bar-track"><div class="ka-mock-bar-fill" style="width:74%;background:#22c55e;"></div></div>
                                <span class="ka-mock-bar-pct">74%</span>
                            </div>
                            <div class="ka-mock-bar-row">
                                <span class="ka-mock-bar-label">Fees paid</span>
                                <div class="ka-mock-bar-track"><div class="ka-mock-bar-fill" style="width:88%;background:#f59e0b;"></div></div>
                                <span class="ka-mock-bar-pct">88%</span>
                            </div>
                        </div>
                        <div class="ka-mock-feed">
                            <div class="ka-mock-feed-item">
                                <span class="ka-mock-dot ka-mock-dot-blue"></span>
                                <span class="ka-mock-feed-text">3 students marked absent</span>
                                <span class="ka-mock-feed-time">2m</span>
                            </div>
                            <div class="ka-mock-feed-item">
                                <span class="ka-mock-dot ka-mock-dot-green"></span>
                                <span class="ka-mock-feed-text">Term report ready · Class 8A</span>
                                <span class="ka-mock-feed-time">15m</span>
                            </div>
                            <div class="ka-mock-feed-item">
                                <span class="ka-mock-dot ka-mock-dot-amber"></span>
                                <span class="ka-mock-feed-text">Parent meeting scheduled</span>
                                <span class="ka-mock-feed-time">1h</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ══════════════════════ TRUSTED STRIP ════════════════════ -->
<div class="ka-trusted">
    <div class="ka-container">
        <div class="ka-trusted-label">Trusted by schools across Africa</div>
        <div class="ka-trusted-badges">
            <span class="ka-trusted-badge">Lincoln Academy</span>
            <span class="ka-trusted-badge">Greenfield School</span>
            <span class="ka-trusted-badge">Ridgeview Collegiate</span>
            <span class="ka-trusted-badge">Maple Grove</span>
            <span class="ka-trusted-badge">Hillcrest Prep</span>
            <span class="ka-trusted-badge">Seaview School</span>
        </div>
    </div>
</div>

<!-- ══════════════════════ FEATURES ════════════════════════ -->
<section id="features" class="ka-section">
    <div class="ka-container">
        <header class="ka-section-header">
            <div class="ka-section-label">Platform</div>
            <h2 class="ka-section-title">Everything your school needs,<br>in one place</h2>
            <p class="ka-section-sub">KlassApp unifies every tool your admin team, teachers, and parents rely on — no more switching between apps.</p>
        </header>
        <div class="ka-features-grid">
            <article class="ka-feature-card">
                <div class="ka-feature-icon" aria-hidden="true">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                </div>
                <div>
                    <h3 class="ka-feature-title">Real-time Messaging</h3>
                    <p class="ka-feature-desc">Secure group and individual messaging with read receipts, scheduled sends, and file attachments.</p>
                </div>
            </article>
            <article class="ka-feature-card">
                <div class="ka-feature-icon" aria-hidden="true">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                </div>
                <div>
                    <h3 class="ka-feature-title">Attendance Tracking</h3>
                    <p class="ka-feature-desc">Fast daily check-ins, instant reporting, and automatic absence alerts delivered to parents.</p>
                </div>
            </article>
            <article class="ka-feature-card">
                <div class="ka-feature-icon" aria-hidden="true">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>
                </div>
                <div>
                    <h3 class="ka-feature-title">Parent Portal</h3>
                    <p class="ka-feature-desc">A simple, secure window for parents to track grades, attendance, and school communications.</p>
                </div>
            </article>
            <article class="ka-feature-card">
                <div class="ka-feature-icon" aria-hidden="true">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                </div>
                <div>
                    <h3 class="ka-feature-title">Homework &amp; Assignments</h3>
                    <p class="ka-feature-desc">Create, grade, and publish assignments with deadlines, rubrics, and attachment support.</p>
                </div>
            </article>
            <article class="ka-feature-card">
                <div class="ka-feature-icon" aria-hidden="true">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </div>
                <div>
                    <h3 class="ka-feature-title">Push Notifications</h3>
                    <p class="ka-feature-desc">Timely alerts for urgent messages, upcoming events, and schedule changes in real time.</p>
                </div>
            </article>
            <article class="ka-feature-card">
                <div class="ka-feature-icon" aria-hidden="true">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19h16M7 16V9M12 16V5M17 16v-3"/></svg>
                </div>
                <div>
                    <h3 class="ka-feature-title">Analytics Dashboard</h3>
                    <p class="ka-feature-desc">Actionable insights on attendance trends, engagement scores, and communication health.</p>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- ══════════════════════ HOW IT WORKS ════════════════════ -->
<section id="how" class="ka-section ka-section-tinted">
    <div class="ka-container">
        <header class="ka-section-header">
            <div class="ka-section-label">Getting started</div>
            <h2 class="ka-section-title">Up and running in minutes</h2>
            <p class="ka-section-sub">No training sessions. No complex onboarding. Your whole school connected from day one.</p>
        </header>
        <div class="ka-steps-grid">
            <div class="ka-step">
                <div class="ka-step-num">1</div>
                <div class="ka-step-body">
                    <h3 class="ka-step-title">Create your school account</h3>
                    <p class="ka-step-desc">Sign up and set your school name, term structure, and basic preferences in under five minutes.</p>
                </div>
            </div>
            <div class="ka-step">
                <div class="ka-step-num">2</div>
                <div class="ka-step-body">
                    <h3 class="ka-step-title">Import and invite everyone</h3>
                    <p class="ka-step-desc">Upload your student roster via CSV or SIS sync, then send invite links to staff and parents.</p>
                </div>
            </div>
            <div class="ka-step">
                <div class="ka-step-num">3</div>
                <div class="ka-step-body">
                    <h3 class="ka-step-title">Start communicating</h3>
                    <p class="ka-step-desc">Send announcements, take attendance, post homework, and track engagement from one dashboard.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════ STATS ══════════════════════════ -->
<div class="ka-stats-band">
    <div class="ka-container">
        <div class="ka-stats-grid">
            <div>
                <div class="ka-stat-number" data-target="500" data-suffix="+">0</div>
                <div class="ka-stat-label">Schools on the platform</div>
            </div>
            <div>
                <div class="ka-stat-number" data-target="200000" data-suffix="+">0</div>
                <div class="ka-stat-label">Active users daily</div>
            </div>
            <div>
                <div class="ka-stat-number" data-target="98" data-suffix="%">0</div>
                <div class="ka-stat-label">Customer satisfaction</div>
            </div>
            <div>
                <div class="ka-stat-number" data-target="1" data-suffix=" day">0</div>
                <div class="ka-stat-label">Average setup time</div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════ TESTIMONIALS ════════════════════ -->
<section id="testimonials" class="ka-section">
    <div class="ka-container">
        <header class="ka-section-header">
            <div class="ka-section-label">Social proof</div>
            <h2 class="ka-section-title">Schools love KlassApp</h2>
            <p class="ka-section-sub">From headteachers to finance officers — hear from those who've made the switch.</p>
        </header>
        <div class="ka-testimonials-grid">
            <article class="ka-testimonial-card">
                <p class="ka-testimonial-text">"KlassApp transformed our attendance workflow and saved our office team hours every single week. I can't imagine going back."</p>
                <div class="ka-testimonial-meta">
                    <div class="ka-testimonial-avatar" aria-hidden="true">SH</div>
                    <div>
                        <div class="ka-testimonial-name">Sarah H.</div>
                        <div class="ka-testimonial-role">Headteacher, Lincoln Academy</div>
                    </div>
                </div>
            </article>
            <article class="ka-testimonial-card">
                <p class="ka-testimonial-text">"Parents love the instant updates and our teachers spend far less time on admin. The parent portal alone is worth the subscription."</p>
                <div class="ka-testimonial-meta">
                    <div class="ka-testimonial-avatar" aria-hidden="true">MP</div>
                    <div>
                        <div class="ka-testimonial-name">Marcus P.</div>
                        <div class="ka-testimonial-role">Principal, Greenfield School</div>
                    </div>
                </div>
            </article>
            <article class="ka-testimonial-card">
                <p class="ka-testimonial-text">"Reliable, secure, and simple. Our finance team closes the month faster and with far fewer errors than before KlassApp."</p>
                <div class="ka-testimonial-meta">
                    <div class="ka-testimonial-avatar" aria-hidden="true">AF</div>
                    <div>
                        <div class="ka-testimonial-name">Aisha F.</div>
                        <div class="ka-testimonial-role">Finance Officer, Ridgeview Collegiate</div>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- ════════════════════════ PRICING ════════════════════════ -->
<section id="pricing" class="ka-section ka-section-tinted">
    <div class="ka-container">
        <header class="ka-section-header">
            <div class="ka-section-label">Pricing</div>
            <h2 class="ka-section-title">Simple, honest pricing</h2>
            <p class="ka-section-sub">Every plan includes a 30-day free trial. No credit card required to start.</p>
        </header>
        <div class="ka-plans-grid">
            <div class="ka-plan-card">
                <div class="ka-plan-name">Basic</div>
                <div class="ka-plan-price-row">
                    <span class="ka-plan-price">$25</span>
                    <span class="ka-plan-period">/mo</span>
                </div>
                <div class="ka-plan-sub">Per school, billed monthly</div>
                <hr class="ka-plan-divider">
                <div class="ka-plan-group-title">Core tools</div>
                <ul class="ka-plan-features">
                    <li>Student &amp; class roster management</li>
                    <li>Attendance tracking</li>
                    <li>Teacher–parent messaging</li>
                    <li>Parent portal (read-only)</li>
                    <li>Homework &amp; assignment posting</li>
                    <li>Basic grade entry</li>
                    <li>Class calendar &amp; scheduling</li>
                    <li>Email notifications</li>
                </ul>
                <div class="ka-plan-cta">
                    <a class="ka-btn ka-btn-outline" href="{{ url('/register') }}">Get started →</a>
                </div>
            </div>

            <div class="ka-plan-card highlight">
                <div class="ka-plan-badge">Most popular</div>
                <div class="ka-plan-name">Pro</div>
                <div class="ka-plan-price-row">
                    <span class="ka-plan-price">$35</span>
                    <span class="ka-plan-period">/mo</span>
                </div>
                <div class="ka-plan-sub">Per school, billed monthly</div>
                <hr class="ka-plan-divider">
                <div class="ka-plan-group-title">Power features</div>
                <ul class="ka-plan-features">
                    <li>Push notifications</li>
                    <li>Advanced analytics &amp; reports</li>
                    <li>Bulk messaging &amp; announcements</li>
                    <li>Assignment submission &amp; grading</li>
                    <li>Progress reports &amp; report cards</li>
                    <li>File &amp; document sharing</li>
                    <li>Message read receipts</li>
                    <li>Two-way parent portal interaction</li>
                </ul>
                <div class="ka-plan-cta">
                    <a class="ka-btn ka-btn-primary" href="{{ url('/register') }}">Get started →</a>
                </div>
            </div>

            <div class="ka-plan-card">
                <div class="ka-plan-name">Enterprise</div>
                <div class="ka-plan-price-row">
                    <span class="ka-plan-price" style="font-size:1.75rem;">Custom</span>
                </div>
                <div class="ka-plan-sub">Tailored to your institution</div>
                <hr class="ka-plan-divider">
                <div class="ka-plan-group-title">Everything in Pro, plus:</div>
                <ul class="ka-plan-features">
                    <li>SAML SSO / Active Directory</li>
                    <li>Custom SIS, LMS, ERP integrations</li>
                    <li>Dedicated account manager</li>
                    <li>Role-based admin access controls</li>
                    <li>FERPA &amp; GDPR compliance tools</li>
                    <li>Custom branding &amp; white-labeling</li>
                    <li>API access &amp; audit logs</li>
                    <li>SLA guarantee</li>
                </ul>
                <div class="ka-plan-cta">
                    <a class="ka-btn ka-btn-outline" href="https://calendly.com/moemucu/talk-to-mucu" target="_blank" rel="noopener noreferrer">Contact sales →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═════════════════════════ FAQ ═══════════════════════════ -->
<section id="faq" class="ka-section">
    <div class="ka-container">
        <header class="ka-section-header">
            <div class="ka-section-label">FAQ</div>
            <h2 class="ka-section-title">Frequently asked questions</h2>
        </header>
        <div class="ka-faq-list">
            <div class="ka-faq-item">
                <button class="ka-faq-btn" aria-expanded="false"><span>How long is the free trial?</span><span class="ka-faq-icon" aria-hidden="true">+</span></button>
                <div class="ka-faq-panel"><div class="ka-faq-panel-inner">All new accounts receive a full 30-day free trial with complete Pro features — no credit card required.</div></div>
            </div>
            <div class="ka-faq-item">
                <button class="ka-faq-btn" aria-expanded="false"><span>Is my school's data secure?</span><span class="ka-faq-icon" aria-hidden="true">+</span></button>
                <div class="ka-faq-panel"><div class="ka-faq-panel-inner">Yes — AES-256 encryption in transit and at rest, role-based access controls, and full audit logs for every action.</div></div>
            </div>
            <div class="ka-faq-item">
                <button class="ka-faq-btn" aria-expanded="false"><span>Can I import our existing student data?</span><span class="ka-faq-icon" aria-hidden="true">+</span></button>
                <div class="ka-faq-panel"><div class="ka-faq-panel-inner">Yes — CSV import is available on all plans. SIS integrations are supported for Pro and Enterprise during onboarding.</div></div>
            </div>
            <div class="ka-faq-item">
                <button class="ka-faq-btn" aria-expanded="false"><span>What support do you provide?</span><span class="ka-faq-icon" aria-hidden="true">+</span></button>
                <div class="ka-faq-panel"><div class="ka-faq-panel-inner">All plans include email support. Enterprise includes a dedicated account manager, phone support, and an SLA guarantee.</div></div>
            </div>
            <div class="ka-faq-item">
                <button class="ka-faq-btn" aria-expanded="false"><span>Can I cancel my subscription anytime?</span><span class="ka-faq-icon" aria-hidden="true">+</span></button>
                <div class="ka-faq-panel"><div class="ka-faq-panel-inner">Yes — monthly plans can be cancelled at any time. Annual plans are refundable within the first 30 days.</div></div>
            </div>
        </div>
    </div>
</section>

<!-- ═════════════════════════ CTA ═══════════════════════════ -->
<section class="ka-cta-section">
    <div class="ka-container">
        <div class="ka-cta-inner">
            <h2 class="ka-cta-title">Ready to lead your school into the future?</h2>
            <p class="ka-cta-sub">Set up takes minutes. No training required. Your whole school connected from day one.</p>
            <div class="ka-cta-actions">
                <a class="ka-btn ka-btn-green ka-btn-lg" href="{{ url('/register') }}">Start your free trial</a>
                <a class="ka-btn ka-btn-glass ka-btn-lg" href="{{ route('login') }}">Sign in to Portal</a>
            </div>
            <p class="ka-cta-note">Free 30-day trial · No credit card required · Cancel anytime</p>
        </div>
    </div>
</section>

<!-- ════════════════════════ FOOTER ═════════════════════════ -->
<footer class="ka-footer">
    <div class="ka-container">
        <div class="ka-footer-grid">
            <!-- Brand col -->
            <div>
                <img src="{{ asset('images/klassapplogo-dark.png') }}" alt="KlassApp">
                <p class="ka-footer-tagline">Smarter schools start here. One platform for every school stakeholder.</p>
            </div>
            <!-- Product -->
            <div>
                <div class="ka-footer-col-title">Product</div>
                <div class="ka-footer-links">
                    <a href="#features">Features</a>
                    <a href="#pricing">Pricing</a>
                    <a href="#how">How it works</a>
                    <a href="#faq">FAQ</a>
                </div>
            </div>
            <!-- Company -->
            <div>
                <div class="ka-footer-col-title">Company</div>
                <div class="ka-footer-links">
                    <a href="#">About</a>
                    <a href="#">Contact</a>
                    <a href="#">Terms</a>
                    <a href="#">Privacy</a>
                </div>
            </div>
            <!-- Social -->
            <div>
                <div class="ka-footer-col-title">Follow us</div>
                <div class="ka-footer-socials">
                    <!-- X / Twitter -->
                    <a href="#" class="ka-social-btn" aria-label="X (Twitter)" target="_blank" rel="noopener noreferrer">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.736-8.862L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <!-- LinkedIn -->
                    <a href="#" class="ka-social-btn" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <!-- Facebook -->
                    <a href="#" class="ka-social-btn" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="ka-footer-copy">
            <span>© {{ date('Y') }} KlassApp. All rights reserved.</span>
            <div class="ka-footer-copy-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<script>
(function () {
    'use strict';

    /* ── Nav scroll shadow ── */
    var nav = document.getElementById('kaMainNav');
    window.addEventListener('scroll', function () {
        nav.classList.toggle('scrolled', window.scrollY > 24);
    }, { passive: true });

    /* ── Desktop dropdown (click toggle for touch devices) ── */
    var homeDropdown = document.getElementById('kaHomeDropdown');
    var homeToggle   = document.getElementById('kaHomeToggle');
    if (homeDropdown && homeToggle) {
        homeToggle.addEventListener('click', function (e) {
            // Only intercept on non-hover devices (touch)
            if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
            e.preventDefault();
            homeDropdown.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (!homeDropdown.contains(e.target)) {
                homeDropdown.classList.remove('open');
            }
        });
        // Close dropdown when a link inside it is clicked
        homeDropdown.querySelectorAll('.ka-dropdown a').forEach(function (a) {
            a.addEventListener('click', function () {
                homeDropdown.classList.remove('open');
            });
        });
    }

    /* ── Mobile hamburger ── */
    var hamburger = document.getElementById('kaHamburger');
    var navMobile  = document.getElementById('kaNavMobile');
    hamburger.addEventListener('click', function () {
        var open = navMobile.classList.toggle('open');
        hamburger.classList.toggle('active', open);
        hamburger.setAttribute('aria-expanded', open ? 'true' : 'false');
        navMobile.setAttribute('aria-hidden', open ? 'false' : 'true');
    });

    /* ── Mobile Home accordion ── */
    var mobHomeToggle = document.getElementById('kaMobHomeToggle');
    var mobHomePanel  = document.getElementById('kaMobHomePanel');
    if (mobHomeToggle && mobHomePanel) {
        mobHomeToggle.addEventListener('click', function () {
            var open = mobHomePanel.classList.toggle('open');
            mobHomeToggle.classList.toggle('open', open);
            mobHomeToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    /* Close mobile menu when any link is tapped */
    navMobile.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
            navMobile.classList.remove('open');
            hamburger.classList.remove('active');
            hamburger.setAttribute('aria-expanded', 'false');
            navMobile.setAttribute('aria-hidden', 'true');
        });
    });

    /* ── Smooth scroll ── */
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var target = document.querySelector(this.getAttribute('href'));
            if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });

    /* ── Stat counters ── */
    function animateCounters() {
        document.querySelectorAll('[data-target]').forEach(function (el) {
            var target = parseInt(el.getAttribute('data-target'), 10);
            var suffix = el.getAttribute('data-suffix') || '';
            var t0 = null, dur = 1400;
            function step(ts) {
                if (!t0) t0 = ts;
                var pct = Math.min((ts - t0) / dur, 1);
                el.textContent = Math.floor(pct * target).toLocaleString() + (pct >= 1 ? suffix : '');
                if (pct < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        });
    }
    var band = document.querySelector('.ka-stats-band');
    if (band && 'IntersectionObserver' in window) {
        var so = new IntersectionObserver(function (e) {
            if (e[0].isIntersecting) { animateCounters(); so.disconnect(); }
        }, { threshold: 0.3 });
        so.observe(band);
    }

    /* ── FAQ accordion ── */
    document.querySelectorAll('.ka-faq-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var panel  = this.nextElementSibling;
            var isOpen = this.getAttribute('aria-expanded') === 'true';
            document.querySelectorAll('.ka-faq-btn').forEach(function (b) {
                b.setAttribute('aria-expanded', 'false');
                b.nextElementSibling.style.maxHeight = null;
            });
            if (!isOpen) {
                this.setAttribute('aria-expanded', 'true');
                panel.style.maxHeight = panel.scrollHeight + 'px';
            }
        });
    });

})();
</script>
</body>
</html>
