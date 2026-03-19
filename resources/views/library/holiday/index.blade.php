{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.library.layout')

@section('content')
    <div class="relative">
        <div class="flex flex-wrap lg:flex-row justify-between">
            <div class="">
                <h1 class="admin-h1 my-3">Holidays</h1>
            </div>
        </div>
        @include('partials.message')
        <holiday-list url="{{ url('/') }}" mode="library"></holiday-list>
    </div>
@endsection