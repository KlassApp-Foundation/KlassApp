<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>KlassApp — The School in Every Parent's Pocket</title>
    <meta name="description" content="KlassApp is a WhatsApp-first school management platform. Parents check grades, fees and attendance with a single message. No app. No login. Just WhatsApp." />
    <meta property="og:title" content="KlassApp — The School in Every Parent's Pocket" />
    <meta property="og:description" content="Grades, fees, and attendance delivered to parents on WhatsApp. Built for African schools." />
    <meta property="og:image" content="{{ asset('images/klassapp-logo-stacked.svg') }}" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/klassapp-logo.svg') }}" />

    <!-- Google Fonts: Sora + DM Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['Sora', 'sans-serif'],
                        body: ['DM Sans', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        navy: { DEFAULT: '#0D1526', light: '#141E36', dark: '#070C16' },
                        brand: { blue: '#1E6FD9', green: '#22C55E', amber: '#D97706' },
                        surface: '#FAFAF5',
                        warm: { 50: '#FFFCF5', 100: '#FEF7ED', 200: '#FDF0DB', 800: '#78350F' },
                    },
                },
            },
        };
    </script>

    <style>
        :root {
            --navy: #0F172A;
            --blue: #1E6FD9;
            --green: #22C55E;
            --amber: #D97706;
            --surface: #FFFFFF;
            --white: #FFFFFF;
            --wa-header: #075E54;
            --wa-bg: #E5DDD5;
            --wa-sent: #D9FDD3;
            --wa-received: #FFFFFF;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            color: #1F2937;
            overflow-x: hidden;
            background: #FFFFFF;
        }

        /* ── Scroll reveal ── */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.3s; }
        .reveal-delay-4 { transition-delay: 0.4s; }

        /* ── Navigation ── */
        /* ── Navbar (Flare-style 3-layer: header > nav-container > nav-pill) ── */
        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            height: 88px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .site-header.scrolled { height: 104px; }

        .nav-container {
            width: 100%;
            max-width: 1280px;
            margin: 0;
            transition: max-width 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                        margin 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .site-header.scrolled .nav-container {
            max-width: 1024px;
            margin: 0 104px;
        }

        .nav-pill {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 40px;
            padding: 0;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: padding 0.3s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
                        height 0.3s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
                        border-radius 0.3s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
                        background 0.3s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
                        box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
        }
        .site-header.scrolled .nav-pill {
            padding: 8px 8px 8px 20px;
            height: 56px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        .nav-pill .logo-text {
            font-family: 'Sora', sans-serif;
            font-size: 16px;
            font-weight: 500;
            color: #0D1526;
            transition: font-size 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                        font-weight 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .site-header.scrolled .nav-pill .logo-text {
            font-size: 20px;
            font-weight: 600;
        }

        .nav-pill .nav-links {
            display: flex;
            align-items: center;
            gap: 16px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: #475569;
            transition: gap 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .site-header.scrolled .nav-pill .nav-links { gap: 24px; }

        .nav-pill .nav-links a {
            color: #475569;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .nav-pill .nav-links a:hover { color: #1E6FD9; }

        .nav-pill .nav-cta {
            padding: 7px 18px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Sora', sans-serif;
            border-radius: 9999px;
            background: #1E6FD9;
            color: #fff;
            text-decoration: none;
            transition: padding 0.3s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
                        font-size 0.3s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
                        background 0.2s ease;
        }
        .nav-pill .nav-cta:hover { background: #22C55E; }
        .site-header.scrolled .nav-pill .nav-cta {
            padding: 10px 24px;
            font-size: 15px;
        }

        /* ── Hero dot-grid ── */
        .dot-grid {

        /* ── Hero dot-grid ── */
        .dot-grid {
            background-image: radial-gradient(rgba(255,255,255,0.035) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        /* ── WhatsApp Phone Mockup ── */
        .phone-frame {
            width: 340px;
            height: 660px;
            background: #fff;
            border-radius: 36px;
            box-shadow: 0 0 0 6px #1a1a2e, 0 0 0 8px #2a2a3e, 0 24px 80px rgba(0,0,0,0.5);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin: 0 auto;
            position: relative;
            flex-shrink: 0;
        }

        .wa-header-bg { background: var(--wa-header); }

        .bubble-sent {
            background: var(--wa-sent);
            color: #1F2937;
            border-radius: 8px 8px 0 8px;
            align-self: flex-end;
            max-width: 80%;
        }
        .bubble-received {
            background: var(--wa-received);
            color: #1F2937;
            border-radius: 0 8px 8px 8px;
            align-self: flex-start;
            max-width: 80%;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-6px); }
        }
        .typing-dot {
            width: 8px; height: 8px;
            background: #90A4AE;
            border-radius: 50%;
            animation: typing 1.4s ease-in-out infinite;
        }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .msg-animate {
            opacity: 0;
            animation: fadeUp 0.5s ease forwards;
        }
        .msg-1 { animation-delay: 0.5s; }
        .msg-2 { animation-delay: 1.2s; }
        .msg-3 { animation-delay: 2.0s; }
        .msg-4 { animation-delay: 3.0s; }
        .msg-5 { animation-delay: 3.8s; }

        /* ── WhatsApp List Message styles ── */
        .list-message {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            overflow: hidden;
            align-self: flex-start;
            max-width: 88%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .list-section {
            border-bottom: 1px solid #F3F4F6;
            padding: 10px 14px;
            cursor: pointer;
            transition: background 0.15s;
        }
        .list-section:last-child { border-bottom: none; }
        .list-section:hover { background: #F9FAFB; }
        .list-section-title {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
        }
        .list-section-desc {
            font-size: 11px;
            color: #6B7280;
            margin-top: 1px;
        }
        .list-footer {
            background: #F9FAFB;
            padding: 8px 14px;
            font-size: 11px;
            color: #9CA3AF;
            text-align: center;
            border-top: 1px solid #E5E7EB;
        }

        /* ── Social proof marquee ── */
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .marquee-track {
            animation: marquee 30s linear infinite;
        }
        .marquee-track:hover { animation-play-state: paused; }

        /* ── Pricing card highlight ── */
        .pricing-popular {
            transform: scale(1.05);
            border: 2px solid var(--blue);
        }

        /* ── CTA hover ── */
        .btn-scale {
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-scale:hover {
            transform: scale(1.04);
        }

        /* ── Dashboard mockup card ── */
        .dash-mockup {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: box-shadow 0.3s, transform 0.3s;
            position: relative;
        }
        .dash-mockup::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #D97706, #F59E0B);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .dash-mockup:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .dash-mockup:hover::before {
            opacity: 1;
        }
        .dash-header {
            background: #0D1526;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dash-dot { width: 8px; height: 8px; border-radius: 50%; }
        .dash-body { padding: 16px; }

        /* ── Premium school page mockup ── */
        .school-mockup {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.1);
            border: 1px solid #E5E7EB;
        }

        /* ── Testimonial avatar ── */
        .avatar-circle {
            width: 48px; height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.125rem;
            color: #fff;
            flex-shrink: 0;
        }

        /* ── Typewriter cursor ── */
        #typewriter-cursor {
            animation: blink 0.8s step-end infinite;
            font-weight: 100;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        #typewriter-text {
            border-right: 2px solid transparent;
        }

        /* ── CTA section pattern ── */
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .cta-pattern {
            background-image:
                radial-gradient(circle at 20% 50%, rgba(30,111,217,0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(30,111,217,0.03) 0%, transparent 50%);
        }

        /* ── Section accent bars ── */
        .accent-bar {
            width: 48px; height: 4px;
            border-radius: 2px;
            margin-bottom: 1rem;
        }

        /* ── Warm ambient backgrounds ── */
        .bg-warm-glow {
            background: #FAFAFA;
        }
        .bg-warm-glow-strong {
            background: #F5F5F5;
        }
        .bg-amber-glow {
            background: #F8F6F1;
        }

        /* ── African geometric pattern overlay ── */
        .pattern-diamonds {
            background-image:
                repeating-linear-gradient(45deg, rgba(217,119,6,0.04) 0px, rgba(217,119,6,0.04) 1px, transparent 1px, transparent 12px),
                repeating-linear-gradient(-45deg, rgba(217,119,6,0.04) 0px, rgba(217,119,6,0.04) 1px, transparent 1px, transparent 12px);
        }

        /* ── Decorative section number ── */
        .section-number {
            font-family: 'Sora', sans-serif;
            font-size: 8rem;
            font-weight: 800;
            line-height: 1;
            position: absolute;
            opacity: 0.04;
            user-select: none;
            pointer-events: none;
        }

        /* ── Gradient text ── */
        .text-gradient-amber {
            background: linear-gradient(135deg, #D97706, #F59E0B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Mobile hamburger ── */
        .mobile-menu { display: none; }
        .mobile-menu.open { display: flex; }

        @media (max-width: 768px) {
            .phone-frame {
                width: 280px;
                height: 540px;
            }
            .pricing-popular { transform: none; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════
     NAVIGATION
     ═══════════════════════════════════════════════════ -->
<!-- ═══════════════════════════════════════════════════
     NAVIGATION (Flare-style: header > nav-container > nav-pill)
     ═══════════════════════════════════════════════════ -->
<header id="mainNav" class="site-header">
    <div class="nav-container">
        <div class="nav-pill">
            <!-- Logo -->
            <a href="#" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                <img src="{{ asset('images/klassapp-logo-primary.svg') }}"
                     alt="KlassApp"
                     style="height: 32px; width: auto;" />
                <span class="logo-text">KlassApp</span>
            </a>
            <!-- Nav Links + CTA -->
            <div class="nav-links hidden md:flex" style="align-items: center;">
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <a href="#schools">Schools</a>
                <a href="#contact">Contact</a>
                <a href="#demo" class="nav-cta">Get Started</a>
                <a href="https://wa.me/{{ str_replace('+', '', config('services.whatsapp.business_number')) }}?text=Hello%2C%20I'd%20like%20to%20learn%20about%20KlassApp"
                   target="_blank"
                   style="display: flex; align-items: center; gap: 6px; color: #475569; text-decoration: none; font-size: 14px; font-weight: 500; font-family: 'DM Sans', sans-serif; transition: color 0.2s ease;"
                   onmouseover="this.style.color='#1E6FD9'" onmouseout="this.style.color='#475569'"
                   aria-label="Chat on WhatsApp">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
                    WhatsApp
                </a>
            </div>
        </div>
    </div>
</header>

        <div class="hidden md:flex items-center gap-8">
            <a href="#features" class="text-slate-600 hover:text-slate-900 transition text-sm font-medium">Features</a>
            <a href="#pricing" class="text-slate-600 hover:text-slate-900 transition text-sm font-medium">Pricing</a>
            <a href="#schools" class="text-slate-600 hover:text-slate-900 transition text-sm font-medium">Schools</a>
            <a href="#contact" class="text-slate-600 hover:text-slate-900 transition text-sm font-medium">Contact</a>
            <a href="#demo"
               class="bg-brand-blue text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition btn-scale">
                Get Started
            </a>
            <a href="https://wa.me/{{ str_replace('+', '', config('services.whatsapp.business_number')) }}?text=Hello%2C%20I'd%20like%20to%20learn%20about%20KlassApp"
               target="_blank"
               class="text-slate-500 hover:text-slate-900 transition text-sm font-medium flex items-center gap-1.5"
               aria-label="Chat on WhatsApp"
               style="display: flex; align-items: center; gap: 6px; color: #475569; text-decoration: none; font-size: 14px; font-weight: 500; font-family: 'DM Sans', sans-serif; transition: color 0.2s ease;"
               onmouseover="this.style.color='#1E6FD9'" onmouseout="this.style.color='#475569'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
                WhatsApp
            </a>
        </div>

        <button id="hamburger" class="md:hidden text-slate-700 p-2" aria-label="Menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 12h18M3 6h18M3 18h18"/>
            </svg>
        </button>
    </div>

    <div id="mobileMenu" class="mobile-menu absolute top-full left-0 right-0 bg-white flex-col py-6 px-6 gap-4 border-t border-slate-200 shadow-lg">
        <a href="#features" class="text-slate-600 hover:text-slate-900 text-base font-medium">Features</a>
        <a href="#pricing" class="text-slate-600 hover:text-slate-900 text-base font-medium">Pricing</a>
        <a href="#schools" class="text-slate-600 hover:text-slate-900 text-base font-medium">Schools</a>
        <a href="#contact" class="text-slate-600 hover:text-slate-900 text-base font-medium">Contact</a>
        <a href="#demo"
           class="bg-brand-blue text-white px-5 py-3 rounded-lg text-sm font-semibold text-center">
            Get Started
        </a>
    </div>
</nav>

<!-- ═══════════════════════════════════════════════════
     HERO
     ═══════════════════════════════════════════════════ -->
<section class="min-h-screen bg-warm-glow flex items-center relative overflow-hidden pt-32">
    <div class="absolute inset-0 bg-white">
        <div class="absolute inset-0 opacity-[0.08]" style="background-image: url('data:image/svg+xml,%3Csvg width=\u002260\u0022 height=\u002260\u0022 viewBox=\u00220 0 60 60\u0022 xmlns=\u0022http://www.w3.org/2000/svg\u0022%3E%3Cg fill=\u0022none\u0022 fill-rule=\u0022evenodd\u0022%3E%3Cg fill=\u0022%231E6FD9\u0022 fill-opacity=\u00220.3\u0022%3E%3Cpath d=\u0022M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\u0022/%3E%3C/g%3E%3C/g%3E%3C/svg%3E'); background-size: 60px 60px;"></div>
    </div>

    <div class="absolute top-1/4 -left-32 w-96 h-96 bg-brand-blue/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-1/4 -right-32 w-80 h-80 bg-brand-green/3 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-brand-amber/3 rounded-full blur-[80px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-6 lg:px-12 relative z-10 w-full">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-16 items-center">
            <div class="max-w-xl">
                <div class="accent-bar bg-brand-amber"></div>

                <!-- Audience Selector Tabs -->
                <div class="flex gap-2 mb-5 flex-wrap" role="tablist" aria-label="Audience selector">
                    <button class="audience-tab active px-4 py-2 rounded-full text-sm font-semibold transition cursor-pointer border border-slate-300 text-slate-900 bg-white shadow-sm" data-audience="admin">Administrators &amp; Principals</button>
                    <button class="audience-tab px-4 py-2 rounded-full text-sm font-semibold transition cursor-pointer border border-slate-300 text-slate-600 bg-white/70 hover:bg-white hover:shadow-sm" data-audience="teacher">Teachers</button>
                    <button class="audience-tab px-4 py-2 rounded-full text-sm font-semibold transition cursor-pointer border border-slate-300 text-slate-600 bg-white/70 hover:bg-white hover:shadow-sm" data-audience="parent">Parents</button>
                </div>

                <h1 class="font-display font-extrabold leading-[1.08] tracking-[-0.02em]"
                    style="font-size: clamp(2.5rem, 5vw, 4.5rem); background: linear-gradient(135deg, #1E6FD9 0%, #22C55E 35%, #D97706 70%, #1E6FD9 100%); background-size: 300% 300%; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; animation: gradientShift 6s ease infinite;">
                    <span id="typewriter-text"></span><span id="typewriter-cursor" class="text-brand-amber">|</span>
                </h1>
                <div id="admin-tagline" class="font-display font-semibold text-slate-500 text-xl sm:text-2xl mt-2 mb-5 opacity-0 transition-opacity duration-700" style="letter-spacing: -0.01em;">
                    <span class="text-brand-amber/60">✦</span> And the system your admin team operates on.
                </div>
                <p class="text-lg leading-relaxed text-slate-600 mb-8 max-w-lg">
                    <span id="hero-typelist" class="text-brand-green font-semibold"></span><span id="hero-typelist-cursor" class="text-brand-green">|</span>, delivered directly to parents on WhatsApp. No app. No login. Just a message. And for administrators, dashboards that make running a school feel effortless.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ url('/register') }}"
                       class="bg-brand-green text-white font-semibold px-7 py-3.5 rounded-lg text-base hover:bg-green-600 transition btn-scale inline-flex items-center gap-2">
                        Join
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M13 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <a href="{{ route('login') }}"
                       class="bg-brand-blue text-white font-semibold px-7 py-3.5 rounded-lg text-base hover:bg-blue-700 transition btn-scale inline-flex items-center gap-2">
                        Portal
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M13 12H3"/>
                        </svg>
                    </a>
                    <a href="https://calendly.com/moemucu/talk-to-mucu" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 bg-slate-100 text-slate-700 border border-slate-200 font-medium px-6 py-3 rounded-xl hover:bg-slate-200 transition btn-scale">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Book a demo
                    </a>
                    <a href="https://wa.me/{{ str_replace('+', '', config('services.whatsapp.business_number')) }}?text=Hello%2C%20I'd%20like%20to%20learn%20about%20KlassApp"
                       target="_blank"
                       class="inline-flex items-center gap-2 bg-[#25D366] text-white font-medium px-6 py-3 rounded-xl hover:bg-[#20BD5A] transition btn-scale"
                       aria-label="Chat on WhatsApp">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
                        Chat on WhatsApp
                    </a>
                    <a href="#how-it-works"
                       class="inline-flex items-center gap-2 bg-slate-100 text-slate-700 border border-slate-200 font-medium px-6 py-3 rounded-xl hover:bg-slate-200 transition btn-scale">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        See How It Works
                    </a>
                </div>
            </div>

            <!-- WhatsApp Phone Mockup with Interactive List Message -->
            <div class="flex justify-center lg:justify-end mt-8 lg:mt-0">
                <div class="phone-frame">
                    <!-- Dynamic Island -->
                    <div class="absolute top-2 left-1/2 -translate-x-1/2 w-[100px] h-[24px] bg-[#1a1a2e] rounded-[20px] z-20"></div>

                    <!-- Status Bar -->
                    <div class="wa-header-bg text-white flex justify-between px-6 pb-1 pt-3.5 text-[11px] font-semibold">
                        <span class="font-bold" id="waTime">9:41</span>
                        <div class="flex gap-1 items-center">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/></svg>
                        </div>
                    </div>

                    <!-- Chat Header -->
                    <div class="wa-header-bg text-white flex items-center gap-2.5 px-4 py-2">
                        <span class="w-8 h-8 rounded-lg overflow-hidden flex-shrink-0 bg-white flex items-center justify-center">
                            <img src="{{ asset('images/klassapp-logo.svg') }}" alt="K" class="w-6 h-6" />
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold leading-tight">KlassApp</div>
                            <div class="text-[10px] text-slate-400">Typically responds instantly</div>
                        </div>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="white" class="opacity-70 shrink-0"><path d="M12 7a2 2 0 110-4 2 2 0 010 4zm0 7a2 2 0 110-4 2 2 0 010 4zm0 7a2 2 0 110-4 2 2 0 010 4z"/></svg>
                    </div>

                    <!-- Chat Messages -->
                    <div class="flex-1 p-3 overflow-hidden flex flex-col gap-2.5" style="background: var(--wa-bg);">
                        <!-- Incoming: greeting -->
                        <div class="bubble-received msg-animate msg-1 px-3.5 py-2.5 text-sm leading-relaxed">
                            👋 Welcome to KlassApp!<br />
                            Tap an option below to get started.
                        </div>

                        <!-- Interactive List Message -->
                        <div class="list-message msg-animate msg-2">
                            <div style="padding: 12px 14px 6px;">
                                <div class="text-sm font-semibold text-gray-900">📋 What would you like to view?</div>
                                <div class="text-xs text-gray-500 mt-0.5">Tap any option for instant results</div>
                            </div>
                            <div style="margin-top: 6px;">
                                <div class="list-section">
                                    <div class="list-section-title">📊 GRADES</div>
                                    <div class="list-section-desc">Exam scores, term results & class position</div>
                                </div>
                                <div class="list-section">
                                    <div class="list-section-title">💰 FEES</div>
                                    <div class="list-section-desc">Real-time fee balance & payment history</div>
                                </div>
                                <div class="list-section">
                                    <div class="list-section-title">📅 ATTENDANCE</div>
                                    <div class="list-section-desc">Daily & term attendance summary</div>
                                </div>
                                <div class="list-section">
                                    <div class="list-section-title">📋 TIMETABLE</div>
                                    <div class="list-section-desc">Class schedule & upcoming periods</div>
                                </div>
                            </div>
                            <div class="list-footer">Tap to select • Powered by KlassApp</div>
                        </div>

                        <!-- Typing indicator -->
                        <div class="flex items-center gap-2 msg-animate msg-3" style="align-self: flex-start; padding: 8px 12px; background: #fff; border-radius: 16px 16px 16px 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                        </div>

                        <!-- Outgoing: grades result -->
                        <div class="bubble-sent msg-animate msg-4 px-3.5 py-2.5 text-sm leading-relaxed">
                            <div class="font-semibold text-gray-900 text-xs uppercase tracking-wide mb-1">📊 GRADES — Term 1 2026</div>
                            <div class="space-y-0.5">
                                <div class="flex justify-between"><span>Mathematics</span><span class="font-semibold text-green-700">A</span></div>
                                <div class="flex justify-between"><span>English</span><span class="font-semibold text-green-700">A-</span></div>
                                <div class="flex justify-between"><span>Science</span><span class="font-semibold text-blue-700">B+</span></div>
                                <div class="flex justify-between"><span>Social Studies</span><span class="font-semibold text-green-700">A</span></div>
                            </div>
                            <div class="text-[10px] text-gray-500 mt-2 pt-1.5 border-t border-gray-200">
                                Position: <strong>3rd</strong> out of 42 • Overall: <strong>A-</strong>
                            </div>
                        </div>

                        <!-- Incoming: satisfaction -->
                        <div class="bubble-received msg-animate msg-5 px-3.5 py-2 text-sm leading-relaxed">
                            ✅ You're all set! Reply any time or tap the menu again.
                        </div>
                    </div>

                    <!-- Chat Input Bar -->
                    <div class="flex items-center gap-2 px-3 py-2.5 border-t border-gray-200 bg-white">
                        <div class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm text-gray-400">Tap a menu option above...</div>
                        <div class="w-9 h-9 rounded-full bg-[#25D366] flex items-center justify-center">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     SOCIAL PROOF MARQUEE
     ═══════════════════════════════════════════════════ -->
<section class="bg-white py-8 overflow-hidden border-t border-gray-200/50">
    <div class="max-w-7xl mx-auto px-6">
        <p class="text-center text-sm font-medium text-gray-500 uppercase tracking-wider mb-5">
            Trusted by schools across Africa
        </p>
        <div class="relative overflow-hidden">
            <div class="flex gap-8 marquee-track w-max">
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🏫 Kampala High School</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">📚 St. Mary's College</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🌍 Light Academy</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">📖 Jinja College</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🎓 Mbarara High</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🇰🇪 Nairobi Prep</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🇹🇿 Dar es Salaam Academy</span>
                <!-- Duplicate for seamless scroll -->
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🏫 Kampala High School</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">📚 St. Mary's College</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🌍 Light Academy</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">📖 Jinja College</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🎓 Mbarara High</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🇰🇪 Nairobi Prep</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🇹🇿 Dar es Salaam Academy</span>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     WHY KLASSAPP (Problem / Solution)
     ═══════════════════════════════════════════════════ -->
<section id="features" class="py-24 bg-warm-glow-strong relative overflow-hidden">
    <div class="absolute inset-0 pattern-diamonds pointer-events-none"></div>
    <div class="max-w-7xl mx-auto px-6 lg:px-12 relative z-10">
        <div class="accent-bar bg-gradient-to-r from-brand-amber to-yellow-400 mx-auto"></div>
        <p class="text-brand-green font-semibold text-sm uppercase tracking-[0.15em] mb-3 text-center reveal">Why KlassApp</p>
        <h2 class="font-display font-bold text-4xl lg:text-5xl text-gray-900 text-center mb-16 reveal">
            School software has forgotten<br class="hidden sm:block" /> the most important person.
        </h2>

        <div class="grid md:grid-cols-2 gap-12 lg:gap-20">
            <div class="reveal">
                <h3 class="text-lg font-bold text-red-600 mb-6 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center text-sm">✕</span>
                    The old way
                </h3>
                <ul class="space-y-5">
                    <li class="flex gap-3 text-gray-600">
                        <span class="text-red-400 mt-0.5 shrink-0">✕</span>
                        <span>Parents find out about exam results at the end of term — weeks after they're published.</span>
                    </li>
                    <li class="flex gap-3 text-gray-600">
                        <span class="text-red-400 mt-0.5 shrink-0">✕</span>
                        <span>Fee reminders get lost in WhatsApp group noise, buried under hundreds of messages.</span>
                    </li>
                    <li class="flex gap-3 text-gray-600">
                        <span class="text-red-400 mt-0.5 shrink-0">✕</span>
                        <span>Absence notifications never reach home — parents discover their child skipped school weeks later.</span>
                    </li>
                    <li class="flex gap-3 text-gray-600">
                        <span class="text-red-400 mt-0.5 shrink-0">✕</span>
                        <span>School portals require data-heavy browsers that don't work on basic phones or slow networks.</span>
                    </li>
                </ul>
            </div>

            <div class="reveal reveal-delay-1">
                <h3 class="text-lg font-bold text-brand-green mb-6 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">✓</span>
                    With KlassApp
                </h3>
                <ul class="space-y-5">
                    <li class="flex gap-3 text-gray-600">
                        <span class="text-brand-green mt-0.5 shrink-0">✓</span>
                        <span>Grades delivered to WhatsApp the moment results are published — real-time, no waiting.</span>
                    </li>
                    <li class="flex gap-3 text-gray-600">
                        <span class="text-brand-green mt-0.5 shrink-0">✓</span>
                        <span>Personalised fee balance — one message away. Every parent gets their own data, not a group broadcast.</span>
                    </li>
                    <li class="flex gap-3 text-gray-600">
                        <span class="text-brand-green mt-0.5 shrink-0">✓</span>
                        <span>Absence detected by the school → parent notified in minutes via WhatsApp, directly.</span>
                    </li>
                    <li class="flex gap-3 text-gray-600">
                        <span class="text-brand-green mt-0.5 shrink-0">✓</span>
                        <span>Works on any phone, any network, no app required. Just WhatsApp — which parents already use every day.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     HOW IT WORKS (Interactive List Message Flow)
     ═══════════════════════════════════════════════════ -->
<section id="how-it-works" class="py-24 bg-amber-glow relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
        <div class="accent-bar bg-gradient-to-r from-brand-amber to-yellow-400 mx-auto"></div>
        <p class="text-brand-green font-semibold text-sm uppercase tracking-[0.15em] mb-3 text-center reveal">How It Works</p>
        <h2 class="font-display font-bold text-4xl lg:text-5xl text-gray-900 mb-4 text-center reveal">
            Tap. See. Done.
        </h2>
        <p class="text-gray-500 text-lg text-center max-w-xl mx-auto mb-14 reveal">
            Parents interact with KlassApp on WhatsApp just like chatting with a friend. No keywords to remember. No portals to learn.
        </p>

        <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            <!-- Step 1 -->
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 text-center reveal">
                <div class="w-16 h-16 rounded-2xl bg-brand-blue/10 text-brand-blue flex items-center justify-center text-2xl font-bold mx-auto mb-5">1</div>
                <div class="text-3xl mb-3">💬</div>
                <h3 class="font-display font-bold text-lg text-gray-900 mb-2">Parent Messages</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    A parent sends any message to the school's KlassApp WhatsApp number — a greeting, a question, anything.
                </p>
            </div>

            <!-- Step 2 -->
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 text-center reveal reveal-delay-1">
                <div class="w-16 h-16 rounded-2xl bg-brand-green/10 text-brand-green flex items-center justify-center text-2xl font-bold mx-auto mb-5">2</div>
                <div class="text-3xl mb-3">📋</div>
                <h3 class="font-display font-bold text-lg text-gray-900 mb-2">Instant Interactive Menu</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    KlassApp responds with a rich list menu — tap <strong>GRADES</strong>, <strong>FEES</strong>, or <strong>ATTENDANCE</strong> to get your data instantly.
                </p>
            </div>

            <!-- Step 3 -->
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 text-center reveal reveal-delay-2">
                <div class="w-16 h-16 rounded-2xl bg-brand-amber/10 text-brand-amber flex items-center justify-center text-2xl font-bold mx-auto mb-5">3</div>
                <div class="text-3xl mb-3">✅</div>
                <h3 class="font-display font-bold text-lg text-gray-900 mb-2">Personalised Response</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Fees, results, timetable — all formatted and delivered in seconds. Each parent sees only their own child's information.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     ROLE DASHBOARDS (Admin, Teacher, Accountant, WhatsApp)
     ═══════════════════════════════════════════════════ -->
<section class="py-24 bg-warm-glow-strong relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
        <div class="accent-bar bg-gradient-to-r from-brand-amber to-yellow-400 mx-auto"></div>
        <p class="text-brand-green font-semibold text-sm uppercase tracking-[0.15em] mb-3 text-center reveal">For Every Role</p>
        <h2 class="font-display font-bold text-4xl lg:text-5xl text-gray-900 text-center mb-4 reveal">
            Dashboards built for<br class="hidden sm:block" /> how your team works.
        </h2>
        <p class="text-gray-500 text-lg text-center max-w-2xl mx-auto mb-14 reveal">
            Separate, role-specific dashboards for admin, teachers, accountants, receptionists, and librarians — each with the tools they need and nothing they don't.
        </p>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Admin Dashboard -->
            <div class="dash-mockup reveal">
                <div class="dash-header">
                    <div class="dash-dot" style="background:#EF4444;"></div>
                    <div class="dash-dot" style="background:#F59E0B;"></div>
                    <div class="dash-dot" style="background:#22C55E;"></div>
                    <span class="text-slate-500 text-[10px] font-medium ml-2 tracking-wide">ADMIN DASHBOARD</span>
                </div>
                <div class="dash-body">
                    <div class="flex gap-2 mb-3">
                        <div class="flex-1 bg-blue-50 rounded-lg p-2.5 text-center">
                            <div class="text-lg font-bold text-brand-blue">846</div>
                            <div class="text-[10px] text-gray-500">Students</div>
                        </div>
                        <div class="flex-1 bg-green-50 rounded-lg p-2.5 text-center">
                            <div class="text-lg font-bold text-brand-green">42</div>
                            <div class="text-[10px] text-gray-500">Staff</div>
                        </div>
                        <div class="flex-1 bg-amber-50 rounded-lg p-2.5 text-center">
                            <div class="text-lg font-bold text-brand-amber">96%</div>
                            <div class="text-[10px] text-gray-500">Attendance</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="w-2 h-2 rounded-full bg-brand-green"></span>
                            <span>Fee collection this term</span>
                            <span class="ml-auto font-semibold text-gray-900">$12.4K</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="w-2 h-2 rounded-full bg-brand-blue"></span>
                            <span>Pending approvals</span>
                            <span class="ml-auto font-semibold text-gray-900">8</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Upcoming exams</span>
                            <span class="ml-auto font-semibold text-gray-900">3</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Teacher Dashboard -->
            <div class="dash-mockup reveal reveal-delay-1">
                <div class="dash-header">
                    <div class="dash-dot" style="background:#EF4444;"></div>
                    <div class="dash-dot" style="background:#F59E0B;"></div>
                    <div class="dash-dot" style="background:#22C55E;"></div>
                    <span class="text-slate-500 text-[10px] font-medium ml-2 tracking-wide">TEACHER DASHBOARD</span>
                </div>
                <div class="dash-body">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-full bg-brand-blue/20 flex items-center justify-center text-brand-blue font-bold text-sm">JM</div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900">Mathematics — S.3</div>
                            <div class="text-[11px] text-gray-500">42 students · 6 periods/week</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="text-brand-green">✓</span>
                            <span>Lesson plans this week</span>
                            <span class="ml-auto font-semibold text-gray-900">5/5</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="text-brand-amber">◷</span>
                            <span>Marks to submit</span>
                            <span class="ml-auto font-semibold text-gray-900">12</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="text-red-500">!</span>
                            <span>Homework pending review</span>
                            <span class="ml-auto font-semibold text-gray-900">4</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accountant Dashboard -->
            <div class="dash-mockup reveal reveal-delay-2">
                <div class="dash-header">
                    <div class="dash-dot" style="background:#EF4444;"></div>
                    <div class="dash-dot" style="background:#F59E0B;"></div>
                    <div class="dash-dot" style="background:#22C55E;"></div>
                    <span class="text-slate-500 text-[10px] font-medium ml-2 tracking-wide">ACCOUNTANT DASHBOARD</span>
                </div>
                <div class="dash-body">
                    <div class="flex gap-2 mb-3">
                        <div class="flex-1 bg-green-50 rounded-lg p-2.5 text-center">
                            <div class="text-lg font-bold text-brand-green">$8.2K</div>
                            <div class="text-[10px] text-gray-500">Collected</div>
                        </div>
                        <div class="flex-1 bg-red-50 rounded-lg p-2.5 text-center">
                            <div class="text-lg font-bold text-red-500">$3.1K</div>
                            <div class="text-[10px] text-gray-500">Outstanding</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="w-2 h-2 rounded-full bg-brand-green"></span>
                            <span>Payment rate</span>
                            <span class="ml-auto font-semibold text-gray-900">72%</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="w-2 h-2 rounded-full bg-brand-blue"></span>
                            <span>Pending payroll</span>
                            <span class="ml-auto font-semibold text-gray-900">18 staff</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Dashboard -->
            <div class="dash-mockup reveal reveal-delay-3">
                <div class="dash-header">
                    <div class="dash-dot" style="background:#EF4444;"></div>
                    <div class="dash-dot" style="background:#F59E0B;"></div>
                    <div class="dash-dot" style="background:#22C55E;"></div>
                    <span class="text-slate-500 text-[10px] font-medium ml-2 tracking-wide">WHATSAPP ANALYTICS</span>
                </div>
                <div class="dash-body">
                    <div class="flex gap-2 mb-3">
                        <div class="flex-1 bg-green-50 rounded-lg p-2.5 text-center">
                            <div class="text-lg font-bold text-brand-green">1,247</div>
                            <div class="text-[10px] text-gray-500">Sent</div>
                        </div>
                        <div class="flex-1 bg-blue-50 rounded-lg p-2.5 text-center">
                            <div class="text-lg font-bold text-brand-blue">98.2%</div>
                            <div class="text-[10px] text-gray-500">Delivered</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="w-2 h-2 rounded-full bg-brand-green"></span>
                            <span>Messages this month</span>
                            <span class="ml-auto font-semibold text-gray-900">3,842</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="w-2 h-2 rounded-full bg-brand-amber"></span>
                            <span>Active parents linked</span>
                            <span class="ml-auto font-semibold text-gray-900">312</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="text-[10px]">📊</span>
                            <span>Delivery rate</span>
                            <span class="ml-auto font-semibold text-gray-900">98.2%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-12 reveal">
            <a href="#demo" class="inline-flex items-center gap-2 text-brand-blue font-semibold hover:gap-3 transition-all">
                See all features →
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     FEATURES GRID (Comprehensive)
     ═══════════════════════════════════════════════════ -->
<section class="py-20 bg-amber-glow relative overflow-hidden">
    <div class="absolute inset-0 pattern-diamonds pointer-events-none"></div>
    <div class="max-w-7xl mx-auto px-6 lg:px-12 relative z-10">
        <div class="accent-bar bg-gradient-to-r from-brand-amber to-yellow-400 mx-auto"></div>
        <h2 class="font-display font-bold text-3xl lg:text-4xl text-gray-900 text-center mb-4 reveal">
            Everything your school needs,<br class="sm:hidden" /> all in one place.
        </h2>
        <p class="text-gray-500 text-center max-w-xl mx-auto mb-12 reveal">
            From academics to accounting, KlassApp covers the full picture — with WhatsApp at the centre.
        </p>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 max-w-5xl mx-auto">
            <div class="bg-white rounded-xl p-4 text-center border border-amber-100/50 shadow-sm hover:shadow-md hover:border-amber-300 hover:-translate-y-0.5 transition-all duration-200 reveal">
                <div class="text-2xl mb-1">📊</div>
                <div class="text-xs font-semibold text-gray-900">Exams & Marks</div>
            </div>
            <div class="bg-white rounded-xl p-4 text-center border border-amber-100/50 shadow-sm hover:shadow-md hover:border-amber-300 hover:-translate-y-0.5 transition-all duration-200 reveal reveal-delay-1">
                <div class="text-2xl mb-1">💰</div>
                <div class="text-xs font-semibold text-gray-900">Fees & Payments</div>
            </div>
            <div class="bg-white rounded-xl p-4 text-center border border-amber-100/50 shadow-sm hover:shadow-md hover:border-amber-300 hover:-translate-y-0.5 transition-all duration-200 reveal reveal-delay-2">
                <div class="text-2xl mb-1">📅</div>
                <div class="text-xs font-semibold text-gray-900">Attendance</div>
            </div>
            <div class="bg-white rounded-xl p-4 text-center border border-amber-100/50 shadow-sm hover:shadow-md hover:border-amber-300 hover:-translate-y-0.5 transition-all duration-200 reveal reveal-delay-3">
                <div class="text-2xl mb-1">📋</div>
                <div class="text-xs font-semibold text-gray-900">Timetables</div>
            </div>
            <div class="bg-white rounded-xl p-4 text-center border border-amber-100/50 shadow-sm hover:shadow-md hover:border-amber-300 hover:-translate-y-0.5 transition-all duration-200 reveal">
                <div class="text-2xl mb-1">📚</div>
                <div class="text-xs font-semibold text-gray-900">Homework</div>
            </div>
            <div class="bg-white rounded-xl p-4 text-center border border-amber-100/50 shadow-sm hover:shadow-md hover:border-amber-300 hover:-translate-y-0.5 transition-all duration-200 reveal reveal-delay-1">
                <div class="text-2xl mb-1">📝</div>
                <div class="text-xs font-semibold text-gray-900">Lesson Plans</div>
            </div>
            <div class="bg-white rounded-xl p-4 text-center border border-amber-100/50 shadow-sm hover:shadow-md hover:border-amber-300 hover:-translate-y-0.5 transition-all duration-200 reveal reveal-delay-2">
                <div class="text-2xl mb-1">🏛️</div>
                <div class="text-xs font-semibold text-gray-900">Library</div>
            </div>
            <div class="bg-white rounded-xl p-4 text-center border border-amber-100/50 shadow-sm hover:shadow-md hover:border-amber-300 hover:-translate-y-0.5 transition-all duration-200 reveal reveal-delay-3">
                <div class="text-2xl mb-1">💳</div>
                <div class="text-xs font-semibold text-gray-900">Payroll</div>
            </div>
            <div class="bg-white rounded-xl p-4 text-center border border-amber-100/50 shadow-sm hover:shadow-md hover:border-amber-300 hover:-translate-y-0.5 transition-all duration-200 reveal">
                <div class="text-2xl mb-1">📈</div>
                <div class="text-xs font-semibold text-gray-900">Reports</div>
            </div>
            <div class="bg-white rounded-xl p-4 text-center border border-amber-100/50 shadow-sm hover:shadow-md hover:border-amber-300 hover:-translate-y-0.5 transition-all duration-200 reveal reveal-delay-1">
                <div class="text-2xl mb-1">📢</div>
                <div class="text-xs font-semibold text-gray-900">Events</div>
            </div>
            <div class="bg-white rounded-xl p-4 text-center border border-green-100/50 shadow-sm hover:shadow-md hover:border-green-300 hover:-translate-y-0.5 transition-all duration-200 reveal reveal-delay-2">
                <div class="text-2xl mb-1">💬</div>
                <div class="text-xs font-semibold text-gray-900">WhatsApp</div>
            </div>
            <div class="bg-white rounded-xl p-4 text-center border border-amber-100/50 shadow-sm hover:shadow-md hover:border-amber-300 hover:-translate-y-0.5 transition-all duration-200 reveal reveal-delay-3">
                <div class="text-2xl mb-1">🏫</div>
                <div class="text-xs font-semibold text-gray-900">Multi-School</div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     FOR SCHOOLS + PREMIUM PAGES
     ═══════════════════════════════════════════════════ -->
<section id="schools" class="py-24 bg-white relative overflow-hidden">
    <div class="absolute inset-0 pattern-diamonds pointer-events-none"></div>
    <div class="absolute top-1/3 -right-32 w-96 h-96 bg-brand-amber/5 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="max-w-7xl mx-auto px-6 lg:px-12 relative z-10">
        <div class="accent-bar bg-gradient-to-r from-brand-amber to-yellow-400"></div>
        <h2 class="font-display font-bold text-4xl lg:text-5xl text-slate-900 mb-4 reveal">
            A complete school management<br class="hidden sm:block" /> platform — with parents built in.
        </h2>
        <p class="text-slate-600 text-lg max-w-2xl mb-14 reveal">
            KlassApp isn't just WhatsApp. It's a full school management system with admin, teacher, and bursar dashboards — and every feature is designed to keep parents informed. For parents, it's the simplest way to stay connected: no app, no login, just a familiar chat interface they already use every day.
        </p>

        <div class="grid sm:grid-cols-2 gap-6 mb-16">
            <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition reveal">
                <h3 class="font-display font-bold text-xl text-slate-900 mb-3">🏫 Admin Dashboard</h3>
                <p class="text-slate-600 leading-relaxed">
                    Manage students, staff, timetables and finances from one place. Multi-school support with granular permissions.
                </p>
            </div>
            <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition reveal reveal-delay-1">
                <h3 class="font-display font-bold text-xl text-slate-900 mb-3">💬 WhatsApp Notifications</h3>
                <p class="text-slate-600 leading-relaxed">
                    Broadcast fee reminders, exam results and school events to every parent automatically — no app needed.
                </p>
            </div>
            <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition reveal reveal-delay-2">
                <h3 class="font-display font-bold text-xl text-slate-900 mb-3">🌐 Premium School Pages</h3>
                <p class="text-slate-600 leading-relaxed">
                    Every school gets a beautiful public page on KlassApp — choose from 5 templates, customise colours and branding, and publish instantly. A professional digital presence at no extra development cost.
                </p>
            </div>
            <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition reveal reveal-delay-3">
                <h3 class="font-display font-bold text-xl text-slate-900 mb-3">👥 Multi-Role Access</h3>
                <p class="text-slate-600 leading-relaxed">
                    Admins, teachers, accountants, receptionists and librarians — each with their own dashboard and role-specific permissions.
                </p>
            </div>
            <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition reveal">
                <h3 class="font-display font-bold text-xl text-slate-900 mb-3">📊 Role Dashboards</h3>
                <p class="text-slate-600 leading-relaxed">
                    Dedicated dashboards for every role — teacher lesson plans & marks, accountant fee tracking & payroll, librarian books & lending.
                </p>
            </div>
            <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition reveal reveal-delay-1">
                <h3 class="font-display font-bold text-xl text-slate-900 mb-3">📈 Analytics & Reports</h3>
                <p class="text-slate-600 leading-relaxed">
                    Track attendance trends, fee collection rates, exam performance, and WhatsApp delivery metrics — all from one dashboard.
                </p>
            </div>
        </div>

        <!-- Premium School Pages Showcase -->
        <div class="reveal">
            <div class="text-center mb-10">
                <span class="inline-block bg-brand-amber/20 text-brand-amber text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-3">Premium Feature</span>
                <h3 class="font-display font-bold text-3xl text-slate-900 mb-3">Your school's public face on KlassApp</h3>
                <p class="text-slate-600 max-w-lg mx-auto">
                    Premium schools get a custom page with their branding, contact info, and messaging — ready in minutes.
                </p>
            </div>

            <div class="school-mockup max-w-2xl mx-auto bg-white">
                <!-- Mockup browser chrome -->
                <div class="flex items-center gap-2 px-4 py-3 bg-gray-100 border-b border-gray-200">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                        <div class="w-3 h-3 rounded-full bg-green-400"></div>
                    </div>
                    <div class="flex-1 max-w-md mx-auto bg-white rounded-full px-3 py-1.5 text-[11px] text-gray-400 text-center truncate border border-gray-200">
                        https://klassapp.com/school/kampala-high
                    </div>
                </div>
                <!-- Mockup page content -->
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-brand-blue flex items-center justify-center text-white font-bold text-lg">KH</div>
                        <div>
                            <div class="font-semibold text-gray-900">Kampala High School</div>
                            <div class="text-xs text-gray-500">Kampala, Uganda · Est. 1998</div>
                        </div>
                        <a href="#" class="ml-auto bg-brand-green text-white text-xs font-semibold px-4 py-2 rounded-full whitespace-nowrap">Chat on WhatsApp</a>
                    </div>
                    <div class="border-t border-gray-100 pt-4 grid grid-cols-3 gap-3 text-center text-xs">
                        <div>
                            <div class="font-bold text-gray-900">846</div>
                            <div class="text-gray-500">Students</div>
                        </div>
                        <div>
                            <div class="font-bold text-gray-900">42</div>
                            <div class="text-gray-500">Teachers</div>
                        </div>
                        <div>
                            <div class="font-bold text-gray-900">98%</div>
                            <div class="text-gray-500">Pass Rate</div>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-4 flex-wrap">
                        <span class="bg-blue-50 text-brand-blue text-[10px] font-medium px-2.5 py-1 rounded-full">National Curriculum</span>
                        <span class="bg-green-50 text-brand-green text-[10px] font-medium px-2.5 py-1 rounded-full">Day & Boarding</span>
                        <span class="bg-amber-50 text-brand-amber text-[10px] font-medium px-2.5 py-1 rounded-full">STEM Focus</span>
                    </div>
                </div>
            </div>
            <p class="text-center text-slate-500 text-sm mt-4">Choose from 5 templates · Custom colours · Instant publish</p>
        </div>

        <div class="text-center mt-12 reveal">
            <a href="#pricing" class="text-brand-blue font-semibold hover:underline inline-flex items-center gap-2">
                See plans &amp; pricing →
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     PRICING (Freemium)
     ═══════════════════════════════════════════════════ -->
<section id="pricing" class="py-24 bg-warm-glow relative overflow-hidden">
    <div class="absolute inset-0 pattern-diamonds pointer-events-none"></div>
    <div class="max-w-7xl mx-auto px-6 lg:px-12 relative z-10">
        <div class="accent-bar bg-gradient-to-r from-brand-amber to-yellow-400 mx-auto"></div>
        <h2 class="font-display font-bold text-4xl lg:text-5xl text-gray-900 text-center mb-4 reveal">
            Simple pricing.<br class="sm:hidden" /> No surprises.
        </h2>
        <p class="text-gray-500 text-lg text-center max-w-lg mx-auto mb-14 reveal">
            Start for free. Upgrade when you need more.
        </p>

        <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto items-start">
            <!-- Free / Starter -->
            <div class="bg-white rounded-2xl p-8 border border-gray-200 shadow-sm reveal">
                <h3 class="font-display font-bold text-xl text-gray-900 mb-2">Free</h3>
                <p class="text-gray-500 text-sm mb-6">Get started</p>
                <p class="text-5xl font-display font-bold text-gray-900 mb-6">
                    $0
                </p>
                <ul class="space-y-3 mb-8 text-sm text-gray-600">
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Up to <strong>200</strong> students
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        WhatsApp parent notifications
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Grades, fees, attendance on WhatsApp
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Basic admin dashboard
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        1 school
                    </li>
                </ul>
                <a href="#demo"
                   class="block text-center border-2 border-gray-200 text-gray-700 font-semibold py-3 rounded-xl hover:border-gray-300 transition btn-scale">
                    Get Started Free
                </a>
            </div>

            <!-- Growth (Popular) -->
            <div class="bg-white rounded-2xl p-8 border-2 border-brand-blue shadow-xl relative reveal"
                 style="transform: scale(1.05);">
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand-green text-white text-xs font-bold px-4 py-1 rounded-full">
                    Most Popular
                </span>
                <h3 class="font-display font-bold text-xl text-gray-900 mb-2">Growth</h3>
                <p class="text-gray-500 text-sm mb-6">For growing schools</p>
                <p class="text-5xl font-display font-bold text-gray-900 mb-6">
                    —
                </p>
                <ul class="space-y-3 mb-8 text-sm text-gray-600">
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Up to <strong>1,000</strong> students
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Everything in Free
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Bulk parent onboarding
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Custom school announcements
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Teacher &amp; accountant dashboards
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Priority support
                    </li>
                </ul>
                <a href="#demo"
                   class="block text-center bg-brand-blue text-white font-semibold py-3 rounded-xl hover:bg-blue-700 transition btn-scale">
                    Request Pricing
                </a>
            </div>

            <!-- Premium -->
            <div class="bg-white rounded-2xl p-8 border border-gray-200 shadow-sm reveal">
                <h3 class="font-display font-bold text-xl text-gray-900 mb-2">Premium</h3>
                <p class="text-gray-500 text-sm mb-6">Unlimited potential</p>
                <p class="text-5xl font-display font-bold text-gray-900 mb-6">
                    —
                </p>
                <ul class="space-y-3 mb-8 text-sm text-gray-600">
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Unlimited students
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Everything in Growth
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Premium school page (5 templates)
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Multi-school support
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Custom WhatsApp number
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Dedicated onboarding support
                    </li>
                </ul>
                <a href="#demo"
                   class="block text-center border-2 border-gray-200 text-gray-700 font-semibold py-3 rounded-xl hover:border-gray-300 transition btn-scale">
                    Talk to Sales
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     TESTIMONIALS
     ═══════════════════════════════════════════════════ -->
<section class="py-24 bg-amber-glow">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
        <div class="accent-bar bg-gradient-to-r from-brand-amber to-yellow-400 mx-auto"></div>
        <h2 class="font-display font-bold text-4xl lg:text-5xl text-gray-900 text-center mb-14 reveal">
            What schools are saying
        </h2>

        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 reveal">
                <p class="text-gray-600 leading-relaxed mb-6 italic">
                    "Since we started using KlassApp, parent inquiries about results dropped by 80%. They just tap GRADES and get everything."
                </p>
                <div class="flex items-center gap-3">
                    <div class="avatar-circle" style="background: #1E6FD9;">JM</div>
                    <div>
                        <div class="font-semibold text-sm text-gray-900">Joyce Mwangi</div>
                        <div class="text-xs text-gray-500">Head Teacher, Kampala High School</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 reveal reveal-delay-1">
                <p class="text-gray-600 leading-relaxed mb-6 italic">
                    "Our fee collection improved 40% in one term. Parents appreciate getting their balance in a single message instead of chasing the office."
                </p>
                <div class="flex items-center gap-3">
                    <div class="avatar-circle" style="background: #22C55E;">SO</div>
                    <div>
                        <div class="font-semibold text-sm text-gray-900">Samuel Okello</div>
                        <div class="text-xs text-gray-500">Bursar, Light Academy</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 reveal reveal-delay-2">
                <p class="text-gray-600 leading-relaxed mb-6 italic">
                    "The WhatsApp feature is a game-changer. Parents who never used our portal are now actively engaged — all through an app they already have."
                </p>
                <div class="flex items-center gap-3">
                    <div class="avatar-circle" style="background: #8B5CF6;">PN</div>
                    <div>
                        <div class="font-semibold text-sm text-gray-900">Peter Nsubuga</div>
                        <div class="text-xs text-gray-500">Director, St. Mary's College</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     CTA
     ═══════════════════════════════════════════════════ -->
<section id="demo" class="py-24 bg-white relative overflow-hidden border-t border-slate-200">
    <div class="max-w-3xl mx-auto px-6 text-center relative z-10">
        <h2 class="font-display font-bold text-4xl lg:text-5xl text-slate-900 mb-4 reveal">
            Ready to bring parents closer to school?
        </h2>
        <p class="text-slate-700 text-lg mb-10 max-w-xl mx-auto reveal">
            Join schools across Africa using KlassApp to keep every parent informed.
        </p>
        <div class="flex flex-wrap gap-4 justify-center reveal">
            <a href="{{ url('/register') }}"
               class="bg-brand-green text-white font-semibold px-7 py-3.5 rounded-lg text-base hover:bg-green-600 transition btn-scale inline-flex items-center gap-2">
                Join
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M13 5l7 7-7 7"/>
                </svg>
            </a>
            <a href="{{ route('login') }}"
               class="bg-brand-blue text-white font-semibold px-7 py-3.5 rounded-lg text-base hover:bg-blue-700 transition btn-scale inline-flex items-center gap-2">
                Portal
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M13 12H3"/>
                </svg>
            </a>
            <a href="https://calendly.com/moemucu/talk-to-mucu" target="_blank" rel="noopener noreferrer"
               class="bg-brand-blue text-white font-semibold px-7 py-3.5 rounded-lg text-base hover:bg-blue-700 transition btn-scale inline-flex items-center gap-2">
                Book a demo
            </a>
        </div>
        <p class="text-slate-600 text-sm mt-6 reveal">or
            <a href="https://wa.me/{{ str_replace('+', '', config('services.whatsapp.business_number')) }}?text=Hello%2C%20I'd%20like%20to%20request%20a%20demo"
               target="_blank" class="text-slate-900 font-semibold hover:underline">
                chat with us on WhatsApp →
            </a>
        </p>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     FOOTER
     ═══════════════════════════════════════════════════ -->
<footer class="relative py-20 bg-white border-t border-slate-200 overflow-hidden">
    <!-- Oversized decorative KlassApp wordmark in brand gradient colors -->
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none select-none overflow-hidden">
        <span class="font-display font-extrabold text-[18vw] leading-none tracking-tighter whitespace-nowrap"
              style="background: linear-gradient(135deg, #1E6FD9 0%, #22C55E 50%, #0F172A 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; opacity: 0.03; transform: translateY(10%);">KlassApp</span>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-12">
        <div class="flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/klassapp-logo-primary.svg') }}"
                     alt="KlassApp"
                     class="h-14 w-auto" />
                <p class="text-slate-600 text-sm max-w-xs">
                    Smarter schools start here.
                </p>
            </div>

            <div class="flex items-center gap-8">
                <a href="#" class="text-slate-500 hover:text-slate-900 text-sm transition">Terms</a>
                <a href="#" class="text-slate-500 hover:text-slate-900 text-sm transition">Privacy</a>
                <a href="#" class="text-slate-500 hover:text-slate-900 text-sm transition">Contact</a>
            </div>

            <div class="flex gap-3">
                <a href="#" class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center hover:bg-slate-200 transition text-slate-400 text-lg" aria-label="Facebook">f</a>
                <a href="#" class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center hover:bg-slate-200 transition text-slate-400 text-lg" aria-label="X">𝕏</a>
                <a href="#" class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center hover:bg-slate-200 transition text-slate-400 text-lg" aria-label="Instagram">◉</a>
                <a href="#" class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center hover:bg-slate-200 transition text-slate-400 text-lg" aria-label="LinkedIn">in</a>
            </div>
        </div>

        <div class="border-t border-slate-200 mt-12 pt-6 text-center text-slate-400 text-sm">
            &copy; {{ date('Y') }} KlassApp. All rights reserved.
        </div>
    </div>
</footer>

<!-- ═══════════════════════════════════════════════════
     JAVASCRIPT
     ═══════════════════════════════════════════════════ -->
<script>
(function() {
    // ── Scroll reveal observer ──
    const reveals = document.querySelectorAll('.reveal');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });
    reveals.forEach(el => observer.observe(el));

    // ── Nav scroll effect (Flare-style toggle .scrolled class) ──
    const header = document.querySelector('.site-header');
    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 60);
    });

    // ── Hamburger menu ──
    const hamburger = document.getElementById('hamburger');
    const mobileMenu = document.getElementById('mobileMenu');
    hamburger.addEventListener('click', () => {
        mobileMenu.classList.toggle('open');
    });
    mobileMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.remove('open');
        });
    });

    // ── Demo form — inline success ──
    const demoForm = document.getElementById('demoForm');
    const demoSuccess = document.getElementById('demoSuccess');
    if (demoForm) {
        demoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            demoForm.classList.add('hidden');
            demoSuccess.classList.remove('hidden');
        });
    }

    // ── Audience tabs ──
    const audienceCopy = {
        admin: {
            title: "The school in every parent's pocket.",
            subtitle: 'And the command centre your admin team runs on.'
        },
        teacher: {
            title: 'Spend less time on admin. More time doing what you love.',
            subtitle: 'KlassApp handles the noise so you can focus on what actually matters — teaching.'
        },
        parent: {
            title: "Always in the loop. Never in the dark.",
            subtitle: 'Real-time updates on attendance, grades, and school communications — all in one place, built for busy parents.'
        }
    };

    const titleEl = document.getElementById('typewriter-text');
    const cursorEl = document.getElementById('typewriter-cursor');
    const taglineEl = document.getElementById('admin-tagline');
    let heroAnimationToken = 0;
    let currentAudience = 'admin';

    function typeWriter(element, text, speed, token, callback) {
        element.textContent = '';
        element.classList.add('is-typing');
        let i = 0;
        function type() {
            if (token !== heroAnimationToken) return;
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                const delay = text.charAt(i - 1) === '.' ? 180 : speed;
                setTimeout(type, delay);
                return;
            }
            setTimeout(function() {
                if (token !== heroAnimationToken) return;
                element.classList.remove('is-typing');
                if (callback) callback();
            }, 500);
        }
        type();
    }

    function animateHeroCopy(aud) {
        if (!titleEl || !taglineEl || !audienceCopy[aud]) return;
        heroAnimationToken += 1;
        const token = heroAnimationToken;

        titleEl.classList.remove('hero-reveal', 'is-typing');
        taglineEl.classList.remove('hero-reveal');
        titleEl.style.opacity = '0';
        titleEl.style.transform = 'translateY(16px)';
        taglineEl.style.opacity = '0';
        taglineEl.style.transform = 'translateY(16px)';

        requestAnimationFrame(function() {
            titleEl.classList.add('hero-reveal');
            typeWriter(titleEl, audienceCopy[aud].title, 45 + Math.random() * 35, token, function() {
                taglineEl.innerHTML = '<span class="text-brand-amber/60">✦</span> ' + audienceCopy[aud].subtitle;
                taglineEl.classList.add('hero-reveal');
                taglineEl.classList.remove('opacity-0');
            });
        });
    }

    function setAudience(aud) {
        currentAudience = aud;
        document.querySelectorAll('.audience-tab').forEach(function(btn) {
            const isActive = btn.getAttribute('data-audience') === aud;
            btn.classList.toggle('active', isActive);
            if (isActive) {
                btn.classList.add('bg-white', 'text-slate-900', 'shadow-sm');
                btn.classList.remove('bg-white/70', 'text-slate-600', 'hover:bg-white');
            } else {
                btn.classList.remove('bg-white', 'text-slate-900', 'shadow-sm');
                btn.classList.add('bg-white/70', 'text-slate-600', 'hover:bg-white');
            }
        });
        animateHeroCopy(aud);
    }

    document.querySelectorAll('.audience-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            setAudience(this.getAttribute('data-audience'));
        });
    });

    // ── Typewriter effect initial run + admin tagline ──
    const typewriterText = audienceCopy.admin.title;
    const el = titleEl;
    const cursor = cursorEl;
    let idx = 0;
    function revealAdminTagline() {
        if (taglineEl) taglineEl.classList.remove('opacity-0');
    }
    function typeNext() {
        if (idx < typewriterText.length) {
            el.textContent += typewriterText.charAt(idx);
            idx++;
            const delay = typewriterText.charAt(idx - 1) === '.' ? 180 : 45 + Math.random() * 35;
            setTimeout(typeNext, delay);
        } else {
            cursor.style.animation = 'blink 0.8s step-end infinite';
            setTimeout(revealAdminTagline, 400);
        }
    }
    setTimeout(typeNext, 600);

    // ── Hero Typelist Effect (looping keywords) ──
    const heroTypelistWords = ['Grades', 'fees', 'attendance', 'health', 'canteen', 'discipline', 'notifications', 'timetables', 'exams', 'reports'];
    const heroTypelistEl = document.getElementById('hero-typelist');
    const heroTypelistCursor = document.getElementById('hero-typelist-cursor');
    let currentWordIndex = 0;
    let currentCharIndex = 0;
    let isDeleting = false;
    let heroTypeSpeed = 120;

    function heroTypeTick() {
        const word = heroTypelistWords[currentWordIndex];
        if (isDeleting) {
            heroTypelistEl.textContent = word.substring(0, currentCharIndex - 1);
            currentCharIndex--;
            heroTypeSpeed = 60;
        } else {
            heroTypelistEl.textContent = word.substring(0, currentCharIndex + 1);
            currentCharIndex++;
            heroTypeSpeed = 140;
        }

        if (!isDeleting && currentCharIndex === word.length) {
            isDeleting = true;
            heroTypeSpeed = 1800; // pause at full word
        } else if (isDeleting && currentCharIndex === 0) {
            isDeleting = false;
            currentWordIndex = (currentWordIndex + 1) % heroTypelistWords.length;
            heroTypeSpeed = 400; // pause before next word
        }

        setTimeout(heroTypeTick, heroTypeSpeed);
    }
    if (heroTypelistEl) setTimeout(heroTypeTick, 2200);

    // ── WhatsApp time ──
    const now = new Date();
    const hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const displayHour = hours % 12 || 12;
    document.getElementById('waTime').textContent = displayHour + ':' + minutes + ' ' + ampm;
})();
</script>

{{-- Floating WhatsApp button --}}
<a href="https://wa.me/{{ str_replace('+', '', config('services.whatsapp.business_number')) }}?text=Hello%2C%20I'd%20like%20to%20learn%20about%20KlassApp"
   target="_blank"
   class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full bg-[#25D366] shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center"
   aria-label="Chat on WhatsApp">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
</a>

</body>
</html>
