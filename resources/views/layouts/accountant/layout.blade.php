{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('base-navigation')
  @if(Auth::user()->usergroup_id==11)
    @include('layouts.accountant.navigation')
  @elseif(Auth::user()->usergroup_id==3)
    @include('layouts.partials.navigation')
  @endif
@endsection

@section('base-sidebar')
  @if(Auth::user()->usergroup_id==11)
    @include('layouts.accountant.sidebar')
  @elseif(Auth::user()->usergroup_id==3)
    @include('layouts.admin.sidebar')
  @endif
@endsection

@section('base-content')
  @yield('content')
@endsection
