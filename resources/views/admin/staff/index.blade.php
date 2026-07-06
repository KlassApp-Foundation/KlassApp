{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="relative px-4 md:px-6 py-4">

<div class="ds-page-head">
    <div>
        <h1 class="ds-page-head-title">Support Staff ({{ $count }})</h1>
    </div>
    <div class="flex items-center gap-2">
        <portal-target name="search"></portal-target>
        <portal-target name="teacherfilter"></portal-target>
        <x-button href="{{ url('/admin/staff/add/') }}" variant="success" size="sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Add
        </x-button>
        <staff-export url="{{ url('/') }}" searchquery="{{ $query }}"></staff-export>
    </div>
</div>

@include('partials.message')
<staff-list url="{{ url('/') }}" searchquery="{{ $query }}" birthday="{{ $birthday }}" letter="{{ $alphabet }}"></staff-list>
<staff-filter url="{{ url('/') }}" searchquery="{{ $query }}"></staff-filter>

</div>
@endsection
