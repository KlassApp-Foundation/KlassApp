{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="relative">
        <div class="flex flex-wrap lg:flex-row justify-between my-3">
            <portal-target name="student_count"></portal-target>
            <!-- <div class="">
                <h1 class="admin-h1 my-3">Students ( {{ $count }} )</h1>
            </div> -->
            
            <div class="relative flex items-center w-1/4 lg:justify-end">
              
               <!--  <div class="">
                    <a href="{{ url('/admin/exportUsers/?'.$query) }}" id="export-button" class="no-underline text-white px-4 my-3 mx-1 flex items-center custom-green py-1">
                        <span class="mx-1 text-sm font-semibold">Export</span>
                    </a> 
                </div> -->
        
            </div>
        </div>
        @include('partials.message')
     
        <member-list url="{{ url('/') }}" searchquery="{{ $query }}" letter="{{ $alphabet }}" standard="{{ $standard }}" birthday="{{ $birthday }}" selected_standard="{{ $selected_standard }}"></member-list>
    </div>
@endsection