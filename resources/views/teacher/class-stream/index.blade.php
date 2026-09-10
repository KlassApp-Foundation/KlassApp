{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Class streams</h1>
        <p class="mt-1 text-sm text-gray-600">
            Add a stream to a class you teach, or rename a stream you own.
            Deleting or merging streams stays with school admin.
        </p>
    </div>

    @include('partials.message')

    <div class="bg-white shadow-sm rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">My classes</h2>
            @if($year)
                <p class="text-xs text-gray-500 mt-1">Academic year: {{ $year->name }}</p>
            @endif
        </div>

        <div class="p-6">
            @if (! $year)
                <p class="text-center py-12 text-gray-500" data-testid="ct-streams-no-year">
                    No current academic year is set. Ask your school admin to configure one.
                </p>
            @elseif ($sections->isEmpty())
                <p class="text-center py-12 text-gray-500" data-testid="ct-streams-empty">
                    You are not assigned as class teacher for any class this year.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" data-testid="ct-streams-table">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-200">
                                <th class="py-2 pr-4 font-medium">Class / stream</th>
                                <th class="py-2 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sections as $section)
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 pr-4 text-gray-900 font-medium" data-testid="ct-stream-name-{{ $section->id }}">
                                        {{ $section->name }}
                                    </td>
                                    <td class="py-3 text-right space-x-2">
                                        <a href="{{ route('teacher.class-stream.create', $section) }}"
                                           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-medium"
                                           data-testid="ct-add-stream-{{ $section->id }}">
                                            Add stream
                                        </a>
                                        <a href="{{ route('teacher.class-stream.edit', $section) }}"
                                           class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium"
                                           data-testid="ct-rename-stream-{{ $section->id }}">
                                            Rename
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
