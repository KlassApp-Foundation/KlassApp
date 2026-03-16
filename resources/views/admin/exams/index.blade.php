@extends('layouts.admin.layout')

@section('content')
    <div class="container-fluid px-6 py-8"> <!-- or just container mx-auto px-4 sm:px-6 lg:px-8 if no fluid -->

        <!-- Page Header / Title Area -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Exams</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Create a new exam for your school</p>
                </div>
              
            </div>
        </div>

        <!-- Main Card -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Add New Exam</h2>
            </div>

            <div class="p-6">
                <form action="{{ route('exams.store') }}" method="POST">
                    @csrf

                    <!-- Row 1 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Exam Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name"
                                   class="tw-form-control w-full"
                                   value="{{ old('name') }}" placeholder="e.g. Term 1 Mid-Term Science" required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Exam Type
                            </label>
                            <input type="text" name="type" id="type"
                                   class="tw-form-control w-full"
                                   value="{{ old('type') }}" placeholder="e.g. Mid-Term, Mock, UNEB Practice">
                            @error('type')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Row 2 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="academic_year_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Academic Year <span class="text-red-500">*</span>
                            </label>
                            <select name="academic_year_id" id="academic_year_id" required
                                    class="tw-form-control w-full">
                                <option value="">Select Year</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                                        {{ $year->name ?? $year->start_year . ' - ' . $year->end_year }}
                                    </option>
                                @endforeach
                            </select>
                            @error('academic_year_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="standard_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Class/Standard <span class="text-red-500">*</span>
                            </label>
                            <select name="standard_id" id="standard_id" required
                                    class="tw-form-control w-full">
                                <option value="">Select Class</option>
                                @foreach($standards as $std)
                                    <option value="{{ $std->id }}" {{ old('standard_id') == $std->id ? 'selected' : '' }}>
                                        {{ $std->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('standard_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Row 3 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div>
                            <label for="term" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Term <span class="text-red-500">*</span>
                            </label>
                            <select name="term" id="term" required
                                    class="tw-form-control w-full">
                                <option value="">Select Term</option>
                                <option value="1" {{ old('term') == '1' ? 'selected' : '' }}>Term 1</option>
                                <option value="2" {{ old('term') == '2' ? 'selected' : '' }}>Term 2</option>
                                <option value="3" {{ old('term') == '3' ? 'selected' : '' }}>Term 3</option>
                            </select>
                            @error('term')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="subject_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Subject (optional)
                            </label>
                            <select class="tw-form-control w-full">
                                <option value="">Whole Class Exam</option>
                                @foreach($subjects as $sub)
                                    <option value="{{ $sub->id }}" {{ old('subject_id') == $sub->id ? 'selected' : '' }}>
                                        {{ $sub->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Assigned Teacher (optional)
                            </label>
                            <select class="tw-form-control w-full">
                                <option value="">Select Teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-4">
                        
                        <button class="py-1 px-2 rounded text-white bg-red-500">
                            <a href="{{ route('exams.index') }}"
                           class="">
                            Cancel
                        </a>
                        </button>
                        <button type="submit" class="py-1 px-2 rounded text-white bg-green-500">
                            Create Exam
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection