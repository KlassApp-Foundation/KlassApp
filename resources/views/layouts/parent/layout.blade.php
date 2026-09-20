{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('base-navigation')
  @include('layouts.partials.navigation', ['notifyMode' => null, 'showAcademicYear' => false, 'brandText' => 'KlassApp Parent Portal', 'brandRoute' => 'parent.dashboard', 'showLogout' => true])
@endsection

@section('base-sidebar')
  @include('layouts.parent.sidebar')
@endsection

@section('base-content')
  @yield('content')
@endsection
