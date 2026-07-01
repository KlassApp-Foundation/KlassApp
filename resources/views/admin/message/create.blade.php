{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Messages',
    'subtitle' => 'Send emergency notifications and communications to staff and students.',
])
	    </h1>
	    @include('partials.message')
	    <emergency-message url="{{ url('/') }}" mode="admin"></emergency-emergency> 
	</div>
@endsection