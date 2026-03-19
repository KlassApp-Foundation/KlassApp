{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="relative">
        <div class="flex flex-wrap lg:flex-row justify-between my-3">
            <div class="">
                <h1 class="admin-h1 my-3">Reports</h1>
            </div>
            <div class="relative flex items-center w-1/4 lg:justify-end">
                <div class="flex items-center" dusk="add-button">
                    <a href="{{ url('/admin/reports') }}" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 ml-3 text-sm font-medium">Reset Filters</a>
                </div>
            </div>
        </div>
        @include('partials.message')
    </div>

    <div class="flex flex-col lg:flex-row">
        <div class="flex flex-wrap w-full lg:w-1/2 lg:mr-3 my-2">
            <div class="bg-white shadow border border-grey px-5 py-3 w-full">
                <div class="flex flex-wrap lg:flex-row justify-between my-3">
                    <div>
                        <h1 class="admin-h1 mb-5 flex items-center"> Student </h1>
                    </div>
                    <div class="w-full lg:w-2/4">
                        <portal-target name="search"></portal-target>
                        <portal-target name="memberfilter"></portal-target>
                    </div>
                    <div class="mx-2 relative flex items-center w-1/2 lg:justify-end">
                        <search-filter url="{{ url('/') }}" searchquery="{{ $query }}" type="report"></search-filter>
                    </div>
                </div>
                <ul class="leading-loose list-disc px-5">
                    <li>
                        <a href="{{ url('/admin/report/activeStudents?status=active&'.$query) }}"> Active Students </a>
                    </li>
                    <li>
                        <a href="{{ url('/admin/report/suspendedStudents?status=inactive&'.$query) }}"> Suspended Students </a>
                    </li>
                    <li>
                        <a href="{{ url('/admin/report/exitStudents?status=exit&'.$query) }}"> Exit Students </a>
                    </li>
                    @if(config('gfee.enabled', false))
                    <li>
                        <a href="{{ url('/admin/report/fees?'.$query) }}"> Fees Paid History </a>
                    </li>
                    @endif    
                </ul>  
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row">
        <div class="flex flex-wrap w-full lg:w-1/2 lg:mr-3 my-2">
            <div class="bg-white shadow border border-grey px-5 py-3 w-full">
                <p>
                    <h1 class="admin-h1 mb-5 flex items-center"> Important Dates </h1>
                </p>
                <ul class="leading-loose list-disc px-5">
                    <li>
                        <a href="{{ url('/admin/report/birthday/student') }}"> Student's Birthday </a>
                    </li>
                    <li>
                        <a href="{{ url('/admin/report/birthday/teacher') }}"> Teacher's Birthday </a>
                    </li>
                    <li>
                        <a href="{{ url('/admin/report/exportHolidays') }}"> Holidays List </a>
                    </li>  
                </ul>  
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row">
        <div class="flex flex-wrap w-full lg:w-1/2 lg:mr-3 my-2">
            <div class="bg-white shadow border border-grey px-5 py-3 w-full">
                <div class="flex flex-wrap lg:flex-row justify-between my-3">
                    <div>
                        <h1 class="admin-h1 mb-5 flex items-center"> Parent </h1>
                    </div>
                    <div class="w-full lg:w-2/4">
                        <portal-target name="parent_search"></portal-target>
                        <portal-target name="parentfilter"></portal-target>
                    </div>
                    <div class="mx-2 relative flex items-center w-1/2 lg:justify-end">
                        <parent-search-filter url="{{ url('/') }}" searchquery="{{ $query }}" mode="report"></parent-search-filter>
                    </div>
                </div>
                <ul class="leading-loose list-disc px-5">
                    <li>
                        <a href="{{ url('/admin/report/parents?'.$query) }}"> Parent History </a>
                    </li>    
                </ul>  
            </div>
        </div>
    </div>

    @if(config('ginventory.enabled', false))
    <div class="flex flex-col lg:flex-row">
        <div class="flex flex-wrap w-full lg:w-1/2 lg:mr-3 my-2">
            <div class="bg-white shadow border border-grey px-5 py-3 w-full">
                <div class="flex flex-wrap lg:flex-row justify-between my-3">
                    <div>
                        <h1 class="admin-h1 mb-5 flex items-center"> Stock History </h1>
                    </div>
                     <div class="w-full lg:w-2/4">
                        <portal-target name="stock_search"></portal-target>
                        <portal-target name="stockfilter"></portal-target>
                    </div>
                    <div class="mx-2 relative flex items-center w-1/2 lg:justify-end">
                        <stock-search-filter url="{{ url('/') }}" searchquery="{{ $query }}"></stock-search-filter>
                    </div>
                </div>
                <ul class="leading-loose list-disc px-5"> 
                    <li>
                        <a href="{{ url('/admin/report/currentstock') }}"> Current Stock Report </a>
                    </li>  

                    <li>
                        <a href="{{ url('/admin/report/monthlypurchase?'.$query) }}"> Monthly Purchase Report </a>
                    </li> 

                    <li>
                        <a href="{{ url('/admin/report/monthlysales?'.$query) }}"> Monthly Sales Report </a>
                    </li>    
                </ul>  
            </div>
        </div>
    </div>
    @endif
@endsection