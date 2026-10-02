{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admission')
@section('content')
    <div class="w-full lg:mx-2">
        <div class="bg-white shadow py-3">
            <div class="container mx-auto px-3 lg:px-0">
                <div>
                    <a href="{{ url('/') }}" aria-label="KlassApp home">
                        <img src="{{ !empty($logo) ? $logo : asset('images/klassapp-logo-primary.svg') }}" class="inline-block" style="height:55px;" alt="KlassApp">
                    </a>
                </div>
            </div>
        </div>
        @php
            $closeMessage = $closedetails?->meta_value;
            if (! filled($closeMessage) || $closeMessage === '-') {
                $closeMessage = null;
            }
        @endphp
        @if($isOpen)
            <h1 class="admin-h1 my-3 flex items-center">
                <span class="mx-3">Admission Form</span>
            </h1>
            @include('partials.message')
            <!-- multistep form -->
            <form method="POST" action="" enctype="multipart/form-data" id="msform" class="w-full lg:w-1/2 mx-auto">
                @csrf
                <add-admission url="{{ url('/') }}" slug="{{ $slug }}" boarding="{{ $boardingAvailable ? 'true' : 'false' }}"></add-admission>
                <portal-target name="add_admissionform"></portal-target>
            </form>

        @else
            <h1 class="admin-h1 my-3 flex items-center" data-testid="admission-closed">
                <span class="mx-3">{{ $closeMessage ?? 'Admissions are currently closed.' }}</span>
            </h1>
        @endif
    </div>
@endsection
