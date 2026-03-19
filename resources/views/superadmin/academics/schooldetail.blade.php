{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
    <div class="relative">
        <livewire:superadmin.academics.school-detail :id="$id" />
    </div>
@endsection