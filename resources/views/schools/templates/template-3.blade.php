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
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@300;400;500&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { display: ['DM Serif Display', 'serif'], body: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: { primary: '{{ $primary }}', secondary: '{{ $secondary }}', accent: '{{ $accent }}' },
                },
            },
        };
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', system-ui, sans-serif; color: #374151; font-weight: 300; }
        .reveal { opacity: 0; transform: translateY(24px); transition: opacity 0.8s ease, transform 0.8s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }
        .divider { height: 1px; background: linear-gradient(to right, transparent, #E5E7EB, transparent); }
    </style>
</head>
<body class="bg-white">
    {{-- Nav --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-sm">
        <div class="max-w-4xl mx-auto px-6 py-5 flex items-center justify-between">
            <span class="font-display text-xl italic" style="color: {{ $secondary }};">{{ $schoolName }}</span>
            <div class="flex gap-8 text-sm text-gray-400">
                <a href="#about" class="hover:text-gray-700 transition">About</a>
                <a href="#contact" class="hover:text-gray-700 transition">Contact</a>
            </div>
        </div>
    </section>

    {{-- Hero --}}
    <section class="min-h-[80vh] flex items-center justify-center pt-20">
        <div class="max-w-3xl mx-auto px-6 text-center reveal">
            @if($logo)
                <img src="{{ asset('storage/'.$logo) }}" alt="{{ $schoolName }}" class="h-16 w-auto mx-auto mb-8 opacity-90" />
            @endif
            <h1 class="font-display text-5xl lg:text-7xl leading-[1.15] mb-6" style="color: {{ $secondary }};">{{ $schoolName }}</h1>
            @if($moto)
                <p class="text-gray-400 text-xl lg:text-2xl font-light italic leading-relaxed">{{ $moto }}</p>
            @endif
            <div class="mt-12 divider"></div>
            <div class="mt-8 flex justify-center gap-6 text-sm text-gray-400">
                <a href="#about" class="hover:text-gray-700 transition">About →</a>
                <a href="#contact" class="hover:text-gray-700 transition">Contact →</a>
                @if($whatsappLink)
                <a href="{{ $whatsappLink }}" target="_blank"
                   class="inline-flex items-center gap-1.5 hover:text-gray-700 transition">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
                    WhatsApp →
                </a>
                @endif
            </div>
        </div>
    </section>

    {{-- About --}}
    <section id="about" class="py-28">
        <div class="max-w-3xl mx-auto px-6 reveal">
            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-300">About</span>
            <h2 class="font-display text-3xl lg:text-4xl mt-3 mb-8" style="color: {{ $secondary }};">{{ $content['about_heading'] ?? 'Our Story' }}</h2>
            <div class="text-gray-400 leading-relaxed text-lg space-y-4">{{ $aboutUs ?: ($content['about_body'] ?? '') }}</div>

            @if($page->hero_image)
            <div class="mt-12">
                <img src="{{ asset('storage/'.$page->hero_image) }}" alt="{{ $schoolName }}" class="w-full rounded-lg" />
            </div>
            @endif
        </div>
    </section>

    {{-- Stats --}}
    @php $stats = $content['stats'] ?? []; @endphp
    @if(count($stats))
    <section class="py-20 bg-gray-50/50">
        <div class="max-w-4xl mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-12">
                @foreach($stats as $stat)
                <div class="text-center reveal">
                    <div class="font-display text-4xl" style="color: {{ $secondary }};">{{ $stat['value'] ?? '' }}</div>
                    <div class="text-gray-400 text-sm mt-1.5">{{ $stat['label'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Contact --}}
    <section id="contact" class="py-28">
        <div class="max-w-3xl mx-auto px-6 text-center reveal">
            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-300">Contact</span>
            <h2 class="font-display text-3xl lg:text-4xl mt-3 mb-10" style="color: {{ $secondary }};">Get in Touch</h2>
            <div class="grid sm:grid-cols-3 gap-8 text-left">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-300 mb-2">Address</div>
                    <p class="text-gray-500 text-sm">{{ $address }}</p>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-300 mb-2">Phone</div>
                    @if($whatsappLink && $phone)
                        <p class="text-gray-500 text-sm"><a href="{{ $whatsappLink }}" target="_blank" class="hover:text-gray-700 inline-flex items-center gap-1"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/></svg>{{ $phone }}</a></p>
                    @else
                        <p class="text-gray-500 text-sm">{{ $phone ?: $landline }}</p>
                    @endif
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-300 mb-2">Email</div>
                    <p class="text-gray-500 text-sm break-all">{{ $email }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-10 text-center border-t border-gray-100">
        <p class="text-gray-300 text-sm">&copy; {{ date('Y') }} {{ $schoolName }}. Powered by <a href="/" class="hover:text-gray-500 transition underline underline-offset-2">KlassApp</a>.</p>
    </footer>

    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); } });
        }, { threshold: 0.15 });
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
