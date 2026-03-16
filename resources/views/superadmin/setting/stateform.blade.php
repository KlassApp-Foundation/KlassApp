{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
    <div class="relative">
        <livewire:superadmin.setting.state-form  :id="$id" />
    </div>
@endsection