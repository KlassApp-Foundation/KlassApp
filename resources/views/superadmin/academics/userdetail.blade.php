{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
    <div class="relative">
        <livewire:superadmin.academics.user-detail :id="$id" />
    </div>
@endsection