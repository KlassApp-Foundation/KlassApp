{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admission')
@section('content')
    <div class="w-full lg:mx-2">
        <div class="bg-white shadow py-3">
            <div class="container mx-auto px-3 lg:px-0">
                <div>
                    <a href="{{ url('/') }}">
                        <img src="{{ $logo }}" class="inline-block" style="height:55px;">
                    </a>
                </div>
            </div>
        </div>
        @if($admission_open['meta_value']=="1")
            <h1 class="admin-h1 my-3 flex items-center">       
                <span class="mx-3">Admission Form</span>
            </h1>
            @include('partials.message')
            <!-- multistep form -->
            <form method="POST" action="" enctype="multipart/form-data" id="msform" class="w-full lg:w-1/2 mx-auto">
                @csrf
                <add-admission url="{{ url('/') }}" slug="{{ $slug }}"></add-admission>
                <portal-target name="add_admissionform"></portal-target>
            </form>

        @elseif($admission_open['meta_value']=="0")
            <h1 class="admin-h1 my-3 flex items-center">       
                <span class="mx-3">{{ $closedetails['meta_value'] }}</span>
            </h1>
        @endif
    </div>
@endsection