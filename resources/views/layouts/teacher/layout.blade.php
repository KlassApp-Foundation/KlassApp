{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('base-navigation')
  @include('layouts.partials.navigation', ['notifyMode' => 'teacher', 'showAcademicYear' => false])
@endsection

@section('base-sidebar')
  @include('layouts.teacher.sidebar')
@endsection

@section('base-content')
    @yield('content')
@endsection
