@include('schools.templates._shared')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $page->seo_title ?: $schoolName }} — KlassApp</title>
    <meta name="description" content="{{ $page->seo_description ?: $moto }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { display: ['Nunito', 'sans-serif'], body: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: { primary: '{{ $primary }}', secondary: '{{ $secondary }}', accent: '{{ $accent }}' },
                },
            },
        };
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', system-ui, sans-serif; overflow-x: hidden; }
        .shape-circle { position: absolute; border-radius: 50%; opacity: 0.08; pointer-events: none; }
        .shape-dot { position: absolute; width: 8px; height: 8px; border-radius: 50%; opacity: 0.15; pointer-events: none; }
        .reveal { opacity: 0; transform: translateY(30px); transition: opacity 0.6s ease, transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .reveal.visible { opacity: 1; transform: translateY(0); }
        .bounce-in { opacity: 0; transform: scale(0.8); transition: opacity 0.5s ease, transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .bounce-in.visible { opacity: 1; transform: scale(1); }
    </style>
</head>
<body>
    {{-- Floating shapes --}}
    @php
        $shapeColor = $primary;
        $pastelBg = $primary . '15';
    @endphp

    {{-- Nav --}}
    <nav class="fixed top-0 left-0 right-0 z-50" style="background: {{ $pastelBg }}; backdrop-filter: blur(12px);">
        <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between">
            <span class="font-display font-extrabold text-lg" style="color: {{ $secondary }};">{{ $schoolName }}</span>
            <div class="flex items-center gap-4 text-sm font-semibold">
                <a href="#about" class="px-4 py-2 rounded-full hover:bg-white/60 transition" style="color: {{ $secondary }};">About</a>
                <a href="#features" class="px-4 py-2 rounded-full hover:bg-white/60 transition" style="color: {{ $secondary }};">Features</a>
                <a href="#contact" class="px-5 py-2 rounded-full text-white font-bold transition hover:brightness-110" style="background: {{ $primary }};">Contact</a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="min-h-screen flex items-center relative overflow-hidden pt-16" style="background: linear-gradient(160deg, {{ $pastelBg }}, white 60%);">
        <div class="shape-circle" style="width: 400px; height: 400px; background: {{ $primary }}; top: -100px; right: -100px;"></div>
        <div class="shape-circle" style="width: 200px; height: 200px; background: {{ $accent }}; bottom: 50px; left: -50px;"></div>
        <div class="shape-dot" style="background: {{ $primary }}; top: 30%; left: 15%;"></div>
        <div class="shape-dot" style="background: {{ $accent }}; top: 60%; right: 20%;"></div>
        <div class="shape-dot" style="background: {{ $primary }}; bottom: 25%; left: 40%;"></div>

        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="reveal">
                    <span class="inline-block text-sm font-extrabold uppercase tracking-wider px-4 py-1.5 rounded-full text-white mb-4" style="background: {{ $primary }};">{{ $board ?: 'Now Enrolling' }}</span>
                    <h1 class="font-display font-black text-5xl lg:text-6xl leading-[1.1] mb-4" style="color: {{ $secondary }};">{{ $schoolName }}</h1>
                    @if($moto)
                        <p class="text-gray-500 text-lg lg:text-xl leading-relaxed">{{ $moto }}</p>
                    @endif
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="#about" class="inline-block font-bold px-8 py-3.5 rounded-full text-white transition hover:brightness-110" style="background: {{ $primary }};">Explore More</a>
                        <a href="#contact" class="inline-block font-semibold px-8 py-3.5 rounded-full border-2 transition" style="border-color: {{ $primary }}; color: {{ $primary }};">Contact Us</a>
                        @if($whatsappLink)
                        <a href="{{ $whatsappLink }}" target="_blank"
                           class="inline-flex items-center gap-2 font-bold px-6 py-3.5 rounded-full text-white transition hover:brightness-110"
                           style="background: #25D366;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
                            WhatsApp
                        </a>
                        @endif
                    </div>
                </div>
                <div class="reveal">
                    @if($page->hero_image)
                        <img src="{{ asset('storage/'.$page->hero_image) }}" alt="{{ $schoolName }}" class="w-full rounded-3xl shadow-2xl" />
                    @else
                        <div class="aspect-[4/3] rounded-3xl flex items-center justify-center" style="background: {{ $pastelBg }};">
                            <span class="font-display font-black text-7xl opacity-20" style="color: {{ $primary }};">{{ substr($schoolName, 0, 2) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    @php $stats = $content['stats'] ?? []; @endphp
    @if(count($stats))
    <section class="py-14 relative" style="background: {{ $secondary }};">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                @foreach($stats as $stat)
                <div class="text-center bounce-in">
                    <div class="font-display font-black text-3xl lg:text-4xl" style="color: {{ $accent }};">{{ $stat['value'] ?? '' }}</div>
                    <div class="text-white/60 text-sm mt-1">{{ $stat['label'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- About --}}
    <section id="about" class="py-24">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="reveal">
                    <h2 class="font-display font-black text-3xl lg:text-4xl mb-6" style="color: {{ $secondary }};">{{ $content['about_heading'] ?? 'Our Story' }}</h2>
                    <div class="text-gray-500 leading-relaxed text-lg">{{ $aboutUs ?: ($content['about_body'] ?? '') }}</div>
                </div>
                <div class="reveal grid grid-cols-2 gap-4">
                    <div class="rounded-2xl p-6" style="background: {{ $pastelBg }};">
                        <div class="font-display font-extrabold text-sm uppercase tracking-wider mb-2" style="color: {{ $primary }};">Est.</div>
                        <div class="font-bold text-lg" style="color: {{ $secondary }};">{{ $established ? date('Y', strtotime($established)) : '—' }}</div>
                    </div>
                    <div class="rounded-2xl p-6" style="background: {{ $pastelBg }};">
                        <div class="font-display font-extrabold text-sm uppercase tracking-wider mb-2" style="color: {{ $primary }};">Board</div>
                        <div class="font-bold text-lg" style="color: {{ $secondary }};">{{ $board ?: '—' }}</div>
                    </div>
                    <div class="rounded-2xl p-6" style="background: {{ $pastelBg }};">
                        <div class="font-display font-extrabold text-sm uppercase tracking-wider mb-2" style="color: {{ $primary }};">Students</div>
                        <div class="font-bold text-lg" style="color: {{ $secondary }};">{{ $school->student_size ?: '—' }}</div>
                    </div>
                    <div class="rounded-2xl p-6" style="background: {{ $pastelBg }};">
                        <div class="font-display font-extrabold text-sm uppercase tracking-wider mb-2" style="color: {{ $primary }};">Location</div>
                        <div class="font-bold text-lg" style="color: {{ $secondary }};">{{ $school->city?->name ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Contact --}}
    <section id="contact" class="py-24 relative overflow-hidden" style="background: {{ $pastelBg }};">
        <div class="shape-circle" style="width: 300px; height: 300px; background: {{ $primary }}; bottom: -150px; right: -100px;"></div>
        <div class="max-w-6xl mx-auto px-6 relative z-10">
            <div class="text-center reveal mb-14">
                <h2 class="font-display font-black text-3xl lg:text-4xl mb-3" style="color: {{ $secondary }};">Get in Touch</h2>
                <p class="text-gray-500">We'd love to hear from you</p>
            </div>
            <div class="grid sm:grid-cols-3 gap-6 reveal">
                <div class="bg-white rounded-2xl p-6 text-center shadow-sm hover:shadow-md transition">
                    <div class="text-3xl mb-2">📍</div>
                    <div class="font-bold text-sm mb-1" style="color: {{ $secondary }};">Address</div>
                    <p class="text-gray-500 text-sm">{{ $address }}</p>
                </div>
                <div class="bg-white rounded-2xl p-6 text-center shadow-sm hover:shadow-md transition">
                    <div class="text-3xl mb-2">📞</div>
                    <div class="font-bold text-sm mb-1" style="color: {{ $secondary }};">Phone</div>
                    @if($whatsappLink && $phone)
                    <p class="text-gray-500 text-sm"><a href="{{ $whatsappLink }}" target="_blank" class="hover:underline inline-flex items-center gap-1"><svg width="14" height="14" viewBox="0 0 24 24" fill="#25D366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/></svg>{{ $phone }}</a></p>
                    @else
                    <p class="text-gray-500 text-sm">{{ $phone ?: $landline }}</p>
                    @endif
                </div>
                <div class="bg-white rounded-2xl p-6 text-center shadow-sm hover:shadow-md transition">
                    <div class="text-3xl mb-2">✉️</div>
                    <div class="font-bold text-sm mb-1" style="color: {{ $secondary }};">Email</div>
                    <p class="text-gray-500 text-sm break-all">{{ $email }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-8 text-center text-sm" style="color: {{ $secondary }};">
        &copy; {{ date('Y') }} {{ $schoolName }}. Powered by <a href="/" class="font-bold hover:underline" style="color: {{ $primary }};">KlassApp</a>.
    </footer>

    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); } });
        }, { threshold: 0.1 });
        document.querySelectorAll('.reveal, .bounce-in').forEach(el => observer.observe(el));
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
