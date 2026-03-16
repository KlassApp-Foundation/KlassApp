{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
    <div class="relative">
        <livewire:superadmin.academics.userprofile-form  :id="$id" />
    </div>
@endsection