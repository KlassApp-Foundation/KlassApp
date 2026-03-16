{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="relative">
        <portal-target name="add_academic_year"></portal-target>
        @include('partials.message')
        <list-academic-year url="{{ url('/') }}"></list-academic-year>
    </div>
@endsection