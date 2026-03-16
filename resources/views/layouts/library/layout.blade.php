{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('base-navigation')
  @include('layouts.library.navigation')
@endsection

@section('base-sidebar')
  @include('layouts.library.sidebar')
@endsection

@section('base-content')
  @yield('content')
@endsection

@section('base-content')
  @yield('content')
@endsection
