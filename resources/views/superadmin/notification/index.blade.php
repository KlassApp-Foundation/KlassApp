{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')

@section('content')
    <div class="px-4 md:px-6 py-4">
        @include('layouts.partials.page-header', ['title' => 'Notifications'])
        <notification-list url="{{ url('/') }}" mode="superadmin"></notification-list>
    </div>
@endsection
