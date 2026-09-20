{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('base-navigation')
  @include('layouts.partials.navigation', ['notifyMode' => 'student', 'showAcademicYear' => false])
@endsection

@section('base-sidebar')
  @include('layouts.student.sidebar')
@endsection

@section('base-content')
  @yield('content')
@endsection
