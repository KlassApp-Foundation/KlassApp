{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin-app')

@section('base-navigation')
  @include('layouts.superadmin.navigation')
@endsection


@section('base-sidebar')
  @include('layouts.superadmin.sidebar')
@endsection

@section('base-content')
  @yield('content')
@endsection