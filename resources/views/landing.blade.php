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
                    colors: {
                        'brand-blue': '#1E6FD9',
                        'brand-green': '#22C55E',
                        'brand-amber': '#D97706',
                        'navy': '#0D1526',
                        'surface': '#FAFAF5',
                        'dark': '#0F172A',
                    },
                    fontFamily: {
                        display: ['Sora', 'sans-serif'],
                        body: ['DM Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        /* ── Scroll reveal animations ── */
        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.8s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        /* ── Navbar ── */
        .navbar {
            transition: background 0.3s, box-shadow 0.3s;
        }
        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            box-shadow: 0 1px 0 rgba(0,0,0,0.06);
        }

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
        }
        .bubble-received {
            background: var(--wa-received);
            color: #1F2937;
            border-radius: 8px 8px 8px 0;
        }
        .bubble-system {
            background: var(--wa-system);
            color: #1F2937;
            border-radius: 8px;
        }

        /* ── Marquee ── */
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .marquee-track {
            animation: marquee 35s linear infinite;
        }

        /* ── Typewriter cursor ── */
        @keyframes blink {
            50% { opacity: 0; }
        }
        .typewriter-cursor {
            animation: blink 1s step-end infinite;
        }

        /* ── Mobile menu ── */
        .mobile-menu { display: none; }
        .mobile-menu.open { display: flex; }

        @media (max-width: 768px) {
            .phone-frame {
                width: 280px;
                height: 540px;
            }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════
     NAVIGATION
     ═══════════════════════════════════════════════════ -->
<nav id="mainNav" class="navbar fixed top-0 left-0 right-0 z-50 py-5 px-10">
    <div style="max-width: 1280px; margin: 0 auto; width: 100%; display: flex; align-items: center; justify-content: space-between;">
        <a href="#" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
            <img src="{{ asset('images/klassapp-logo-primary.svg') }}"
                 alt="KlassApp"
                 class="h-10 w-auto" />
            <span class="font-display text-xl font-semibold text-slate-900">KlassApp</span>
        </a>

        <div class="hidden md:flex items-center gap-8">
            <a href="#features" class="text-slate-600 hover:text-slate-900 transition text-sm font-medium">Features</a>
            <a href="#pricing" class="text-slate-600 hover:text-slate-900 transition text-sm font-medium">Pricing</a>
            <a href="#schools" class="text-slate-600 hover:text-slate-900 transition text-sm font-medium">Schools</a>
            <a href="#contact" class="text-slate-600 hover:text-slate-900 transition text-sm font-medium">Contact</a>
            <a href="#demo" class="bg-brand-blue text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition btn-scale">Get Started</a>
            <a href="https://wa.me/{{ str_replace('+', '', config('services.whatsapp.business_number')) }}?text=Hello%2C%20I'd%20like%20to%20learn%20about%20KlassApp"
               target="_blank"
               class="text-slate-500 hover:text-slate-900 transition text-sm font-medium flex items-center gap-1.5">
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
        <a href="#demo" class="bg-brand-blue text-white px-5 py-3 rounded-lg text-sm font-semibold text-center">Get Started</a>
    </div>
</nav>

<!-- ═══════════════════════════════════════════════════
     HERO
     ═══════════════════════════════════════════════════ -->
<section class="min-h-screen bg-surface flex items-center relative overflow-hidden pt-24">
    <div class="absolute inset-0 dot-grid opacity-50"></div>

    <div class="absolute top-1/4 -left-32 w-96 h-96 bg-brand-blue/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-1/4 -right-32 w-80 h-80 bg-brand-green/5 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-brand-amber/5 rounded-full blur-[80px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-6 lg:px-12 relative z-10 w-full">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-16 items-center">
            <div class="max-w-xl">
                <div class="w-12 h-1 bg-brand-amber rounded-full mb-4"></div>

                <!-- Audience Selector Tabs -->
                <div class="flex gap-2 mb-5 flex-wrap" role="tablist" aria-label="Audience selector">
                    <button class="audience-tab active px-4 py-2 rounded-full text-sm font-semibold transition cursor-pointer border-2 text-brand-green bg-white shadow-sm" style="border-color: #22C55E;" data-audience="admin">Administrators &amp; Principals</button>
                    <button class="audience-tab px-4 py-2 rounded-full text-sm font-semibold transition cursor-pointer border border-slate-300 text-slate-600 bg-white/70 hover:bg-white" data-audience="teacher">Teachers</button>
                    <button class="audience-tab px-4 py-2 rounded-full text-sm font-semibold transition cursor-pointer border border-slate-300 text-slate-600 bg-white/70 hover:bg-white" data-audience="parent">Parents</button>
                </div>

                <h1 class="font-display font-extrabold text-slate-900 leading-[1.08] tracking-[-0.02em]"
                    style="font-size: clamp(2.5rem, 5vw, 4.5rem); min-height: 1.2em;">
                    <span id="typewriter-text"></span><span id="typewriter-cursor" class="text-brand-amber">|</span>
                </h1>
                <div id="admin-tagline" class="font-display font-semibold text-slate-500 text-xl sm:text-2xl mt-2 mb-5 opacity-0 transition-opacity duration-700" style="letter-spacing: -0.01em;">
                    <span class="text-brand-amber/60">✦</span> And the system your admin team operates on.
                </div>
                <p class="text-lg leading-relaxed text-slate-600 mb-8 max-w-lg">
                    <span id="hero-keyword-box" class="inline-block" style="min-width: 6.5em;"><span id="hero-typelist" class="text-brand-green font-semibold"></span><span id="hero-typelist-cursor" class="text-brand-green">|</span></span>, delivered directly to parents on WhatsApp. No app. No login. Just a message. And for administrators, dashboards that make running a school feel effortless.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ url('/register') }}" class="bg-brand-green text-white font-semibold px-7 py-3.5 rounded-lg text-base hover:bg-green-600 transition inline-flex items-center gap-2">
                        Join
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                    </a>
                    <a href="{{ route('login') }}" class="bg-brand-blue text-white font-semibold px-7 py-3.5 rounded-lg text-base hover:bg-blue-700 transition inline-flex items-center gap-2">Portal</a>
                    <a href="https://calendly.com/moemucu/talk-to-mucu" target="_blank" rel="noopener noreferrer" class="bg-slate-100 text-slate-700 font-semibold px-7 py-3.5 rounded-lg text-base hover:bg-slate-200 transition inline-flex items-center gap-2">Book a demo</a>
                    <a href="https://wa.me/{{ str_replace('+', '', config('services.whatsapp.business_number')) }}?text=Hello%2C%20I'd%20like%20to%20learn%20about%20KlassApp" target="_blank" class="bg-white text-slate-700 font-semibold px-7 py-3.5 rounded-lg text-base border border-slate-200 hover:bg-slate-50 transition inline-flex items-center gap-2">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
                        WhatsApp
                    </a>
                    <a href="#how-it-works" class="text-slate-500 hover:text-slate-900 transition inline-flex items-center gap-1">See How It Works →</a>
                </div>
            </div>

            <!-- WhatsApp Phone Mockup -->
            <div class="flex justify-center lg:justify-end">
                <div class="phone-frame">
                    <div class="wa-header-bg p-3 flex items-center justify-between text-white" style="--wa-header: linear-gradient(135deg, #075E54 0%, #128C7E 100%);">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-xs">K</div>
                            <div>
                                <div class="text-sm font-semibold">KlassApp</div>
                                <div class="text-xs opacity-75">Typically responds instantly</div>
                            </div>
                        </div>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                    </div>
                    <div class="flex-1 p-4 overflow-y-auto" style="background: #E5DDD5; --wa-sent: #DCF8C6; --wa-received: #FFFFFF; --wa-system: rgba(0,0,0,0.05);">
                        <div class="flex justify-center mb-4"><span class="bubble-system text-xs px-3 py-1">Today</span></div>
                        <div class="flex justify-start mb-3"><div class="bubble-received text-sm p-2.5 max-w-[85%] shadow-sm">Welcome to KlassApp 👋<br/>Tap a topic to get your child's info:</div></div>
                        <div class="flex justify-start mb-3"><div class="p-2 max-w-[85%]"><div class="bg-white rounded-lg shadow-sm overflow-hidden"><div class="p-2 border-b text-sm font-medium text-slate-700">What would you like to check?</div><div class="p-1"><button class="w-full text-left px-3 py-2 hover:bg-slate-50 text-sm text-slate-600 rounded">📊 Grades</button><button class="w-full text-left px-3 py-2 hover:bg-slate-50 text-sm text-slate-600 rounded">💰 Fees</button><button class="w-full text-left px-3 py-2 hover:bg-slate-50 text-sm text-slate-600 rounded">📅 Attendance</button></div></div></div></div>
                    </div>
                    <div class="p-2 flex items-center gap-2 bg-slate-50 border-t">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="text-slate-400"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="text-slate-400"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
                        <div class="flex-1 bg-white rounded-full px-4 py-2 text-sm text-slate-400">Message...</div>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="text-slate-400"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     SCHOOLS
     ═══════════════════════════════════════════════════ -->
