{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
    <div class="relative">
        <div class="ds-page-head">
            <h1 class="ds-page-head-title">Contact Inquiries</h1>
            <p class="ds-page-head-sub">Form submissions from the public landing page — sent to team@klassapp.xyz</p>
        </div>
        <livewire:superadmin.reports.contact />
    </div>
@endsection