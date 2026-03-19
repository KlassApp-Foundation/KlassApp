{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
    <div class="relative">
        <div class="flex flex-wrap lg:flex-row justify-between">
            <div class="">
                <h1 class="admin-h1 my-3">Pages</h1>
            </div>
        </div>
        @include('partials.message')
        <page-list url="{{ url('/') }}" mode="student"></page-list>
    </div>

@endsection