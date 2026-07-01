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

         @filamentStyles
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
        <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
        <link href="{{ asset('css/dashboard-refresh.css') }}" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;500&family=IBM+Plex+Sans:wght@500;600;700&family=Nunito+Sans:ital,wght@0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

        <link rel="stylesheet" href="https://unpkg.com/@themesberg/flowbite@1.2.0/dist/flowbite.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">   
         <script>
        window.User = {!! json_encode(optional(auth()->user())->only('id')) !!}
    </script>

    {{-- <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script> --}}

 <livewire:styles>
    </head>
    <body class="font-primary antialiased min-h-screen overflow-x-hidden" id="superadmin-body">
        <div id="app">
            @yield('base-navigation')
            <main class="flex w-full h-full min-h-screen">
                <div class="sidebar min-h-full">
                    @yield('base-sidebar')
                </div>
                <div class="bg-gray-200 flex-grow w-full px-4 superadmin-content" style="width: calc(100vw - 195px); transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1);">
                    @yield('base-content')
                </div>
            </main>
            @yield('base-footer')
        </div>

        @yield('outside-app')

        @auth
            @if(in_array(auth()->user()->usergroup_id, [1, 2, 3]))
                @livewire('agent-toshi')
            @endif
        @endauth

        <!-- Scripts -->
        @filamentScripts

        <script src="{{ asset('js/app.js') }}" defer></script>
        <script src="{{ asset('js/custom.js') }}" ></script>
        @stack('scripts')

        <livewire:scripts>
        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="{{ asset('vendor/livewire-alert/livewire-alert.js') }}"></script>


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

<script>
    (function () {
        const body = document.getElementById('superadmin-body');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('superadmin-sidebar');
        const storageKey = 'superadmin_sidebar_collapsed';

        function setCollapsed(collapsed) {
            body.classList.toggle('sidebar-collapsed', collapsed);
            if (sidebar) {
                sidebar.setAttribute('data-collapsed', collapsed ? 'true' : 'false');
            }
            try {
                localStorage.setItem(storageKey, collapsed ? '1' : '0');
            } catch (e) {}
        }

        // Ensure top-level menu items have title attributes for collapsed tooltips.
        if (sidebar) {
            sidebar.querySelectorAll('li > a').forEach(function (link) {
                const span = link.querySelector('span');
                if (span && !link.parentElement.hasAttribute('title')) {
                    const text = span.textContent.trim().split('\n')[0].trim();
                    if (text) {
                        link.parentElement.setAttribute('title', text);
                    }
                }
            });
        }

        // Restore preference on load
        try {
            const stored = localStorage.getItem(storageKey);
            if (stored === '1') {
                setCollapsed(true);
            }
        } catch (e) {}

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                const isCollapsed = body.classList.contains('sidebar-collapsed');
                setCollapsed(!isCollapsed);
            });
        }
    })();
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

.tw-form-control{
    border: 1px solid #9ca3af !important;
    border-radius: 0.25rem !important;
    font-size: 0.875rem !important;
}
    </style>
</html>
