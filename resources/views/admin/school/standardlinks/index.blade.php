{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Classes',
    'subtitle' => 'Manage class sections, streams, and class-teacher assignments.',
    'actions' => '<a href="' . url('/admin/school/standardlinks/create') . '" class="px-3 py-1.5 rounded text-xs text-white bg-green-600 hover:bg-green-700 flex items-center gap-1"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Class</a>'
])

<div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6 shadow-sm mt-4">
    <div class="flex items-center gap-3 flex-wrap">
                @if($teacher_count > 0)
                <div class="">
                    <div class="flex items-center">
                        {{-- ========== Levels =========== --}}
<a href="{{ url('/admin/standards') }}" class="no-underline text-white px-4 py-2 mx-1 flex items-center bg-green-600 hover:bg-green-700 rounded-lg text-sm font-medium">
                            <span>Level(s)</span>
                        </a>

                        {{-- classes --}}
                        <a href="{{ url('/admin/classes') }}" class="no-underline text-white px-4 py-2 mx-1 flex items-center bg-green-600 hover:bg-green-700 rounded-lg text-sm font-medium">
                            <span>Classes</span>
                        </a>

                        {{-- standard link --}}
                          <a href="{{ url('/admin/standardLink/add') }}" class="no-underline text-white px-4 py-2 mx-1 flex items-center bg-green-600 hover:bg-green-700 rounded-lg text-sm font-medium">
                            <span>Setup A Class</span>
                            <svg class="w-3 h-3 ml-1 fill-current" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        </a>
                    </div>
                </div>
            @endif

             {{-- <div class="">
                    <div class="flex items-center">
                        <a href="{{ url('/admin/classes/add') }}" class="no-underline text-white px-4 py-2 mx-1 flex items-center bg-green-600 hover:bg-green-700 rounded-lg text-sm font-medium">
                            <span class="mx-1 text-sm font-semibold">Add Class</span>
                            <svg data-v-2a22d6ae="" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 409.6 409.6" xml:space="preserve" class="w-3 h-3 fill-current text-white"><g data-v-2a22d6ae=""><g data-v-2a22d6ae=""><path data-v-2a22d6ae="" d="M392.533,187.733H221.867V17.067C221.867,7.641,214.226,0,204.8,0s-17.067,7.641-17.067,17.067v170.667H17.067 C7.641,187.733,0,195.374,0,204.8s7.641,17.067,17.067,17.067h170.667v170.667c0,9.426,7.641,17.067,17.067,17.067 s17.067-7.641,17.067-17.067V221.867h170.667c9.426,0,17.067-7.641,17.067-17.067S401.959,187.733,392.533,187.733z"></path></g></g></svg>
                        </a>
                    </div>
                </div> --}}
        </div>
        @if($teacher_count > 0)
            <standardfilter url="{{ url('/') }}" type='admin'></standardfilter>
        @endif
        @include('partials.message')
        @include('admin.school.standardlinks.list')
    </div>
</div>
@endsection