<section id="schools" class="py-20">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
        <div class="mb-4 text-center">
            <span class="text-brand-blue font-semibold text-sm uppercase tracking-wider">Trusted across Africa</span>
            <h2 class="font-display font-bold text-4xl md:text-5xl text-slate-900 mt-2">Smarter schools start here.</h2>
        </div>

        <!-- Marquee -->
        <div class="overflow-hidden relative">
            <div class="marquee-track flex gap-4 whitespace-nowrap" style="width: max-content;">
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 text-sm font-medium text-slate-700 shadow-sm">🏫 Kampala High School</span>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 text-sm font-medium text-slate-700 shadow-sm">🏫 St. Mary's College</span>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 text-sm font-medium text-slate-700 shadow-sm">🏫 Nairobi Prep</span>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 text-sm font-medium text-slate-700 shadow-sm">🏫 Dar Academy</span>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 text-sm font-medium text-slate-700 shadow-sm">🏫 Mombasa Secondary</span>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 text-sm font-medium text-slate-700 shadow-sm">🏫 Kigali International</span>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 text-sm font-medium text-slate-700 shadow-sm">🏫 Kampala High School</span>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 text-sm font-medium text-slate-700 shadow-sm">🏫 St. Mary's College</span>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 text-sm font-medium text-slate-700 shadow-sm">🏫 Nairobi Prep</span>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 text-sm font-medium text-slate-700 shadow-sm">🏫 Dar Academy</span>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 text-sm font-medium text-slate-700 shadow-sm">🏫 Mombasa Secondary</span>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 text-sm font-medium text-slate-700 shadow-sm">🏫 Kigali International</span>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     FOOTER
     ═══════════════════════════════════════════════════ -->
