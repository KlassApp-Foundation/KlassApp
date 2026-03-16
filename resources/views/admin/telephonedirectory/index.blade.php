{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="relative">
        @include('partials.message')
        <list-phone-number url="{{ url('/') }}"></list-phone-number>
   </div>
@endsection