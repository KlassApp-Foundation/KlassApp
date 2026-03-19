{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.reception.layout')

@section('content')
    <div class="relative">
        @include('partials.message')
        <list-visitor-log url="{{ url('/') }}" mode="receptionist"></list-visitor-log>
   </div>
@endsection