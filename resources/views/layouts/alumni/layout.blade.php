{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('base-navigation')
    @include('layouts.partials.navigation', ['notifyMode' => 'alumni', 'showAcademicYear' => false, 'chromeInSidebar' => true])
@endsection

@section('base-sidebar')
    @include('layouts.alumni.sidebar')
@endsection

@section('base-content')
    @yield('content')
@endsection

@section('base-content')
    @yield('content')
@endsection