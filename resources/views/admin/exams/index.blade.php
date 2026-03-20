@extends('layouts.admin.layout')

@section('content')
    <div class="container-fluid w-full lg:mx-2"> <!-- or just container mx-auto px-4 sm:px-6 lg:px-8 if no fluid -->
       <div class="">
         {{-- back --}}
         <h1 class="admin-h1 my-3 flex items-center">
        <a href="{{ url('/admin/students') }}" class="rounded-full p-2 bg-gray-100" title="Back">
           <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 492 492" xml:space="preserve" width="512px" height="512px" class="w-3 h-3 fill-current text-gray-700"><g><g><g><path d="M464.344,207.418l0.768,0.168H135.888l103.496-103.724c5.068-5.064,7.848-11.924,7.848-19.124    c0-7.2-2.78-14.012-7.848-19.088L223.28,49.538c-5.064-5.064-11.812-7.864-19.008-7.864c-7.2,0-13.952,2.78-19.016,7.844    L7.844,226.914C2.76,231.998-0.02,238.77,0,245.974c-0.02,7.244,2.76,14.02,7.844,19.096l177.412,177.412    c5.064,5.06,11.812,7.844,19.016,7.844c7.196,0,13.944-2.788,19.008-7.844l16.104-16.112c5.068-5.056,7.848-11.808,7.848-19.008    c0-7.196-2.78-13.592-7.848-18.652L134.72,284.406h329.992c14.828,0,27.288-12.78,27.288-27.6v-22.788    C492,219.198,479.172,207.418,464.344,207.418z" data-original="#000000" fill="" class="active-path"></path></g></g></g></svg>
        </a>
       </div>
        <!-- Page Header / Title Area -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Exams</h1>
                    <h3 class=" text-gray-700 dark:text-gray-400">Create a new exam for your school</h3>
                    
                </div>
              
            </div>
        </div>
          {{-- Flash Success Message --}}
   @include('partials.message')
        <!-- Main Card -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Add New Exam </h2>
            </div>

            <div class="p-6">
                <form action="{{ route('exams.store') }}" method="POST">
                    @csrf

                    <!-- Row 1 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        {{-- <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Exam Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name"
                                   class="tw-form-control w-full"
                                   value="{{ old('name') }}" placeholder="e.g. Term 1 Mid-Term Science" required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div> --}}

                        <div>
                            <label for="exam_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Exam Type
                                {{-- {{ dd($exam_types) }} --}}
                            </label>
                             <select name="exam_type_id" id="exam_type_id" required
                                    class="tw-form-control w-full">
                                <option value="">Select exam type</option>
                                @foreach ($exam_types as $exam_type)

                                     <option value="{{ $exam_type->id }}" {{ old('exam_type') == $exam_type->id ? 'selected' : '' }}>
                                        {{ $exam_type->name }}
                                    </option>
                                @endforeach   
                            </select>
                            @error('exam_type')
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
                                {{-- {{ dd($standards) }} --}}
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
                            <select name="subject_id" class="tw-form-control w-full">
                                <option value="">Select Subject</option>
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
                            <select name="teacher_id" class="tw-form-control w-full">
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