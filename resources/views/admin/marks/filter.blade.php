@extends('layouts.admin.layout')

@section('content')

<div class="container-fluid w-full lg:mx-2">

    {{-- Sticky Filter Bar --}}
    <div class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b flex justify-end">
        <div class="py-2 px-4">
            @include('admin.marks.filter-form')   <!-- your form include -->
        </div>
    </div>

    <div class="grid gap-6 mt-6">

        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Students Marks Overview</h1>
            <p class="text-gray-600 mt-1">
                Filter and review student performance by class, academic year, and term.
            </p>
        </div>

        {{-- Stats Cards (only meaningful when filtered) --}}
        @if (request()->hasAny(['term', 'year', 'class']))
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-xl shadow-sm border hover:shadow transition">
                    <p class="text-sm text-gray-600 font-medium">Total Students</p>
                    <h3 class="text-3xl font-bold text-blue-700 mt-1">
                        {{ $marks->pluck('student_id')->unique()->count() }}
                    </h3>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-white p-5 rounded-xl shadow-sm border hover:shadow transition">
                    <p class="text-sm text-gray-600 font-medium">Subjects Covered</p>
                    <h3 class="text-3xl font-bold text-green-700 mt-1">
                        {{ $marks->pluck('subject_id')->unique()->count() }}
                    </h3>
                </div>

                <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-xl shadow-sm border hover:shadow transition">
                    <p class="text-sm text-gray-600 font-medium">Records Found</p>
                    <h3 class="text-3xl font-bold text-purple-700 mt-1">
                        {{ $marks->total() }}
                    </h3>
                </div>
            </div>
        @endif

        {{-- Results Section --}}
        <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
            <div class="p-2 border-b flex items-center gap-8">
                <h3 class="text-lg font-semibold text-gray-800"> Exam Results </h3>
                    @if(!empty($marks) && $marks->isNotEmpty()) 
                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-600">
                        <span>{{ $class->name }}</span>
                        <span>{{ "Term " . $term }}</span>
                        <span> {{ $year }} </span>
                    </div>
                      @endif
               
            </div>
            <div class="p-6">
                @if (!empty($marks) && $marks->isNotEmpty())
                    @include('admin.marks.results-table', ['marks' => $marks])
                @else
                    @if (request()->hasAny(['term', 'year', 'class']))
                        <div class="text-center py-16 text-gray-500">
                            <div class="text-6xl mb-4 opacity-40">🔍</div>
                            <h4 class="text-xl font-medium text-gray-700 mb-2">No marks found</h4>
                            <p class="text-gray-500">
                                Try adjusting the filters or check if data exists for this combination.
                            </p>
                        </div>
                    @else
                        <div class="text-center py-20 text-gray-400">
                            <div class="text-7xl mb-6 opacity-30">📊</div>
                            <h4 class="text-xl font-medium">Ready when you are</h4>
                            <p class="mt-3">
                                Select class, academic year and term above to view student marks.
                            </p>
                        </div>
                    @endif
                @endif
            </div>

            {{-- ====== pagination ========= --}}
            @if (!empty($marks) && $marks->isNotEmpty())
                <div class="px-6 py-4 border-t bg-gray-50">
                    {{ $marks->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

@endsection