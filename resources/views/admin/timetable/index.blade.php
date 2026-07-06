@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Timetable',
        'subtitle' => 'Select a class to view or manage its timetable.',
    ])

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mt-4">
        @forelse($standards as $standard)
            <a href="{{ url('admin/standardLink/show/timetable/' . $standard->id) }}"
               class="block bg-white rounded-lg border border-gray-200 p-5 shadow-sm hover:shadow-md hover:border-blue-300 transition">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">{{ $standard->StandardSection }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">View timetable</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12 text-gray-400">
                <p class="text-lg font-medium">No classes found</p>
                <p class="text-sm mt-1">Create classes first to set up timetables.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
