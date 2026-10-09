{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')


@section('base-navigation')
  @include('layouts.partials.navigation', ['chromeInSidebar' => true, 'showAcademicYear' => $navShowAcademicYear ?? true])
@endsection


@section('base-sidebar')
  @include('layouts.admin.sidebar')
@endsection

@section('base-content')
  @yield('content')
@endsection
