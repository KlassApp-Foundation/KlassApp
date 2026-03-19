{{-- SPDX-License-Identifier: MIT --}}
  <div class="container mx-auto homepage pt-8 pb-16 lg:px-32 flex flex-col items-center">
     <div class="image-wrapper mt-2 mb-8">
        <img src="https://gego-static.s3.ap-south-1.amazonaws.com/gegok12/gego_k12_logo.png" class="logo-image h-32" alt="GegoK12 - School Management System " />
    </div>
    <!-- <h1 class="text-2xl px-4 lg:px-0 lg:text-5xl text-black text-center font-plex font-extrabold mt-6 mb-6 leading-tight">School Management & Communication System <span class="underline">on Cloud</span></h1>
    <div class="font-exo text-lg lg:text-xl font-medium text-black text-center leading-relaxed mb-6 px-5 lg:px-16">GegoK12 is a modern best-in-class School Management Software, crafted with Cloud Technology to effectively manage all School Operations and effortlessly communicate with Parents and Students through Mobile App.</div> -->
    <demo-tab url="{{ url('/') }}"></demo-tab>
    <a href="{{ url('/uploads/appfile/parent_app.apk') }}" class="btn bg-gray-600 hover:bg-gray-900 text-white font-bold tracking-wide uppercase text-xs px-5 py-3 mt-12 cursor-pointer rounded flex items-center justify-center" download>
                    @include('welcome._android_svg')
                    <div class="ml-4">Download Android App <br/>for <span class="font-extrabold underline">Parents</span></div>
                </a>

    @guest
    <div class="my-4 text-center mx-2 lg:mx-0 md:mx-0">
      <a href="{{ url('/register') }}" class="inline-block px-3 py-2 bg-red-600 text-base lg:text-xl md:text-xl font-exo text-white tracking-wide rounded my-1">
      Trial Signup ( FREE for 30 Days )
      </a>
    {{--   <a href="{{ \Storage::url('sdk/app-debug.apk') }}" class="inline-block px-3 py-2 bg-red-600 text-xl font-exo text-white tracking-wide rounded my-1">
      Click Here To Download App
      </a> <!-- have to change url --> --}}
    </div>
    @endguest
    <!-- <div class="image-wrapper lg:mt-24 mt-8">
        <img src="https://gego-static.s3.ap-south-1.amazonaws.com/gegok12/gego_k12_school_management_system.jpg" class="cta-image" alt="GegoK12 School Management System" />
    </div> -->
</div>
