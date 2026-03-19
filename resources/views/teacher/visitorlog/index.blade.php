{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
    <div class="relative">
        @include('partials.message')
        <list-visitor-log url="{{ url('/') }}" mode="teacher"></list-visitor-log>
    </div>
@endsection