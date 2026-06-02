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
                                        <h3 class="font-semibold text-sm text-red-00 dark:text-white">{{ $exam->subject->name . " EXAM"}}
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

                                    @if ($exam->status === "undone" || $exam->status === "done")
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
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection