{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admission')
@section('content')
    <div class="w-full lg:mx-2">
        <div class="bg-white shadow py-3">
            <div class="container mx-auto px-3 lg:px-0">
                <h1 class="admin-h1 my-3 flex items-center" data-testid="admission-unavailable">
                    <span class="mx-3">We could not load the admission form right now. Please try again in a few minutes.</span>
                </h1>
                <p class="mx-3 my-2">
                    <a href="{{ url()->current() }}" class="text-blue-600 underline">Try again</a>
                </p>
            </div>
        </div>
    </div>
@endsection
