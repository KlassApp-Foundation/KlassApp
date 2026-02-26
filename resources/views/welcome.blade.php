{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.minimal')

@section('content')
    <div class="bg-gray-300 pt-16 min-h-screen">
        <div class="container flex flex-col items-center mx-auto  bg-white p-8 shadow">
            <img src="{{ url('/uploads/demologo.png') }}" style="height: 150px; width: 150px;">
            <h1 class="font-bold text-3xl mt-12">THE LEADING UGANDAN SCHOOL MANAGEMENT SYSTEM</h1>
            <h2 class="font-bold text-xl">For Primary and secondary schools</h2>
           <!--  <demo-tab url="{{ url('/') }}"></demo-tab> -->
            <a href="{{ url('/login') }}"
                class="btn bg-red-600 hover:bg-red-900 text-white font-bold tracking-wide uppercase px-5 py-3 mt-12 cursor-pointer rounded">Web
                Control Panel Login</a>
            <div class="grid lg:grid-cols-3 gap-10">
        {{-- <a href="{{ url('/uploads/appfile/demo_school_parent_app.apk') }}"
                    class="btn bg-gray-600 hover:bg-gray-900 text-white font-bold tracking-wide uppercase text-xs px-5 py-3 mt-12 cursor-pointer rounded flex items-center justify-center"
                    download>
                    @include('welcome._android_svg')
                    <div class="ml-4">Download Android App <br />for <span class="font-extrabold underline">Parents</div>
                </a> --}}
                {{-- <a href="{{ url('/uploads/appfile/demo_school_teacher_app.apk') }}" class="btn bg-gray-600 hover:bg-gray-900 text-white font-bold tracking-wide uppercase text-xs px-5 py-3 mt-12 cursor-pointer rounded flex items-center justify-center">
                        @include('welcome._android_svg')
                        <div class="ml-4">Download Android App <br/>for <span class="font-extrabold underline">Teachers</div>
                    </a> --}}

                     {{-- <a href="{{ url('/uploads/appfile/demo_school_driver_app.apk') }}" class="btn bg-gray-600 hover:bg-gray-900 text-white font-bold tracking-wide uppercase text-xs px-5 py-3 mt-12 cursor-pointer rounded flex items-center justify-center">
                        @include('welcome._android_svg')
                        <div class="ml-4">Download Android App <br/>for <span class="font-extrabold underline">Driver</div>
                    </a> --}}

                    {{-- <a href="{{ url('/uploads/appfile/demo_school_coordinator_app.apk') }}" class="btn bg-gray-600 hover:bg-gray-900 text-white font-bold tracking-wide uppercase text-xs px-5 py-3 mt-12 cursor-pointer rounded flex items-center justify-center">
                        @include('welcome._android_svg')
                        <div class="ml-4">Download Android App <br/>for <span class="font-extrabold underline">Co-ordinator</div>
                    </a> --}}

                   <!--  <a href="{{ url('/student-app') }}" class="btn bg-gray-600 hover:bg-gray-900 text-white font-bold tracking-wide uppercase text-xs px-5 py-3 mt-12 cursor-pointer rounded flex items-center justify-center">
                        @include('welcome._android_svg')
                        <div class="ml-4">Download Android App <br/>for <span class="font-extrabold underline">Students</div>
                    </a> -->
            </div>

        </div>
        <div class="container mx-auto my-8 text-sm">
            Powered By : <a href="https://gegosoft.com">elicom solutions</a>
        </div>
    </div>
    {{-- @include('welcome._slider_cta_section') --}}
    {{-- @include('welcome._app_tiles_section') --}}
    {{-- @include('welcome._better_communications_section') --}}
    {{-- @include('welcome._better_data_management_section') --}}
    {{-- @include('welcome._modules_list_section') --}}
    {{-- @include('welcome._footer_cta_section') --}}
    {{-- @include('welcome._footer_usecase_section') --}}
@endsection
