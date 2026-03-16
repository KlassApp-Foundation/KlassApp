{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="relative">
        <div class="flex flex-wrap lg:flex-row justify-between">
            <div>
                <h1 class="admin-h1 mb-5 flex items-center">
                    <span class="mx-3">Setup Standards</span>
                </h1>
            </div>
        </div>
        @include('partials.message')
        <standard-setup url="{{ url('/') }}" academic_year_id="{{ $academic_year_id }}"></standard-setup>
    </div>
@endsection