{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
    <div class="relative">
            <div class="flex items-center justify-between my-3">
                <h1 class="admin-h1">Add Teacher Timetable details</h1>
            </div>
        <livewire:timetable.teacher-time-table-detail/>    
    </div>
@endsection

