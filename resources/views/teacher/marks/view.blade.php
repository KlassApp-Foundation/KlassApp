@extends('layouts.teacher.layout')

@section('content')
<div class="container-fluid w-full lg:mx-2 py-2 text-gray-800">
    {{-- Errors (e.g. the marks-locked message) must be visible on this page:
         updateMark redirects back here when a correction is refused. --}}
    @include('partials.message')

    {{-- Header --}}
    @php
        $examStatus = isset($submission) && $submission ? $submission->reviewLabel() : 'Not started';
    @endphp
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-3">
        <div>
            <h2 class="text-2xl font-semibold ">{{ $exam->examType->code ?? $exam->examType->name ?? 'Exam' }} Exam Marks</h2>
                <p class="text-xs text-center rounded-full px-3 py-1" style="background:#E2E8F0;color:#1E293B;" data-testid="marks-view-status">
                {{ $examStatus }}</p>
        </div>

    </div>

    @if(isset($submission) && $submission && $submission->status === 'reopened')
    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3" data-testid="marks-reopened-banner">
        <p class="text-sm font-semibold" style="color:#92400E;">
            {{ $submission->isReturned() ? 'Returned' : 'Reopened' }} — you can edit these marks.
        </p>
        @if(!empty($returnNotice))
            <p class="text-xs mt-1" style="color:#92400E;" data-testid="marks-return-reason">{{ $returnNotice }}</p>
        @elseif($submission->reopen_reason && ! $submission->isReturned())
            <p class="text-xs mt-1" style="color:#92400E;">Reason given: {{ $submission->reopen_reason }}</p>
        @endif
    </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto bg-white border border-gray-200 rounded-lg shadow-sm p-1">
        <h3 class='text-center py-2 font-semibold '>
            <span>{{ $exam->examType->name ?? 'Exam' }}</span>
            <span>{{ ($exam->academicTerm->name ?? '') . ', ' . ($exam->academicYear->name ?? '') }}</span>
            <span>{{ $exam->subject->name ?? '' }}</span>
            </h3>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200  text-sm">
                <tr>
                    <th class="px-4 py-3 text-left border border-gray-400">No</th>
                    <th class="px-4 py-3 text-left border border-gray-400">Student</th>
                    {{-- <th class="px-4 py-3 text-left border border-gray-400">Subject</th> --}}
                    <th class="px-4 py-3 text-left border border-gray-400">Marks</th>
                    {{-- <th class="px-4 py-3 text-left border border-gray-400">Exam</th> --}}
                    <th class="px-4 py-3 text-left border border-gray-400">Grade</th>
                    <th class="px-4 py-3 text-center border border-gray-400">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse($marks as $mark)
                    <tr class="hover:bg-gray-50 transition">
                        {{-- ID --}}
                        <td class="p-1  border border-gray-400">
                            {{ $loop->iteration }}
                        </td>

                        {{-- Student --}}
                        <td class="p-1 font-medium  border border-gray-400  space-x-2">
                            <span>{{ $mark->student->userprofile->firstname ?? $mark->student->name }}</span>
                            <span>{{ $mark->student->userprofile->lastname ?? "" }}</span>
                        </td>

                        {{-- Subject --}}
                        {{-- <td class="p-1 text-xs  border border-gray-400">
                            {{ $mark->subject->name ?? 'N/A' }}
                        </td> --}}

                        {{-- Marks --}}
                        <td class="p-1 border border-gray-400">
                            <span class="px-2 py-1 text-sm font-semibold rounded-full
                                {{ $mark->marks >= 70 ? 'bg-green-100 text-green-700' : 
                                   ($mark->marks >= 50 ? 'bg-yellow-100 text-yellow-700' : 
                                   'bg-red-100 text-red-700') }}">
                                {{ ceil($mark->marks) }}
                            </span>
                        </td>

                        {{-- Exam --}}
                        {{-- <td class="px-4 text-xs py-3  border border-gray-400">
                            {{ $mark->subject->name . " Exam" ?? 'N/A' }}
                        </td> --}}

                        {{-- Teacher --}}
                        {{-- <td class="p-1 ">
                            {{ $mark->teacher->name ?? 'N/A' }}
                        </td> --}}

                         {{-- grade --}}
                        <td class="p-1  border border-gray-400">
                            {{ $mark->grade ?? 'N/A' }}
                        </td>

                        {{-- Actions --}}
                        <td class="p-1 border border-gray-400 text-center">
                                <a href="{{ route('teacher.student.marks.edit',[ $mark->exam->id, $mark->student_id, $mark->id])}}"
                                   class="px-3 py-1 text-xs font-medium bg-green-500 text-white rounded-md hover:bg-green-600"
                                   aria-label="Correct this student's mark">
                                    Edit
                                </a>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="7" class="text-center py-10">
                            <p class=" text-sm">No marks found</p>
                            {{-- {{ route('teacher.exam.marks.enter', ["exam" => $exam]) }} --}}
                            <a href="{{ route('teacher.exam.marks.enter', ["exam" => $exam]) }}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                                + Add your first record
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex items-center justify-end py-4 text-sm font-semibold ">
            <span>
                {{ 'Tr. ' . ($exam->teacher->name ?? '—') }}
            </span>
    </div>
</div>
@endsection