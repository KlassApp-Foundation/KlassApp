{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')



  @if(Auth::user()->usergroup_id==11)
    @section('base-navigation')
      @include('layouts.accountant.navigation')
     @endsection

    @section('base-sidebar')
      @include('layouts.accountant.sidebar')
    @endsection
  @endif

  @if(Auth::user()->usergroup_id==3)

  @section('base-navigation')
    @include('layouts.partials.navigation')
   @endsection
    @section('base-sidebar')
      @include('layouts.admin.sidebar')
    @endsection
  @endif

@section('base-content')
  @yield('content')
@endsection

@section('base-content')
  @yield('content')
@endsection
