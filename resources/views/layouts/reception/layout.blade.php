{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('base-navigation')
  @include('layouts.partials.navigation', ['notifyMode' => 'receptionist', 'showAcademicYear' => false])
@endsection

@section('base-sidebar')
  @include('layouts.reception.sidebar')
@endsection

@section('base-content')
  @yield('content')
@endsection
