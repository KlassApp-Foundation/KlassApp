{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('base-navigation')
  @include('layouts.parent.navigation')
@endsection

@section('base-sidebar')
  @include('layouts.parent.sidebar')
@endsection

@section('base-content')
  @yield('content')
@endsection
