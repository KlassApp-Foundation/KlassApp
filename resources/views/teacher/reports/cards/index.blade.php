@extends('layouts.teacher.layout')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Report Cards</h1>
        <p class="mt-1 text-sm text-gray-600">Generate report cards for your class teacher streams only.</p>
    </div>

    @include('partials.message')

    <div class="bg-white shadow-sm rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">My classes</h2>
        </div>

        <div class="p-6">
            @if ($rows->isEmpty())
                <p class="text-center py-12 text-gray-500">You are not assigned as class teacher for any stream this year.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-200">
                                <th class="py-2 pr-4 font-medium">Class</th>
                                <th class="py-2 pr-4 font-medium">Students with marks</th>
                                <th class="py-2 pr-4 font-medium">Subjects with marks</th>
                                <th class="py-2 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php $sl = $row['stdLink']; @endphp
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 pr-4 text-gray-900 font-medium">
                                        {{ $sl->section->name ?? 'Class' }}
                                        @if ($sl->stream)
                                            <span class="text-gray-500 font-normal">({{ $sl->stream }})</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4 text-gray-700">
                                        @if ($row['hasExam'])
                                            {{ $row['studentCount'] }}
                                        @else
                                            <span class="text-gray-400">No EOT exam</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4 text-gray-700">
                                        {{ $row['subjectsFilled'] }} / {{ $row['subjectsTotal'] }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <a href="{{ route('teacher.reports.cards.show', $sl) }}"
                                           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-medium">
                                            Open
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
