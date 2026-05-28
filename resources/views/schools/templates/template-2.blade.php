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
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { display: ['Space Grotesk', 'sans-serif'], body: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: { primary: '{{ $primary }}', secondary: '{{ $secondary }}', accent: '{{ $accent }}' },
                },
            },
        };
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', system-ui, sans-serif; }
        .clip-diagonal { clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%); }
        .reveal { opacity: 0; transform: translateY(40px); transition: opacity 0.7s ease, transform 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
        .reveal.visible { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body>
    {{-- Nav --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-8 py-4 flex items-center justify-between">
            <span class="font-display font-bold text-xl" style="color: {{ $secondary }};">{{ $schoolName }}</span>
            <div class="flex gap-8 text-sm font-medium">
                <a href="#about" class="text-gray-600 hover:text-gray-900">About</a>
                <a href="#features" class="text-gray-600 hover:text-gray-900">Features</a>
                <a href="#contact" class="text-gray-600 hover:text-gray-900">Contact</a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="min-h-screen clip-diagonal flex items-center relative overflow-hidden pt-16" style="background: linear-gradient(160deg, {{ $secondary }} 0%, {{ $primary }} 100%);">
        <div class="max-w-7xl mx-auto px-8 py-24 relative z-10 w-full">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="reveal">
                    <p class="text-white/60 text-sm font-semibold uppercase tracking-[0.2em] mb-4">{{ $board ?: 'Excellence in Education' }}</p>
                    <h1 class="font-display font-bold text-5xl lg:text-7xl text-white leading-[1.1] mb-6">{{ $schoolName }}</h1>
                    @if($moto)
                        <p class="text-white/70 text-xl lg:text-2xl font-light leading-relaxed">{{ $moto }}</p>
                    @endif
                    <div class="mt-10 flex flex-wrap gap-4">
                        <a href="#about" class="inline-block bg-white font-semibold px-8 py-3.5 rounded-full hover:bg-gray-100 transition" style="color: {{ $primary }};">Explore</a>
                        <a href="#contact" class="inline-block border-2 border-white/30 text-white font-semibold px-8 py-3.5 rounded-full hover:bg-white/10 transition">Contact</a>
                        @if($whatsappLink)
                        <a href="{{ $whatsappLink }}" target="_blank"
                           class="inline-flex items-center gap-2 font-semibold px-6 py-3.5 rounded-full transition btn-scale"
                           style="background: #25D366; color: white;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
                            WhatsApp
                        </a>
                        @endif
                    </div>
                </div>
                @if($page->hero_image)
                <div class="reveal">
                    <img src="{{ asset('storage/'.$page->hero_image) }}" alt="{{ $schoolName }}" class="w-full rounded-2xl shadow-2xl" />
                </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Stats Bar --}}
    @php $stats = $content['stats'] ?? []; @endphp
    @if(count($stats))
    <section class="py-12 bg-white relative z-10 -mt-20">
        <div class="max-w-5xl mx-auto px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 bg-white rounded-2xl shadow-xl p-8 reveal">
                @foreach($stats as $stat)
                <div class="text-center">
                    <div class="font-display font-bold text-3xl lg:text-4xl" style="color: {{ $primary }};">{{ $stat['value'] ?? '' }}</div>
                    <div class="text-gray-500 text-sm mt-1">{{ $stat['label'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- About --}}
    <section id="about" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="reveal">
                    <span class="inline-block text-sm font-semibold uppercase tracking-[0.2em] mb-3" style="color: {{ $primary }};">About</span>
                    <h2 class="font-display font-bold text-4xl lg:text-5xl text-gray-900 mb-6">{{ $content['about_heading'] ?? 'Our Story' }}</h2>
                    <div class="text-gray-600 leading-relaxed text-lg">{{ $aboutUs ?: ($content['about_body'] ?? '') }}</div>
                </div>
                <div class="reveal">
                    <div class="rounded-2xl overflow-hidden shadow-xl" style="background: linear-gradient(135deg, {{ $primary }}22, {{ $secondary }}11); padding: 2px;">
                        <div class="bg-white rounded-2xl p-8">
                            @if($established)
                            <div class="flex items-center gap-4 py-3 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-500 w-28">Established</span>
                                <span class="font-medium">{{ date('Y', strtotime($established)) }}</span>
                            </div>
                            @endif
                            @if($board)
                            <div class="flex items-center gap-4 py-3 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-500 w-28">Board</span>
                                <span class="font-medium">{{ $board }}</span>
                            </div>
                            @endif
                            @if($affiliatedBy)
                            <div class="flex items-center gap-4 py-3 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-500 w-28">Affiliated by</span>
                                <span class="font-medium">{{ $affiliatedBy }}</span>
                            </div>
                            @endif
                            <div class="flex items-center gap-4 py-3">
                                <span class="text-sm font-medium text-gray-500 w-28">Location</span>
                                <span class="font-medium">{{ $address }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Contact --}}
    <section id="contact" class="py-24 relative overflow-hidden" style="background: {{ $secondary }};">
        <div class="max-w-4xl mx-auto px-8 text-center relative z-10">
            <h2 class="font-display font-bold text-4xl lg:text-5xl text-white mb-4 reveal">Get in Touch</h2>
            <p class="text-white/60 text-lg mb-10 reveal">{{ $moto ?: "We'd love to hear from you." }}</p>
            <div class="grid sm:grid-cols-3 gap-8 text-left reveal">
                <div class="bg-white/10 rounded-xl p-6 text-white">
                    <div class="font-semibold mb-1">📍 Address</div>
                    <p class="text-white/70 text-sm">{{ $address }}</p>
                </div>
                <div class="bg-white/10 rounded-xl p-6 text-white">
                    <div class="font-semibold mb-1">📞 Phone</div>
                    @if($whatsappLink && $phone)
                        <p class="text-white/70 text-sm"><a href="{{ $whatsappLink }}" target="_blank" class="hover:underline inline-flex items-center gap-1"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/></svg>{{ $phone }}</a></p>
                    @else
                        <p class="text-white/70 text-sm">{{ $phone ?: $landline }}</p>
                    @endif
                </div>
                <div class="bg-white/10 rounded-xl p-6 text-white">
                    <div class="font-semibold mb-1">✉️ Email</div>
                    <p class="text-white/70 text-sm break-all">{{ $email }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-10 text-center text-sm text-gray-500 bg-white">
        &copy; {{ date('Y') }} {{ $schoolName }}. Powered by <a href="/" class="font-semibold hover:underline" style="color: {{ $primary }};">KlassApp</a>.
    </footer>

    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); } });
        }, { threshold: 0.1 });
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
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
