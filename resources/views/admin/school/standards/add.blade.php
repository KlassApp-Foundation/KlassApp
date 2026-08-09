{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="relative">
        <div class="ds-page-head">
            <div class="flex items-center gap-3">
                <x-button href="{{ url('/admin/standards') }}" variant="ghost" size="sm" title="Back">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                </x-button>
                <h1 class="ds-page-head-title">Setup Standards</h1>
            </div>
        </div>
        @include('partials.message')
        {{-- @include("admin.school.standards.standard_form") --}}
        <standard-setup
            url="{{ url('/') }}"
            academic_year_id="{{ $academic_year_id }}"
            current-board="{{ $current_board }}">
        </standard-setup>
    </div>
@endsection
