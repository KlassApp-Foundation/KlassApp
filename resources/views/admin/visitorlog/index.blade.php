{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="relative">
        @include('partials.message')
        <list-visitor-log url="{{ url('/') }}" mode="admin"></list-visitor-log>
    </div>
@endsection