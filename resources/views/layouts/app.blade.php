{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('layouts.partials.favicon')
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'KlassApp') }}</title>
        <!-- Styles -->

        @vite([
            'resources/assets/js/app.js',
            'resources/assets/sass/app.scss',
            'resources/css/tailwind.css',
        ])
        <link href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}" rel="stylesheet">
        <link href="{{ asset('css/dashboard-refresh.css') }}?v={{ filemtime(public_path('css/dashboard-refresh.css')) }}" rel="stylesheet">
        <link href="{{ asset('vendor/toshi-ui/toshi-ui.css') }}?v={{ filemtime(public_path('vendor/toshi-ui/toshi-ui.css')) }}" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700&display=swap" rel="stylesheet">
        {{-- Font Awesome removed — all icons migrated to inline SVGs (Jul 22, 2026) --}}
         <script>
        window.User = {!! json_encode(optional(auth()->user())->only('id')) !!}
    </script>

    {{-- Alpine is bundled with Livewire v3 — remove duplicate CDN load --}}

    <!-- new -->
    <script>
       window.AppConfig = {
          gtimetable_enabled: @json(config('gtimetable.enabled')),
          gquiz_enabled: @json(config('gquiz.enabled')),
          gexam_enabled: @json(config('gexam.enabled')),
          ginventory_enabled: @json(config('ginventory.enabled')),
          gchat_enabled: @json(config('gchat.enabled')),
          gtransport_enabled: @json(config('gtransport.enabled')),
          gcertificate_enabled: @json(config('gcertificate.enabled')),
          gtimetable_enabled: @json(config('gtimetable.enabled')),
          gvideoroom_enabled: @json(config('gvideoroom.enabled')),
          galumni_enabled: @json(config('galumni.enabled')),
          gfee_enabled: @json(config('gfee.enabled'))

       };
    </script>
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <!-- end -->

 <livewire:styles>
    </head>
    <body class="font-primary antialiased min-h-screen overflow-x-hidden">
        <div id="app">
            @yield('base-navigation')
            <main class="flex w-full min-h-screen relative">
                <div class="sidebar self-stretch">
                    @yield('base-sidebar')
                </div>
                <div class="bg-gray-200 dashboard-content-area flex-grow w-full px-4 md:w-auto" style="width: calc(100vw - 195px);">
                    @yield('base-content')
                </div>
            </main>
            @yield('base-footer')
        </div>

        {{-- Toshi lives OUTSIDE #app so Vue never touches Alpine markup --}}
        @auth
            @if(in_array(auth()->user()->usergroup_id, [1, 3, 4, 5, 11, 8, 10, 6]))
                @livewire('agent-toshi')
                <div id="toshi-toggle-wrapper" class="toshi-toggle-wrapper">
                    <div id="toshi-toggle" class="toshi-toggle" title="Open Toshi" onclick="document.body.classList.toggle('toshi-collapsed');var t=document.getElementById('toshi-toggle');t.textContent=document.body.classList.contains('toshi-collapsed')?'◀':'▶'">▶</div>
                </div>
                <script>
                document.addEventListener('click', function(e) {
                    if (document.body.classList.contains('toshi-collapsed') && e.target.closest('.toshi-pill')) {
                        e.preventDefault();
                        document.body.classList.remove('toshi-collapsed');
                        document.getElementById('toshi-toggle').textContent = '▶';
                    }
                });
                </script>
            @endif
        @endauth

        @yield('outside-app')

        <!-- Scripts -->

        <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
        <script src="{{ asset('js/custom.js') }}" defer></script>
        @stack('scripts')

        <livewire:scripts>

        <script>
   window.addEventListener('alert', event => {
        toastr[event.detail.type](event.detail.message,
            event.detail.title ?? ''), toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": 'bottom-right',

            }
    });

   window.addEventListener('registeralert', event => {
        toastr[event.detail.type](event.detail.message,
            event.detail.title ?? ''), toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": 'bottom-right',

            }
    });
</script>

    </body>
    <style>
    .page-loading .loading {
  margin: auto;
  height: 100px;
  width: 100px;
  animation: spinner 1.5s linear infinite !important;
}
.page-loading .loading > div {
  height: inherit;
  width: inherit;
  position: absolute;
  animation-name: opacity;
  animation-duration: 1.1s;
  animation-timing-function: ease;
  animation-iteration-count: infinite;
  opacity: 0;
}
.page-loading .loading > div > div {
  height: 11px;
  width: 11px;
  border-radius: 50%;
  background: #9b2c2c;
  position: absolute;
  top: 0%;
  right: 50%;
  transform: translate(50%, 0%);
}
.page-loading .loading > div:nth-child(2) {
  transform: rotate(30deg);
  animation-delay: 0.1s;
}
.page-loading .loading > div:nth-child(3) {
  transform: rotate(60deg);
  animation-delay: 0.2s;
}
.page-loading .loading > div:nth-child(4) {
  transform: rotate(90deg);
  animation-delay: 0.3s;
}
.page-loading .loading > div:nth-child(5) {
  transform: rotate(120deg);
  animation-delay: 0.4s;
}
.page-loading .loading > div:nth-child(6) {
  transform: rotate(150deg);
  animation-delay: 0.5s;
}
.page-loading .loading > div:nth-child(7) {
  transform: rotate(180deg);
  animation-delay: 0.6s;
}
.page-loading .loading > div:nth-child(8) {
  transform: rotate(210deg);
  animation-delay: 0.7s;
}
.page-loading .loading > div:nth-child(9) {
  transform: rotate(240deg);
  animation-delay: 0.8s;
}
.page-loading .loading > div:nth-child(10) {
  transform: rotate(270deg);
  animation-delay: 0.9s;
}
.page-loading .loading > div:nth-child(11) {
  transform: rotate(300deg);
  animation-delay: 1s;
}
.page-loading .loading > div:nth-child(12) {
  transform: rotate(330deg);
  animation-delay: 1.1s;
}
@keyframes opacity {
  0% {
    opacity: 0.2;
  }
  50% {
    opacity: 1;
  }
  100% {
    opacity: 0.2;
  }
}
    </style>
</html>
