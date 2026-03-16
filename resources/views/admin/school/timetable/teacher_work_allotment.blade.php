{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
    <div class="relative">
            <div class="flex items-center justify-between my-3">
                <h1 class="admin-h1">Teacher Work Allotment details</h1>
                   <a href="#" onclick="printTeacher()" class="blue-bg text-sm text-white px-2 py-1 rounded mx-1">Print</a>
            </div>
        <livewire:timetable.teacher-work-allotment-detail/>    
    </div>
@endsection