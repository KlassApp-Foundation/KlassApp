@extends('layouts.admin.layout')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        {{-- Back Button + Title --}}
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ url('/admin/students') }}" 
               class="rounded-full p-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors"
               title="Back">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Exams</h1>
        </div>

        @include('partials.message')

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">

            <!-- Header -->
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Create / Edit Exam</h2>
            </div>

            <div class="p-6">

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ $exam ? route('admin.exams.update', $exam->id) : route('admin.exams.store') }}" 
                      method="POST">
                    @csrf
                    @if ($exam)
                        @method('PUT')
                    @endif

                    <input type="hidden" name="section_id" value="{{ request('section') }}">

                    <!-- 2-Column Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Scheduled Date & Time -->
                        <div>
                            <label for="scheduled_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Scheduled Date & Time <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" 
                                   name="scheduled_at" 
                                   id="scheduled_at"
                                   value="{{ old('scheduled_at', optional($exam)->scheduled_at ? \Carbon\Carbon::parse($exam->scheduled_at)->format('Y-m-d\TH:i') : '') }}"
                                   class="tw-form-control w-full" />
                            @error('scheduled_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Academic Year -->
                        <div>
                            <label for="academic_year_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Academic Year <span class="text-red-500">*</span>
                            </label>
                            <select name="academic_year_id" id="academic_year_id" required class="tw-form-control w-full">
                                <option value="">Select Year</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" 
                                        {{ old('academic_year_id', optional($exam)->academic_year_id) == $year->id ? 'selected' : '' }}>
                                        {{ $year->name ?? $year->start_year . ' - ' . $year->end_year }}
                                    </option>
                                @endforeach
                            </select>
                            @error('academic_year_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Subject -->
                        <div>
                            <label for="subject_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Subject (optional)
                            </label>
                            <select name="subject_id" class="tw-form-control w-full">
                                <option value="">Select Subject</option>
                                @foreach($subjects as $sub)
                                    <option value="{{ $sub->id }}"
                                        {{ old('subject_id', optional($exam)->subject_id) == $sub->id ? 'selected' : '' }}>
                                        {{ $sub->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Exam Type -->
                        <div>
                            <label for="exam_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Exam Type <span class="text-red-500">*</span>
                            </label>
                            <select name="exam_type_id" id="exam_type_id" required class="tw-form-control w-full">
                                <option value="">Select exam type</option>
                                @foreach ($exam_types as $exam_type)
                                    <option value="{{ $exam_type->id }}" 
                                        {{ old('exam_type_id', optional($exam)->exam_type_id) == $exam_type->id ? 'selected' : '' }}>
                                        {{ $exam_type->name }}
                                    </option>
                                @endforeach   
                            </select>
                            @error('exam_type_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Level (Standard) -->
                        <div>
                            <label for="standard_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Level <span class="text-red-500">*</span>
                            </label>
                            <select name="standard_id" id="standard_id" required class="tw-form-control w-full">
                                <option value="">Select Level</option>
                                @foreach($standards as $std)
                                    <option value="{{ $std->id }}"
                                        {{ old('standard_id', optional($exam)->standard_id) == $std->id ? 'selected' : '' }}>
                                        {{ $std->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('standard_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Term -->
                        <div>
                            <label for="academic_term_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Term <span class="text-red-500">*</span>
                            </label>
                            <select name="academic_term_id" id="academic_term_id" required class="tw-form-control w-full">
                                <option value="">Select Term</option>
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}"
                                        {{ old('academic_term_id', optional($exam)->academic_term_id) == $term->id ? 'selected' : '' }}>
                                        {{ $term->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('academic_term_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Assigned Teacher -->
                        <div class="">
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Assigned Teacher
                            </label>
                            <select name="teacher_id" class="tw-form-control w-full">
                                <option value="">Select Teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" 
                                        {{ old('teacher_id', optional($exam)->teacher_id) == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-4 mt-10">
                        <a href="{{ route('admin.exams') }}" 
                           class="px-6 py-3 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-8 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-medium transition-colors">
                            {{ $exam ? 'Update Exam' : 'Create Exam' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection