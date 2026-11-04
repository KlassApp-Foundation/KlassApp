{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('base-navigation')
  @include('layouts.partials.navigation', ['notifyMode' => 'library', 'showAcademicYear' => false, 'chromeInSidebar' => true])
@endsection

@section('base-sidebar')
  @include('layouts.library.sidebar')
@endsection

@section('base-content')
  @yield('content')
@endsection
