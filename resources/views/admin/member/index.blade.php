{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Students (' . ($count ?? 0) . ')',
        'subtitle' => 'View, search, add, import, or export students.',
        'actions' => '<a href="' . url('/admin/student/add/') . '" class="px-3 py-1.5 rounded text-xs text-white bg-green-600 hover:bg-green-700 flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Add</a>'
            . '<student-export url="' . url('/') . '" searchquery="' . ($query ?? '') . '"></student-export>'
            . '<a href="' . url('/admin/import') . '" class="px-3 py-1.5 rounded text-xs text-white bg-blue-600 hover:bg-blue-700 flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg> Import</a>'
    ])
    @include('partials.message')
    <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6 shadow-sm mt-4">
        <form action="{{ url('/admin/students') }}" enctype="multipart form-data">
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <select class="tw-form-control text-xs" name="standard">
                    <option value="">All Class</option>
                    @foreach($standardLinks as $standardLink)
                        <option value="{{ $standardLink->id }}" {{ $standardLink->id == request()->query('standard') ? 'selected' : '' }} {{ $standardLink->id == $standard ? 'selected' : '' }}>{{ $standardLink->StandardSection }}</option>
                    @endforeach
                </select>
                <button value="Submit" type="submit" class="blue-bg text-sm text-white px-2 py-1 rounded mx-1">Submit</button>
            </div>
        </form>
        <member-list url="{{ url('/') }}" searchquery="{{ $query }}" letter="{{ $alphabet }}" standard="{{ $standard }}" birthday="{{ $birthday }}" selected_standard="{{ $selected_standard }}"></member-list>
        <search-filter url="{{ url('/') }}" searchquery="{{ $query }}" selected_standard="{{ $selected_standard }}"></search-filter>
    </div>
</div>
@endsection