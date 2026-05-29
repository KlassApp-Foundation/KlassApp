@include('schools.templates._shared')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $page->seo_title ?: $schoolName }} — KlassApp</title>
    <meta name="description" content="{{ $page->seo_description ?: $moto }}" />
    <meta property="og:title" content="{{ $page->seo_title ?: $schoolName }}" />
    <meta property="og:description" content="{{ $page->seo_description ?: $moto }}" />
    @if($page->hero_image)
        <meta property="og:image" content="{{ asset('storage/'.$page->hero_image) }}" />
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { display: ['Plus Jakarta Sans', 'sans-serif'], body: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'] },
                    colors: { primary: '{{ $primary }}', secondary: '{{ $secondary }}', accent: '{{ $accent }}' },
                },
            },
        };
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; overflow-x: hidden; }
        .parallax { background-attachment: fixed; background-position: center; background-size: cover; }
        .reveal { opacity: 0; transform: translateY(40px); transition: opacity 0.7s ease, transform 0.7s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }
        .reveal-left { opacity: 0; transform: translateX(-40px); transition: opacity 0.7s ease, transform 0.7s ease; }
        .reveal-left.visible { opacity: 1; transform: translateX(0); }
        .reveal-right { opacity: 0; transform: translateX(40px); transition: opacity 0.7s ease, transform 0.7s ease; }
        .reveal-right.visible { opacity: 1; transform: translateX(0); }
        .glass { background: rgba(255,255,255,0.1); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.15); }
        @keyframes counter { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .counter-anim { animation: counter 0.6s ease forwards; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .fade-in { animation: fadeIn 1s ease forwards; }
    </style>
</head>
<body>
    {{-- Nav --}}
    <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" id="mainNav">
        <div class="max-w-7xl mx-auto px-8 py-4 flex items-center justify-between">
            <span class="font-display font-bold text-xl text-white drop-shadow-sm">{{ $schoolName }}</span>
            <div class="flex gap-8 text-sm font-medium text-white/80">
                <a href="#about" class="hover:text-white transition">About</a>
                <a href="#stats" class="hover:text-white transition">Achievements</a>
                <a href="#gallery" class="hover:text-white transition">Gallery</a>
                <a href="#contact" class="hover:text-white transition">Contact</a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="min-h-screen flex items-center relative overflow-hidden" style="background: linear-gradient(160deg, {{ $secondary }}, {{ $primary }} 60%, {{ $secondary }});">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 25% 25%, white 0%, transparent 50%), radial-gradient(circle at 75% 75%, white 0%, transparent 50%);"></div>
        <div class="max-w-7xl mx-auto px-8 relative z-10 w-full">
            <div class="max-w-4xl reveal">
                <span class="inline-block text-sm font-semibold uppercase tracking-[0.2em] text-white/50 mb-5">Welcome to</span>
                <h1 class="font-display font-extrabold text-6xl lg:text-8xl text-white leading-[1.05] mb-6">{{ $schoolName }}</h1>
                @if($moto)
                    <p class="text-white/60 text-xl lg:text-2xl max-w-2xl leading-relaxed">{{ $moto }}</p>
                @endif
                <div class="mt-10 flex flex-wrap gap-4">
                    <a href="#about" class="inline-block bg-white font-bold px-8 py-4 rounded-xl hover:bg-gray-100 transition shadow-2xl" style="color: {{ $primary }};">Discover More</a>
                    <a href="#contact" class="inline-block glass text-white font-semibold px-8 py-4 rounded-xl hover:bg-white/20 transition">Get in Touch</a>
                    @if($whatsappLink)
                    <a href="{{ $whatsappLink }}" target="_blank"
                       class="inline-flex items-center gap-2 font-bold px-6 py-4 rounded-xl transition btn-scale"
                       style="background: #25D366; color: white;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
                        WhatsApp
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @if($page->hero_image)
        <div class="absolute bottom-0 right-0 w-1/3 h-full opacity-20" style="background-image: url('{{ asset('storage/'.$page->hero_image) }}'); background-size: cover; background-position: center; mask-image: linear-gradient(to left, black, transparent); -webkit-mask-image: linear-gradient(to left, black, transparent);"></div>
        @endif
    </section>

    {{-- About --}}
    <section id="about" class="py-28 bg-white">
        <div class="max-w-7xl mx-auto px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="reveal-left">
                    <span class="text-sm font-semibold uppercase tracking-[0.2em]" style="color: {{ $primary }};">About</span>
                    <h2 class="font-display font-bold text-4xl lg:text-5xl text-gray-900 mt-3 mb-6">{{ $content['about_heading'] ?? 'Our Story' }}</h2>
                    <div class="text-gray-600 leading-relaxed text-lg">{{ $aboutUs ?: ($content['about_body'] ?? '') }}</div>
                    @if($established || $board)
                    <div class="mt-8 flex gap-8">
                        @if($established)
                        <div>
                            <div class="text-sm font-semibold" style="color: {{ $primary }};">Established</div>
                            <div class="text-gray-900 font-bold text-xl">{{ date('Y', strtotime($established)) }}</div>
                        </div>
                        @endif
                        @if($board)
                        <div>
                            <div class="text-sm font-semibold" style="color: {{ $primary }};">Board</div>
                            <div class="text-gray-900 font-bold text-xl">{{ $board }}</div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                @if($page->hero_image)
                <div class="reveal-right">
                    <div class="relative">
                        <div class="absolute -top-4 -left-4 w-full h-full rounded-2xl" style="background: {{ $primary }}; opacity: 0.1;"></div>
                        <img src="{{ asset('storage/'.$page->hero_image) }}" alt="{{ $schoolName }}" class="w-full rounded-2xl shadow-2xl relative z-10" />
                    </div>
                </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Stats/Achievements --}}
    @php $stats = $content['stats'] ?? []; @endphp
    @if(count($stats))
    <section id="stats" class="py-24 relative overflow-hidden" style="background: {{ $secondary }};">
        <div class="max-w-6xl mx-auto px-8 relative z-10">
            <div class="text-center mb-14 reveal">
                <h2 class="font-display font-bold text-4xl text-white">Our Achievements</h2>
                <p class="text-white/50 mt-2">By the numbers</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                @foreach($stats as $stat)
                <div class="text-center reveal">
                    <div class="font-display font-extrabold text-4xl lg:text-5xl text-white mb-2">{{ $stat['value'] ?? '' }}</div>
                    <div class="text-white/50 text-sm">{{ $stat['label'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Features/Highlights --}}
    @php $features = $content['features'] ?? []; @endphp
    @if(count($features))
    <section id="features" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-8">
            <div class="text-center mb-14 reveal">
                <h2 class="font-display font-bold text-4xl text-gray-900">Why Choose Us</h2>
                <p class="text-gray-500 mt-2">What makes {{ $schoolName }} special</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                @foreach($features as $feature)
                <div class="p-8 rounded-2xl border border-gray-100 hover:shadow-lg transition reveal" style="border-color: {{ $primary }}22;">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl mb-4" style="background: {{ $primary }}11; color: {{ $primary }};">{{ $feature['icon'] ?? '★' }}</div>
                    <h3 class="font-bold text-xl text-gray-900 mb-3">{{ $feature['title'] ?? '' }}</h3>
                    <p class="text-gray-500 leading-relaxed">{{ $feature['body'] ?? '' }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Gallery --}}
    @php $gallery = $content['gallery'] ?? []; @endphp
    @if(count($gallery))
    <section id="gallery" class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-8">
            <div class="text-center mb-14 reveal">
                <h2 class="font-display font-bold text-4xl text-gray-900">Our Gallery</h2>
                <p class="text-gray-500 mt-2">Life at {{ $schoolName }}</p>
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                @foreach($gallery as $image)
                <div class="aspect-[4/3] rounded-xl overflow-hidden reveal">
                    <img src="{{ $image['url'] ?? '' }}" alt="{{ $image['alt'] ?? '' }}" class="w-full h-full object-cover hover:scale-105 transition duration-500" />
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Testimonials --}}
    @php $testimonials = $content['testimonials'] ?? []; @endphp
    @if(count($testimonials))
    <section id="testimonials" class="py-24" style="background: linear-gradient(160deg, {{ $secondary }}, {{ $primary }});">
        <div class="max-w-5xl mx-auto px-8">
            <div class="text-center mb-14 reveal">
                <h2 class="font-display font-bold text-4xl text-white">What People Say</h2>
                <p class="text-white/50 mt-2">Testimonials from our community</p>
            </div>
            <div class="grid md:grid-cols-2 gap-6">
                @foreach($testimonials as $t)
                <div class="glass rounded-2xl p-8 reveal">
                    <p class="text-white/80 leading-relaxed mb-6 italic">"{{ $t['quote'] ?? '' }}"</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white font-bold text-sm">{{ substr($t['author'] ?? '?', 0, 1) }}</div>
                        <div>
                            <div class="text-white font-semibold text-sm">{{ $t['author'] ?? '' }}</div>
                            <div class="text-white/50 text-xs">{{ $t['role'] ?? '' }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Contact --}}
    <section id="contact" class="py-24 bg-white">
        <div class="max-w-4xl mx-auto px-8 text-center">
            <span class="text-sm font-semibold uppercase tracking-[0.2em]" style="color: {{ $primary }};">Contact</span>
            <h2 class="font-display font-bold text-4xl lg:text-5xl text-gray-900 mt-3 mb-10 reveal">Get in Touch</h2>
            <div class="grid sm:grid-cols-3 gap-8 reveal">
                <div class="p-6 rounded-xl" style="background: {{ $primary }}08;">
                    <div class="font-bold text-gray-900 mb-1">📍 Address</div>
                    <p class="text-gray-500 text-sm">{{ $address }}</p>
                </div>
                <div class="p-6 rounded-xl" style="background: {{ $primary }}08;">
                    <div class="font-bold text-gray-900 mb-1">📞 Phone</div>
                    @if($whatsappLink && $phone)
                    <p class="text-gray-500 text-sm"><a href="{{ $whatsappLink }}" target="_blank" class="hover:underline inline-flex items-center gap-1"><svg width="14" height="14" viewBox="0 0 24 24" fill="#25D366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/></svg>{{ $phone }}</a></p>
                    @else
                    <p class="text-gray-500 text-sm">{{ $phone ?: $landline }}</p>
                    @endif
                </div>
                <div class="p-6 rounded-xl" style="background: {{ $primary }}08;">
                    <div class="font-bold text-gray-900 mb-1">✉️ Email</div>
                    <p class="text-gray-500 text-sm break-all">{{ $email }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-10 text-center border-t border-gray-100" style="background: {{ $secondary }};">
        <p class="text-white/40 text-sm">&copy; {{ date('Y') }} {{ $schoolName }}. Powered by <a href="/" class="text-white/60 hover:text-white transition font-semibold">KlassApp</a>.</p>
    </footer>

    <script>
        // Navbar background on scroll
        const nav = document.getElementById('mainNav');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                nav.style.background = '{{ $secondary }}';
                nav.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
            } else {
                nav.style.background = 'transparent';
                nav.style.boxShadow = 'none';
            }
        });

        // Scroll reveal
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); } });
        }, { threshold: 0.1 });
        document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => observer.observe(el));
    </script>

    @if($whatsappLink)
    <a href="{{ $whatsappLink }}" target="_blank"
       class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center"
       style="background: #25D366;"
       aria-label="Chat on WhatsApp">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
    </a>
    @endif
</body>
</html>
