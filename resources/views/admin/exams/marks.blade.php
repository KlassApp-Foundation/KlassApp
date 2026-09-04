@extends('layouts.admin.layout')

@section('content')
    <div class="container-fluid px-6 py-8">

        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Marks Entry</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ $exam->name }} • {{ $standard->name ?? 'Class' }} • {{ $subject ? $subject->name : 'Whole Class' }}
                    </p>
                </div>
                <a href="{{ route('marks.view', $standard->id ?? '') }}"
                   class="py-1 px-3 rounded text-white bg-red-500 hover:bg-red-600">
                    Cancel / Back
                </a>
            </div>
        </div>

        <!-- Main Card -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Enter Marks for {{ $exam->name }}
                </h2>
            </div>

            <div class="p-6">
                <form action="{{ route('marks.save', $exam->id) }}" method="POST">
                    @csrf

                    <!-- Hidden context -->
                    <input type="hidden" name="exam_id" value="{{ $exam->id }}">
                    @if($subject)
                        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                    @endif
                    <input type="hidden" name="standard_id" value="{{ $standard->id ?? '' }}">

                    <!-- Students Table - Bulk Entry -->
                    <div class="overflow-x-auto mb-8">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Student</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Adm No.</th>
                                    <th class="px-4 py-3 text-center text-sm font-medium text-gray-700 dark:text-gray-300">Marks <span class="text-red-500">*</span></th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Comment (optional)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($students as $student)
                                    <tr>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $student->displayName ?: $student->name }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                            {{ $student->registration_number ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <input type="number" min="0" max="100" step="0.5"
                                                   name="marks[{{ $student->id }}]"
                                                   value="{{ old("marks.{$student->id}", $existing[$student->id]['marks'] ?? '') }}"
                                                   class="tw-form-control w-28 text-center mx-auto @error("marks.{$student->id}") border-red-500 @enderror"
                                                   required>
                                            @error("marks.{$student->id}")
                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-4">
                                            <input type="text"
                                                   name="comments[{{ $student->id }}]"
                                                   value="{{ old("comments.{$student->id}", $existing[$student->id]['comment'] ?? '') }}"
                                                   class="tw-form-control w-full"
                                                   placeholder="e.g. Excellent effort">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No students found for this class.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('marks.view', $standard->id ?? '') }}"
                           class="py-1 px-3 rounded text-white bg-red-500 hover:bg-red-600">
                            Cancel
                        </a>
                        <button type="submit"
                                class="py-1 px-3 rounded text-white bg-green-500 hover:bg-green-600">
                            Save Marks
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection