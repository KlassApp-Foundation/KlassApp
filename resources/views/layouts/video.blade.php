{{-- SPDX-License-Identifier: MIT --}}
<!-- <!DOCTYPE html> -->
<html lang="en">
<head>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/1.1.3/tailwind.min.css" rel="stylesheet">
</head>
  <body id="app">
  <div class="fixed w-full h-full overflow-auto">
  <div class="fixed bg-white px-5 py-2 rounded-b-lg mx-2 lg:mx-16 z-40">
  	<img src="{{url('images/gego_k12.png')}}" class="w-12 lg:w-24">
  </div>
  @include('pages.video.__video-bar')
  @yield('content')
  <!-- @include('pages.video.__video-toolbar') -->
  </div>
     <script src="{{ asset('js/app.js') }}"></script>
        <script src="{{ asset('js/custom.js') }}" ></script>
   @stack('scripts')
</body>
</html>
