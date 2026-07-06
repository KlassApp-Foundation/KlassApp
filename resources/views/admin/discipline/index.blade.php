{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout') 

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Records',
    'subtitle' => 'Track disciplinary cases and student conduct records.',
    'actions' => '<a href="' . url('/admin/discipline/create') . '" class="px-3 py-1.5 rounded text-xs text-white bg-green-600 hover:bg-green-700 flex items-center gap-1"><i class="fa-solid fa-plus text-xs"></i> New Record</a>'
])

<div class="relative mt-4">
<section class="section">
    <div class="w-full">
        <!-- <div class="flex justify-between mb-2 mt-2 lg:mt-0">
            <a href="{{url('/admin/discipline/add/')}}" class="no-underline text-white  px-4 my-3 mx-1 flex items-center custom-green py-1 justify-center">
                <span class="mx-1 text-sm font-semibold">Add</span>
                <img src="{{url('uploads/icons/plus.svg')}}" class="w-3 h-3">
            </a>
        </div> -->
        </div>
        <div class="p-4 bg-white shadow-lg">
            <div class="w-full">
                @include('partials.message')
            </div>
            <form action="{{ url('/admin/disciplines') }}" enctype="multipart form-data">
                <div class=" flex flex-wrap items-center mb-3">
                    <select class="tw-form-control text-xs" name="class">
                        <option value="">Filter By Class</option>
                        @foreach($standardLinks as $standardLink)
                            <option value="{{ $standardLink->id }}" {{ $standardLink->id == request()->query('class') ? 'selected' : '' }}>{{ $standardLink->StandardSection }}</option>
                        @endforeach
                    </select>
                    <select class="tw-form-control text-xs ml-1" name="type">
                        <option value="">Filter By Type</option>
                        <option value="discipline" {{  request()->query('type')=='discipline' ? 'selected' : '' }}>Discipline</option>
                        <option value="performance" {{  request()->query('type')=='performance' ? 'selected' : '' }}>Performance</option>
                        <option value="others" {{  request()->query('type')=='others' ? 'selected' : '' }}>Others</option>
                        
                    </select>
                    <button value="Submit" type="submit" class="blue-bg text-sm text-white px-2 py-1 rounded mx-1">Submit</button>
                </div>
            </form>
            @include('admin.discipline.list')               
        </div>
    </div>
</section>
@endsection