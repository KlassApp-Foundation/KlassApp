{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('additional-styles')
        <style>
            #playerContainer,
            .player {
                width: 100%;
                height: auto;
                min-height: 400px;
                background-color: black;
                outline: none;
            }
        </style>
@endsection

@section('base-navigation')
  @include('layouts.partials.navigation')
@endsection

@section('base-content')
  @yield('content')
@endsection
