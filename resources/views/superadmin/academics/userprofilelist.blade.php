{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
    <div class="relative">
        <div class="flex items-center justify-between my-3">
            <h1 class="admin-h1">Userprofile</h1>
        </div>
        <livewire:superadmin.academics.userprofile-list :id="$id" />
    </div>
@endsection