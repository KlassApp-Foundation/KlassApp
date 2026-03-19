{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
    <div class="relative">
        <livewire:superadmin.setting.city-form  :id="$id" />
    </div>
@endsection