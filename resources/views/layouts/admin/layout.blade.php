{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')


@section('base-navigation')
  @include('layouts.partials.navigation')
@endsection


@section('base-sidebar')
  @include('layouts.admin.sidebar')
@endsection

@section('base-content')
  @yield('content')
@endsection