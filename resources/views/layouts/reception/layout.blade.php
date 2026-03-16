{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('base-navigation')
  @include('layouts.reception.navigation')
@endsection

@section('base-sidebar')
  @include('layouts.reception.sidebar')
@endsection

@section('base-content')
  @yield('content')
@endsection

@section('base-content')
  @yield('content')
@endsection