<footer class="py-16 bg-slate-50 border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
        <div class="flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/klassapp-logo-primary.svg') }}" alt="KlassApp" class="h-14 w-auto" />
                <p class="text-slate-600 text-sm max-w-xs">Smarter schools start here.</p>
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
        <div class="mt-10 pt-8 border-t border-slate-200 text-center">
            <p class="text-slate-500 text-sm">&copy; 2026 KlassApp. All rights reserved.</p>
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

    // ── Nav scroll effect ──
    const nav = document.querySelector('.navbar');
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 60);
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

    // ── Audience tabs ──
    const audienceCopy = {
        admin: { title: "The school in every parent's pocket.", subtitle: 'And the system your admin team operates on.' },
        teacher: { title: 'Spend less time on admin. More time doing what you love.', subtitle: 'KlassApp handles the noise so you can focus on what actually matters: teaching.' },
        parent: { title: "Always in the loop. Never in the dark.", subtitle: 'Real-time updates on attendance, grades, and school communications — all in one place, built for busy parents.' }
    };

    const titleEl = document.getElementById('typewriter-text');
    const cursorEl = document.getElementById('typewriter-cursor');
    const taglineEl = document.getElementById('admin-tagline');
    let heroAnimationToken = 0;
    let currentAudience = 'admin';
    let autoRotateInterval = null;

    function typeWriter(element, text, speed, token, callback) {
        element.textContent = '';
        cursorEl.classList.add('typewriter-cursor');
        let i = 0;
        function type() {
            if (token !== heroAnimationToken) return;
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                const delay = text.charAt(i - 1) === '.' ? 180 : speed;
                setTimeout(type, delay);
            } else {
                cursorEl.classList.remove('typewriter-cursor');
                setTimeout(() => {
                    if (token === heroAnimationToken && callback) callback();
                }, 500);
            }
        }
        type();
    }

    function animateHeroCopy(aud) {
        if (!titleEl || !taglineEl || !audienceCopy[aud]) return;
        heroAnimationToken += 1;
        const token = heroAnimationToken;

        titleEl.textContent = '';
        taglineEl.classList.remove('opacity-0');
        taglineEl.style.opacity = '0';

        typeWriter(titleEl, audienceCopy[aud].title, 45 + Math.random() * 35, token, function() {
            if (token !== heroAnimationToken) return;
            taglineEl.innerHTML = '<span class="text-brand-amber/60">✦</span> ' + audienceCopy[aud].subtitle;
            taglineEl.style.opacity = '1';
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

    // Manual click
    document.querySelectorAll('.audience-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (autoRotateInterval) {
                clearInterval(autoRotateInterval);
                autoRotateInterval = null;
            }
            setAudience(this.getAttribute('data-audience'));
        });
    });

    // Auto-rotate every 4 seconds
    function startAutoRotate() {
        const audiences = ['admin', 'teacher', 'parent'];
        let currentIndex = 0;
        autoRotateInterval = setInterval(() => {
            currentIndex = (currentIndex + 1) % audiences.length;
            setAudience(audiences[currentIndex]);
        }, 4000);
    }

    // Initial typewriter + tagline
    const typewriterText = audienceCopy.admin.title;
    let idx = 0;
    function typeNext() {
        if (idx < typewriterText.length) {
            titleEl.textContent += typewriterText.charAt(idx);
            idx++;
            const delay = typewriterText.charAt(idx - 1) === '.' ? 180 : 45 + Math.random() * 35;
            setTimeout(typeNext, delay);
        } else {
            cursorEl.classList.add('typewriter-cursor');
            setTimeout(() => {
                taglineEl.classList.remove('opacity-0');
                taglineEl.style.opacity = '1';
                startAutoRotate();
            }, 400);
        }
    }
    setTimeout(typeNext, 600);

    // ── Hero keyword typewriter loop ──
    const heroTypelistWords = ['Grades', 'fees', 'attendance', 'health', 'canteen', 'discipline', 'notifications', 'timetables', 'exams', 'reports'];
    const heroTypelistEl = document.getElementById('hero-typelist');
    const heroTypelistCursor = document.getElementById('hero-typelist-cursor');
    let currentWordIndex = 0;
    let currentCharIndex = 0;
    let isDeleting = false;

    function heroTypeTick() {
        const word = heroTypelistWords[currentWordIndex];
        if (isDeleting) {
            heroTypelistEl.textContent = word.substring(0, currentCharIndex - 1);
            currentCharIndex--;
        } else {
            heroTypelistEl.textContent = word.substring(0, currentCharIndex + 1);
            currentCharIndex++;
        }

        if (!isDeleting && currentCharIndex === word.length) {
            isDeleting = true;
            setTimeout(heroTypeTick, 1800);
        } else if (isDeleting && currentCharIndex === 0) {
            isDeleting = false;
            currentWordIndex = (currentWordIndex + 1) % heroTypelistWords.length;
            setTimeout(heroTypeTick, 400);
        } else {
            setTimeout(heroTypeTick, isDeleting ? 60 : 140);
        }
    }
    if (heroTypelistEl) setTimeout(heroTypeTick, 2200);
})();
</script>

<!-- Floating WhatsApp button -->
<a href="https://wa.me/{{ str_replace('+', '', config('services.whatsapp.business_number')) }}?text=Hello%2C%20I'd%20like%20to%20learn%20about%20KlassApp"
   target="_blank"
   class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full bg-[#25D366] shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center"
   aria-label="Chat on WhatsApp">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
</a>

</body>
</html>