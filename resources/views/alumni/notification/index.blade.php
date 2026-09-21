{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.alumni.layout')

@section('content')
    <div class="px-4 md:px-6 py-4">
        @include('layouts.partials.page-header', ['title' => 'Notifications'])
        <notification-list url="{{ url('/') }}" mode="alumni"></notification-list>
    </div>
@endsection
