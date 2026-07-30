{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <!-- Favicon -->
        @include('layouts.partials.favicon')
        <title>KlassApp</title>
        <!-- Styles / Scripts (Vite) — minimal historically only Mix app.css, not tailwind -->
        @vite([
            'resources/assets/js/app.js',
            'resources/assets/sass/app.scss',
        ])
        <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,wght@0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    </head>
    <body class="antialiased">
        <div id="app" class="flex flex-col min-h-screen">

            <main class="flex-grow">
                @yield('content')
            </main>

        </div>
        <!-- Scripts -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
        <script src="{{ asset('js/custom.js') }}" ></script>
        @stack('scripts')
        <script>
        (function waitForJquery(run) {
            if (window.$) { run(window.$); return; }
            var n = 0;
            var t = setInterval(function () {
                if (window.$) { clearInterval(t); run(window.$); }
                else if (++n > 100) { clearInterval(t); }
            }, 50);
        })(function ($) {
            $(document).ready(function(){
                $('ul.course_tabs li').click(function(){
                    var tab_id = $(this).attr('data-tab');
                    $('ul.course_tabs li').removeClass('current');
                    $('.tab-content').removeClass('current');
                    $(this).addClass('current');
                    $("#"+tab_id).addClass('current');
                });
            });
            $(window).scroll(function(){
                if($(this).scrollTop() > 100){
                    $('.home-navbar').addClass('sticky');
                } else {
                    $('.home-navbar').removeClass('sticky');
                }
            });
        });
        </script>
    </body>
</html>
