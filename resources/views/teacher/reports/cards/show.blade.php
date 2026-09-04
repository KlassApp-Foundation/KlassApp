@extends('layouts.teacher.layout')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('teacher.reports.cards.index') }}"
           class="rounded-full p-3 bg-gray-100 hover:bg-gray-200 transition-colors"
           title="Back to classes">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                {{ $stdLink->section->name ?? 'Class' }}
                @if ($stdLink->stream)
                    <span class="text-gray-500 font-normal text-lg">({{ $stdLink->stream }})</span>
                @endif
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                Subjects with marks: {{ $subjectsFilled }} / {{ $subjectsTotal }}
            </p>
        </div>
    </div>

    @include('partials.message')

    <div class="bg-white shadow-sm rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Students</h2>
        </div>

        <div class="p-6">
            @if (! $hasExam)
                <p class="text-center py-12 text-gray-500">No EOT exam found for this class yet.</p>
            @elseif ($students->isEmpty())
                <p class="text-center py-12 text-gray-500">No students with marks on the report exam yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-200">
                                <th class="py-2 pr-4 font-medium">Student</th>
                                <th class="py-2 font-medium text-right">Report card</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $student)
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 pr-4 text-gray-900 font-medium">{{ $student->displayName ?: $student->name }}</td>
                                    <td class="py-3 text-right space-x-2">
                                        <a href="{{ route('teacher.reports.cards.student.preview', [$stdLink, $student]) }}"
                                           target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">
                                            Preview
                                        </a>
                                        <a href="{{ route('teacher.reports.cards.student.download', [$stdLink, $student]) }}"
                                           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-medium">
                                            Download
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
