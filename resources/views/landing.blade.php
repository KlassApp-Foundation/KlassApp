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
                        brand: { blue: '#1E6FD9', green: '#22C55E' },
                        surface: '#F8FAFC',
                    },
                },
            },
        };
    </script>

    <style>
        :root {
            --navy: #0D1526;
            --blue: #1E6FD9;
            --green: #22C55E;
            --surface: #F8FAFC;
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

        /* ── Nav styling ── */
        .navbar {
            transition: background 0.3s, backdrop-filter 0.3s;
        }
        .navbar.scrolled {
            background: rgba(13, 21, 38, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
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

        /* ── Social proof marquee ── */
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .marquee-track {
            animation: marquee 25s linear infinite;
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

        /* ── CTA section pattern ── */
        .cta-pattern {
            background-image:
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(255,255,255,0.05) 0%, transparent 50%);
        }

        /* ── Mobile hamburger ── */
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

<!-- ════════════════════════════════════════════════
     SECTION 1: NAVIGATION
     ════════════════════════════════════════════════ -->
<nav class="navbar fixed top-0 left-0 right-0 z-50 py-4 px-6 lg:px-12">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <!-- Logo -->
        <a href="#" class="flex-shrink-0">
             <img src="{{ asset('images/klassapp-logo-primary.svg') }}"
                  alt="KlassApp"
                  class="h-14 w-auto" />
        </a>

        <!-- Desktop nav links -->
        <div class="hidden md:flex items-center gap-8">
            <a href="#features" class="text-white/75 hover:text-white transition text-sm font-medium">Features</a>
            <a href="#pricing" class="text-white/75 hover:text-white transition text-sm font-medium">Pricing</a>
            <a href="#schools" class="text-white/75 hover:text-white transition text-sm font-medium">Schools</a>
            <a href="#contact" class="text-white/75 hover:text-white transition text-sm font-medium">Contact</a>
                <a href="#demo"
                   class="bg-brand-blue text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition btn-scale">
                    Get Started
                </a>
                <a href="https://wa.me/{{ str_replace('+', '', config('services.whatsapp.business_number')) }}?text=Hello%2C%20I'd%20like%20to%20learn%20about%20KlassApp"
                   target="_blank"
                   class="text-white/75 hover:text-white transition text-sm font-medium flex items-center gap-1.5"
                   aria-label="Chat on WhatsApp">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
                    WhatsApp
                </a>
            </div>

        <!-- Hamburger (mobile) -->
        <button id="hamburger" class="md:hidden text-white p-2" aria-label="Menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 12h18M3 6h18M3 18h18"/>
            </svg>
        </button>
    </div>

    <!-- Mobile menu -->
    <div id="mobileMenu" class="mobile-menu absolute top-full left-0 right-0 bg-navy-light flex-col py-6 px-6 gap-4 border-t border-white/10">
        <a href="#features" class="text-white/75 hover:text-white text-base font-medium">Features</a>
        <a href="#pricing" class="text-white/75 hover:text-white text-base font-medium">Pricing</a>
        <a href="#schools" class="text-white/75 hover:text-white text-base font-medium">Schools</a>
        <a href="#contact" class="text-white/75 hover:text-white text-base font-medium">Contact</a>
        <a href="#demo"
           class="bg-brand-blue text-white px-5 py-3 rounded-lg text-sm font-semibold text-center">
            Get Started
        </a>
    </div>
</nav>

<!-- ════════════════════════════════════════════════
     SECTION 2: HERO
     ════════════════════════════════════════════════ -->
<section class="min-h-screen bg-navy flex items-center relative overflow-hidden pt-20">
    <!-- Dot grid texture overlay -->
    <div class="absolute inset-0 dot-grid pointer-events-none"></div>

    <!-- Gradient mesh -->
    <div class="absolute top-1/4 -left-32 w-96 h-96 bg-brand-blue/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-1/4 -right-32 w-80 h-80 bg-brand-green/5 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-6 lg:px-12 relative z-10 w-full">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-16 items-center">
            <!-- Hero Content -->
            <div class="max-w-xl">
                <h1 class="font-display font-extrabold text-white leading-[1.08] tracking-[-0.02em] mb-5"
                    style="font-size: clamp(2.5rem, 5vw, 4.5rem);">
                    The school in every parent's pocket.
                </h1>
                <p class="text-lg leading-relaxed text-white/70 mb-8 max-w-lg">
                    KlassApp brings <span class="text-brand-green font-semibold">grades</span>,
                    <span class="text-brand-green font-semibold">fees</span>, and
                    <span class="text-brand-green font-semibold">attendance</span> directly to parents on WhatsApp —
                    no app downloads, no logins, no friction.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="#demo"
                       class="bg-brand-blue text-white font-semibold px-7 py-3.5 rounded-lg text-base hover:bg-blue-700 transition btn-scale inline-flex items-center gap-2">
                        Request a Demo
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M13 5l7 7-7 7"/>
                        </svg>
                    </a>
    <a href="#how-it-works"
       class="inline-flex items-center gap-2 bg-white/10 text-white border border-white/20 font-medium px-6 py-3 rounded-xl hover:bg-white/20 transition btn-scale reveal">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
        Watch Demo
    </a>
    <a href="https://wa.me/{{ str_replace('+', '', config('services.whatsapp.business_number')) }}?text=Hello%2C%20I'd%20like%20to%20learn%20about%20KlassApp"
       target="_blank"
       class="inline-flex items-center gap-2 bg-[#25D366] text-white font-medium px-6 py-3 rounded-xl hover:bg-[#20BD5A] transition btn-scale reveal"
       aria-label="Chat on WhatsApp">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
        Chat on WhatsApp
    </a>
                </div>
            </div>

            <!-- WhatsApp Phone Mockup -->
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
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold shrink-0">K</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[14px] font-semibold truncate">KlassApp</div>
                            <div class="text-[11px] text-white/70">online</div>
                        </div>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="white" class="opacity-80">
                            <path d="M12 7a2 2 0 1 0-.001-4.001A2 2 0 0 0 12 7zm0 2a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 9zm0 6a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 15z"/>
                        </svg>
                    </div>

                    <!-- Chat Messages (wa-bg) -->
                    <div class="flex-1 bg-[#E5DDD5] p-3 overflow-y-auto flex flex-col gap-2"
                         style="background-image: url('data:image/svg+xml,%3Csvg width=\'32\' height=\'32\' viewBox=\'0 0 32 32\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\' fill-rule=\'evenodd\'%3E%3Ccircle cx=\'16\' cy=\'16\' r=\'1\'/%3E%3C/g%3E%3C/svg%3E');">

                        <!-- Typing indicator (before message) -->
                        <div class="bubble-received self-start px-4 py-3 msg-animate" style="animation-delay: 0.3s;">
                            <div class="flex gap-1.5 items-center">
                                <span class="typing-dot"></span>
                                <span class="typing-dot"></span>
                                <span class="typing-dot"></span>
                            </div>
                        </div>

                        <!-- Parent sends "grades" -->
                        <div class="bubble-sent px-4 py-2.5 msg-animate" style="animation-delay: 1.2s;">
                            <div class="text-[14px] leading-relaxed">grades</div>
                            <div class="text-[10px] text-gray-500 text-right mt-1">9:42 AM</div>
                        </div>

                        <!-- KlassApp receives + responds -->
                        <div class="bubble-received self-start px-4 py-3 msg-animate" style="animation-delay: 2.5s;">
                            <div class="text-[14px] leading-relaxed whitespace-pre-line font-sans">
                                <span class="font-bold">📊 John Mukasa — S.3 West</span>

                                <span class="block mt-2 font-semibold text-sm">Mid-Term Examinations</span>
                                Mathematics: 78% (B)
                                English: 85% (A)
                                Physics: 71% (B)
                                Chemistry: 69% (C)
                                <span class="block mt-2 pt-1.5 border-t border-gray-200 text-sm">
                                    <span class="font-semibold">Overall:</span> 76% |
                                    <span class="font-semibold">Position:</span> 4/45
                                </span>
                            </div>
                            <div class="text-[10px] text-gray-500 text-right mt-1">9:42 AM</div>
                        </div>

                        <!-- Typing indicator for follow-up -->
                        <div class="bubble-received self-start px-4 py-3 msg-animate" style="animation-delay: 4s;">
                            <div class="flex gap-1.5 items-center">
                                <span class="typing-dot"></span>
                                <span class="typing-dot"></span>
                                <span class="typing-dot"></span>
                            </div>
                        </div>

                        <!-- Follow-up: fees prompt -->
                        <div class="bubble-received self-start px-4 py-3 msg-animate" style="animation-delay: 5.5s;">
                            <div class="text-[14px] leading-relaxed">
                                Reply <span class="font-bold text-brand-green">FEES</span> to check fee balance.
                            </div>
                            <div class="text-[10px] text-gray-500 text-right mt-1">9:42 AM</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════
     SECTION 3: SOCIAL PROOF BAR
     ════════════════════════════════════════════════ -->
<section class="bg-surface py-8 overflow-hidden border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-6">
        <p class="text-center text-sm font-medium text-gray-500 uppercase tracking-wider mb-5">
            Trusted by schools across Uganda
        </p>
        <div class="relative overflow-hidden">
            <div class="flex gap-8 marquee-track w-max">
                <!-- Row 1 -->
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🏫 Kampala High School</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">📚 St. Mary's College</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🌍 Light Academy</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">📖 Jinja College</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🎓 Mbarara High</span>
                <!-- Duplicate for seamless scroll -->
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🏫 Kampala High School</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">📚 St. Mary's College</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🌍 Light Academy</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">📖 Jinja College</span>
                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-white rounded-full border border-gray-200 text-sm font-medium text-gray-700 shadow-sm">🎓 Mbarara High</span>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════
     SECTION 4: THE PROBLEM
     ════════════════════════════════════════════════ -->
<section id="features" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
        <p class="text-brand-green font-semibold text-sm uppercase tracking-[0.15em] mb-3 reveal">Why KlassApp</p>
        <h2 class="font-display font-bold text-4xl lg:text-5xl text-gray-900 mb-16 reveal">
            School software has forgotten<br class="hidden sm:block" /> the most important person.
        </h2>

        <div class="grid md:grid-cols-2 gap-12 lg:gap-20">
            <!-- Pain points -->
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

            <!-- Solutions -->
            <div class="reveal">
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

<!-- ════════════════════════════════════════════════
     SECTION 5: HOW IT WORKS
     ════════════════════════════════════════════════ -->
<section id="how-it-works" class="py-24 bg-surface">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
        <p class="text-brand-green font-semibold text-sm uppercase tracking-[0.15em] mb-3 text-center reveal">Simple</p>
        <h2 class="font-display font-bold text-4xl lg:text-5xl text-gray-900 mb-4 text-center reveal">
            Three keywords.<br />Complete visibility.
        </h2>
        <p class="text-gray-500 text-lg text-center max-w-xl mx-auto mb-14 reveal">
            Parents text a keyword to KlassApp on WhatsApp. Instantly, they get exactly what they need.
        </p>

        <div class="grid md:grid-cols-3 gap-8">
            <!-- Card 1: Grades -->
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-md transition reveal">
                <div class="text-4xl mb-4">📊</div>
                <h3 class="font-display font-bold text-xl text-gray-900 mb-3">Exam Results</h3>
                <p class="text-gray-500 leading-relaxed">
                    Text <span class="font-bold text-brand-green">GRADES</span> to get formatted results for every child — scores, grades, class position — delivered instantly.
                </p>
            </div>

            <!-- Card 2: Fees -->
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-md transition reveal">
                <div class="text-4xl mb-4">💰</div>
                <h3 class="font-display font-bold text-xl text-gray-900 mb-3">Fee Balance</h3>
                <p class="text-gray-500 leading-relaxed">
                    Text <span class="font-bold text-brand-green">FEES</span> for a real-time fee balance breakdown. No more surprises at the school gate.
                </p>
            </div>

            <!-- Card 3: Attendance -->
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-md transition reveal">
                <div class="text-4xl mb-4">📅</div>
                <h3 class="font-display font-bold text-xl text-gray-900 mb-3">Attendance</h3>
                <p class="text-gray-500 leading-relaxed">
                    Text <span class="font-bold text-brand-green">ATTENDANCE</span> for a term summary. Schools can also send automatic absence alerts.
                </p>
            </div>
        </div>

        <p class="text-center text-gray-500 mt-10 text-sm reveal">
            And much more — events, announcements, payment instructions, all on WhatsApp.
        </p>
    </div>
</section>

<!-- ════════════════════════════════════════════════
     SECTION 6: FOR SCHOOLS
     ════════════════════════════════════════════════ -->
<section id="schools" class="py-24 bg-navy">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
        <h2 class="font-display font-bold text-4xl lg:text-5xl text-white mb-4 reveal">
            A complete school management<br class="hidden sm:block" /> platform — with parents built in.
        </h2>
        <p class="text-white/60 text-lg max-w-2xl mb-14 reveal">
            KlassApp isn't just WhatsApp. It's a full school management system with admin, teacher, and bursar dashboards — and every feature is designed to keep parents informed.
        </p>

        <div class="grid sm:grid-cols-2 gap-6">
            <div class="bg-white/5 rounded-2xl p-8 border border-white/10 hover:bg-white/10 transition reveal">
                <h3 class="font-display font-bold text-xl text-white mb-3">🏫 Admin Dashboard</h3>
                <p class="text-white/60 leading-relaxed">
                    Manage students, staff, timetables and finances from one place. Multi-school support with granular permissions.
                </p>
            </div>
            <div class="bg-white/5 rounded-2xl p-8 border border-white/10 hover:bg-white/10 transition reveal">
                <h3 class="font-display font-bold text-xl text-white mb-3">💬 WhatsApp Notifications</h3>
                <p class="text-white/60 leading-relaxed">
                    Broadcast fee reminders, exam results and school events to every parent automatically — no app needed.
                </p>
            </div>
            <div class="bg-white/5 rounded-2xl p-8 border border-white/10 hover:bg-white/10 transition reveal">
                <h3 class="font-display font-bold text-xl text-white mb-3">🌐 Premium School Pages</h3>
                <p class="text-white/60 leading-relaxed">
                    Premium schools get a custom public page on KlassApp — a professional digital presence at no extra development cost.
                </p>
            </div>
            <div class="bg-white/5 rounded-2xl p-8 border border-white/10 hover:bg-white/10 transition reveal">
                <h3 class="font-display font-bold text-xl text-white mb-3">👥 Multi-Role Access</h3>
                <p class="text-white/60 leading-relaxed">
                    Admins, teachers, bursars and librarians — each with their own dashboard and role-specific permissions.
                </p>
            </div>
        </div>

        <div class="text-center mt-12 reveal">
            <a href="#pricing" class="text-brand-blue font-semibold hover:underline inline-flex items-center gap-2">
                See all features →
            </a>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════
     SECTION 7: PRICING
     ════════════════════════════════════════════════ -->
<section id="pricing" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
        <h2 class="font-display font-bold text-4xl lg:text-5xl text-gray-900 text-center mb-4 reveal">
            Simple pricing.<br class="sm:hidden" /> No surprises.
        </h2>
        <p class="text-gray-500 text-lg text-center max-w-lg mx-auto mb-14 reveal">
            Start for free. Upgrade when you need more.
        </p>

        <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto items-start">
            <!-- Starter -->
            <div class="bg-white rounded-2xl p-8 border border-gray-200 shadow-sm reveal">
                <h3 class="font-display font-bold text-xl text-gray-900 mb-2">Starter</h3>
                <p class="text-gray-500 text-sm mb-6">Free</p>
                <p class="text-5xl font-display font-bold text-gray-900 mb-6">
                    UGX 0
                </p>
                <ul class="space-y-3 mb-8 text-sm text-gray-600">
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Up to 100 students
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
                <p class="text-gray-500 text-sm mb-6">Contact us</p>
                <p class="text-5xl font-display font-bold text-gray-900 mb-6">
                    —
                </p>
                <ul class="space-y-3 mb-8 text-sm text-gray-600">
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Up to 500 students
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Everything in Starter
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
                <p class="text-gray-500 text-sm mb-6">Contact us</p>
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
                        Custom school page on KlassApp
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Dedicated onboarding support
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-brand-green shrink-0">✓</span>
                        Custom WhatsApp number
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

<!-- ════════════════════════════════════════════════
     SECTION 8: TESTIMONIALS
     ════════════════════════════════════════════════ -->
<section class="py-24 bg-surface">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
        <h2 class="font-display font-bold text-4xl lg:text-5xl text-gray-900 text-center mb-14 reveal">
            What schools are saying
        </h2>

        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 reveal">
                <p class="text-gray-600 leading-relaxed mb-6 italic">
                    "Since we started using KlassApp, parent inquiries about results dropped by 80%. They just text GRADES and get everything."
                </p>
                <div class="flex items-center gap-3">
                    <div class="avatar-circle" style="background: #1E6FD9;">JM</div>
                    <div>
                        <div class="font-semibold text-sm text-gray-900">Joyce Mwangi</div>
                        <div class="text-xs text-gray-500">Head Teacher, Kampala High School</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 reveal">
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

            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 reveal">
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

<!-- ════════════════════════════════════════════════
     SECTION 9: CTA
     ════════════════════════════════════════════════ -->
<section id="demo" class="py-24 bg-brand-blue cta-pattern relative overflow-hidden">
    <div class="max-w-3xl mx-auto px-6 text-center relative z-10">
        <h2 class="font-display font-bold text-4xl lg:text-5xl text-white mb-4 reveal">
            Ready to bring parents closer to school?
        </h2>
        <p class="text-white/80 text-lg mb-10 max-w-xl mx-auto reveal">
            Join schools across Uganda using KlassApp to keep every parent informed.
        </p>
        <form action="#" method="post" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto justify-center reveal"
              onsubmit="alert('Thanks for your interest! We will be in touch shortly.'); return false;">
            <input type="email" required placeholder="Enter your email"
                   class="flex-1 px-5 py-3.5 rounded-xl border-0 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-white/50 text-base" />
            <button type="submit"
                    class="bg-navy text-white font-semibold px-6 py-3.5 rounded-xl hover:bg-navy-light transition btn-scale whitespace-nowrap">
                Request Demo
            </button>
        </form>
        <p class="text-white/60 text-sm mt-4 reveal">or
            <a href="https://wa.me/{{ str_replace('+', '', config('services.whatsapp.business_number')) }}?text=Hello%2C%20I'd%20like%20to%20request%20a%20demo"
               target="_blank" class="text-white font-semibold hover:underline">
                chat with us on WhatsApp →
            </a>
        </p>
    </div>
</section>

<!-- ════════════════════════════════════════════════
     SECTION 10: FOOTER
     ════════════════════════════════════════════════ -->
<footer class="bg-navy py-16">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10">
            <!-- Brand -->
            <div class="lg:col-span-1">
                 <img src="{{ asset('images/klassapp-logo-primary.svg') }}"
                      alt="KlassApp"
                      class="h-14 w-auto mb-4" />
                <p class="text-white/50 text-sm leading-relaxed max-w-xs">
                    Building the bridge between African schools and parents — one WhatsApp message at a time.
                </p>
            </div>

            <!-- Product -->
            <div>
                <h4 class="text-white font-semibold text-sm mb-4 uppercase tracking-wider">Product</h4>
                <ul class="space-y-2.5">
                    <li><a href="#features" class="text-white/50 hover:text-white text-sm transition">Features</a></li>
                    <li><a href="#pricing" class="text-white/50 hover:text-white text-sm transition">Pricing</a></li>
                    <li><a href="#schools" class="text-white/50 hover:text-white text-sm transition">For Schools</a></li>
                    <li><a href="#demo" class="text-white/50 hover:text-white text-sm transition">Request Demo</a></li>
                </ul>
            </div>

            <!-- Company -->
            <div>
                <h4 class="text-white font-semibold text-sm mb-4 uppercase tracking-wider">Company</h4>
                <ul class="space-y-2.5">
                    <li><a href="#" class="text-white/50 hover:text-white text-sm transition">About</a></li>
                    <li><a href="#" class="text-white/50 hover:text-white text-sm transition">Blog</a></li>
                    <li><a href="#" class="text-white/50 hover:text-white text-sm transition">Contact</a></li>
                    <li><a href="#" class="text-white/50 hover:text-white text-sm transition">Privacy Policy</a></li>
                </ul>
            </div>

            <!-- Support / Social -->
            <div>
                <h4 class="text-white font-semibold text-sm mb-4 uppercase tracking-wider">Connect</h4>
                <div class="flex gap-3">
                    <a href="#" class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center hover:bg-white/20 transition" aria-label="Twitter/X">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center hover:bg-white/20 transition" aria-label="LinkedIn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
<a href="https://wa.me/{{ str_replace('+', '', config('services.whatsapp.business_number')) }}?text=Hello%2C%20I'd%20like%20to%20learn%20about%20KlassApp" target="_blank" class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center hover:bg-white/20 transition" aria-label="WhatsApp">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10 mt-12 pt-8 text-center text-white/40 text-sm">
            &copy; {{ date('Y') }} KlassApp. Built in Uganda 🇺🇬
        </div>
    </div>
</footer>

<!-- ════════════════════════════════════════════════
     JAVASCRIPT
     ════════════════════════════════════════════════ -->
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

    // Close mobile menu on link click
    mobileMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.remove('open');
        });
    });

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
