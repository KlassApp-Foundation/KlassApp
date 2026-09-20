@extends('layouts.teacher.layout')

@section('content')
<div class="container-fluid w-full lg:mx-2">

    {{-- Flash Message --}}
    @include('partials.message')

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Edit Marks
        </h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ $exam->examType->name }} • {{$exam->subject->name ?? '' }}
        </p>
    </div>

    <!-- Card -->
    <div class="max-w-2xl bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">

        <!-- Card Header -->
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                Marks for {{ $marks->student->name ?? 'Student' }}
            </h2>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('teacher.marks.update', [
            'exam' => $exam->id,
            'student' => $student->id
        ]) }}">
            @csrf
            @method('PATCH')

            <div class="p-6 space-y-6">

                <!-- Marks -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Marks (out of 100) 
                    </label>
                    <input type="number"
                           name="marks"
                           value="{{ old('marks', $marks->marks ?? 0) }}"
                           min="0"
                           max="100"
                           class="tw-form-control w-24">

                    @error('marks')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                @if($correctionReasonRequired ?? false)
                <!-- Correction reason: these marks were already submitted -->
                <div>
                    <label for="correction_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Reason for correcting submitted marks <span class="text-red-500">*</span>
                    </label>
                    <textarea id="correction_reason"
                              name="correction_reason"
                              rows="3"
                              minlength="10"
                              maxlength="500"
                              required
                              class="tw-form-control w-full"
                              placeholder="e.g. Mis-keyed mark — the script shows 85, not 72.">{{ old('correction_reason') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        Recorded in the marks audit log and sent to your school admin.
                    </p>
                    @error('correction_reason')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">

                <!-- Back Button -->
                <a href="{{ route('teacher.exam.marks.view', $exam->id) }}"
                   class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                    ⬅ Back
                </a>

                <!-- Submit -->
                <button type="submit"
                        class="py-2 px-5 rounded-lg text-white bg-green-500 hover:bg-green-600 transition">
                    Save Marks
                </button>

            </div>

        </form>

    </div>

</div>
@endsection