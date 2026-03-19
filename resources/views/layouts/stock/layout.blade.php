{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('base-navigation')
  @include('layouts.stock.navigation')
@endsection

@section('base-sidebar')
  @include('layouts.stock.sidebar')
@endsection

@section('base-content')
  @yield('content')
@endsection

@section('base-content')
  @yield('content')
@endsection
