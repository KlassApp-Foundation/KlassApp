@extends('layouts.teacher.layout')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('teacher.exam.marks') }}"
           class="rounded-full p-3 bg-gray-100 hover:bg-gray-200 transition-colors"
           title="Back to exams">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">{{ $exam ? 'Edit Exam' : 'Create Exam' }}</h1>
    </div>

    @include('partials.message')

    <div class="bg-white shadow-sm rounded-2xl border border-gray-200 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">
            <div>
                <span class="text-lg font-semibold text-gray-800">{{ $exam ? 'Update class exam' : 'New class exam' }}</span>
                <p class="text-xs text-gray-500 mt-1">Only your class teacher sections are listed.</p>
            </div>
            @unless($exam)
                <form action="{{ route('teacher.exams.create') }}" method="GET">
                    <select name="section" id="section" class="p-2 rounded border border-gray-300 text-sm" onchange="this.form.submit()">
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((int) $selectedSectionId === (int) $section->id)>
                                {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endunless
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

            <form action="{{ $exam ? route('teacher.exams.update', $exam) : route('teacher.exams.store') }}" method="POST">
                @csrf
                @if ($exam)
                    @method('PUT')
                @endif

                <input type="hidden" name="section_id" value="{{ old('section_id', $selectedSectionId) }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Academic Year <span class="text-red-500">*</span>
                        </label>
                        <select name="academic_year_id" id="academic_year_id" required class="tw-form-control w-full">
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}"
                                    @selected((int) old('academic_year_id', optional($exam)->academic_year_id ?? $currentYearId) === (int) $year->id)>
                                    {{ $year->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="academic_term_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Term <span class="text-red-500">*</span>
                        </label>
                        <select name="academic_term_id" id="academic_term_id" required class="tw-form-control w-full">
                            <option value="">Select Term</option>
                            @foreach ($terms as $term)
                                <option value="{{ $term->id }}"
                                    @selected((int) old('academic_term_id', optional($exam)->academic_term_id) === (int) $term->id)>
                                    {{ $term->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-1">
                            Scheduled Date &amp; Time
                        </label>
                        <input type="datetime-local"
                               name="scheduled_at"
                               id="scheduled_at"
                               value="{{ old('scheduled_at', optional($exam)->scheduled_at ? \Carbon\Carbon::parse($exam->scheduled_at)->format('Y-m-d\TH:i') : '') }}"
                               class="tw-form-control w-full" />
                    </div>

                    <div>
                        <label for="subject_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Subject <span class="text-red-500">*</span>
                        </label>
                        <select name="subject_id" id="subject_id" required class="tw-form-control w-full">
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}"
                                    @selected((int) old('subject_id', optional($exam)->subject_id) === (int) $subject->id)>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="exam_type_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Exam Type <span class="text-red-500">*</span>
                        </label>
                        <select name="exam_type_id" id="exam_type_id" required class="tw-form-control w-full">
                            <option value="">Select Exam Type</option>
                            @foreach($examTypes as $examType)
                                <option value="{{ $examType->id }}"
                                    @selected((int) old('exam_type_id', optional($exam)->exam_type_id) === (int) $examType->id)>
                                    {{ $examType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="teacher_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Assigned Teacher
                        </label>
                        <select name="teacher_id" id="teacher_id" class="tw-form-control w-full">
                            @foreach($teachers as $t)
                                @php
                                    $profile = $t->userprofile;
                                    $label = $profile
                                        ? trim(($profile->firstname ?? '') . ' ' . ($profile->lastname ?? ''))
                                        : $t->name;
                                    if ($label === '') {
                                        $label = $t->email;
                                    }
                                @endphp
                                <option value="{{ $t->id }}"
                                    @selected((int) old('teacher_id', optional($exam)->teacher_id ?? $defaultTeacherId) === (int) $t->id)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Defaults to the subject teacher for this class when linked; otherwise you.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4 mt-10">
                    <a href="{{ route('teacher.exam.marks') }}"
                       class="px-6 py-3 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-8 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-medium">
                        {{ $exam ? 'Update Exam' : 'Create Exam' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
