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
        <title>{{ config('app.name', 'GegoK12') }}</title>
        <!-- Styles -->
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;500&family=IBM+Plex+Sans:wght@500;600;700&family=Nunito+Sans:ital,wght@0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    </head>
    <body class="antialiased">
        <div id="app" class="flex flex-col min-h-screen">
            @include('layouts.partials.home_navigation')
            <main class="flex-grow">
                @yield('content')
            </main>
            @include('layouts.partials.main-footer')
        </div>
        <!-- Scripts -->
        <script src="{{ asset('js/app.js') }}"></script>
        <script src="{{ asset('js/custom.js') }}" ></script>
        @stack('scripts')
        <script>
    $(document).ready(function(){
    
    $('ul.course_tabs li').click(function(){
        var tab_id = $(this).attr('data-tab');

        $('ul.course_tabs li').removeClass('current');
        $('.tab-content').removeClass('current');

        $(this).addClass('current');
        $("#"+tab_id).addClass('current');
    })

})
</script>
<!-- <script>
    $(document).ready(function(){
    
    $('ul.course_tabs li').click(function(){
        var tab_id = $(this).attr('data-tab');

        $('ul.course_tabs li').removeClass('current');
        $('.tab-content').removeClass('current');

        $(this).addClass('current');
        $("#"+tab_id).addClass('current');
    })

})

        </script> -->
        <script>
  $(window).scroll(function(){
    if($(this).scrollTop() > 100){
      $('.home-navbar').addClass('sticky')
      } else{
      $('.home-navbar').removeClass('sticky')
      }
  });
/*  function checkscroll() {
  console.log (window.pageYPffset);
if (window.pageYOffset > 1000) {
console.log("Show time");
return 
{
stickyNavBar: true}
} 
else {
return { stickyNavBar : false }
}
}*/
</script>
    </body>
</html>