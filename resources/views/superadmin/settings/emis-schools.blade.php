{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--superadmin">
    <div class="ds-page-head">
        <h1 class="ds-page-head-title">EMIS Schools</h1>
        <p class="ds-page-head-sub">Browse Ministry of Education registered schools</p>
    </div>
    <div class="mt-4">
        @livewire('superadmin.settings.emis-school-list')
    </div>
</div>
@endsection
