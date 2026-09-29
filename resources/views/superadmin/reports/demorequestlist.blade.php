{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
    <div class="relative">
        <div class="ds-page-head">
            <h1 class="ds-page-head-title">Demo Requests</h1>
            <p class="ds-page-head-sub">Lead form submissions from the public landing page - read-only</p>
        </div>
        <livewire:superadmin.reports.demo-requests />
    </div>
@endsection
