@extends('layouts.teacher.layout')

@section('content')
    <div class="container-fluid w-full lg:mx-2">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Exams</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Exams assigned to you - enter or update student marks
            </p>
              {{-- Flash Success Message --}}
                 @include('partials.message')
             <!-- Page Header -->
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Exams to Mark</h2>
            </div>

            <div class="p-6">
                @if($exams->isEmpty())
                    <p class="text-center py-12 text-gray-500 dark:text-gray-400">
                        No exams assigned to you for marking yet.
                    </p>
                @else
                    <div class="space-y-4">
                       
                        @foreach($exams as $exam)
                         @php
                            $termMap = ["First Term" => 1, "Second Term" => 2, "Third Term" => 3];
                            $term = strtolower($termMap[$exam->academicTerm->name])
                        @endphp
                            <div class="flex items-center justify-between p-4 borderr shadow-md hover:shadow-lg dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/30 relative">
                                <p class='bg-yellow-400 px-1 text-red-500 rounded-full text-xs absolute top-0 right-0'>
                                        {{ $exam->status }}
                                    </p>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold text-sm text-gray-800 dark:text-white">{{ $exam->subject->name . " EXAM"}}
                                    </h3>
                                    
                                    </div>
                                    <div class="flex gap-6 sm:gap-10 items-center">
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                        <span>{{ $exam->section->name ?? '-' }}</span>
                                        <span>{{ $exam->examType->name ?? "-" }}</span>
                                         <span>{{ $term ?? '-' }}</span>
                                    </p>
                                    
                                    {{-- to mark the exam as done --}}
                                   <form action="{{ route("teacher.marks.change-status", $exam) }}" method='POST'>
                                    @csrf
                                    @method("PATCH")
                                    @php
                                        $btntext = match ($exam->status){
                                           "undone"     =>  "Mark as done",
                                           "done"       =>  "Submit Marks",
                                           "submitted"       =>  "Marks Submitted",
                                        }
                                    @endphp

                                    @if ( $exam->status === "done")
                                        <button type="submit" class='bg-blue-500 text-white py-1 px-2 rounded text-xs'> {{$btntext}}
                                         </button>
                                    @endif
                                         
                                   </form>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <a href="{{ route('teacher.exam.marks.enter', $exam) }}"
                                       class="py-2 px-4 rounded text-white bg-green-500 hover:bg-green-600 text-sm">
                                        Enter / Edit Marks
                                    </a>
                                    <!-- Optional view link -->
                                    <a href="{{ route('teacher.exam.marks.view', $exam) }}"
                                       class="py-2 px-4 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
                                        View Entered Marks
                                    </a>
                                    <!-- Download marksheet -->
                                    <a href="{{ route('teacher.exam.marksheet', $exam) }}"
                                       class="py-2 px-4 rounded bg-blue-500 hover:bg-blue-600 text-white text-sm inline-flex items-center gap-1">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        Marksheet
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection