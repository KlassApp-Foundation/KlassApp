{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
    <div class="relative">
        @include('partials.message')
        <add-task url="{{ url('/') }}" searchquery="{{ $query }}" mode="teacher"></add-task>
   	</div>
@endsection