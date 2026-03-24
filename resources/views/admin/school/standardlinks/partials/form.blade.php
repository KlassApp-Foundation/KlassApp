
<div class="bg-white p-4 shadow rounded w-full max-w-2xl">
    {{-- STANDARD --}}
    
    <div class="mb-3">
        <label class="block text-sm font-medium">
            Standard <span class="text-red-500">*</span>
        </label>
        <select name="standard_id" class="w-full border p-2 rounded">
            <option value="">Select Standard</option>
            @foreach($standardlist as $standard)
                <option value="{{ $standard->id }}">
                    {{ $standard->name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- SECTION --}}
    <div class="mb-3">
        <label class="block text-sm font-medium">
            Section <span class="text-red-500">*</span>
        </label>
        <select name="section_id" class="w-full border p-2 rounded">
            <option value="">Select Section</option>
            @foreach($sectionlist as $section)
                <option value="{{ $section->id }}">
                    {{ $section->name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- CLASS TEACHER --}}
    <div class="mb-3">
        <label class="block text-sm font-medium">
            Class Teacher
        </label>
        <select name="class_teacher_id" class="w-full border p-2 rounded">
            <option value="">Select Teacher</option>
            @foreach($teacherlist as $teacher)
                <option value="{{ $teacher->id }}">
                    {{ $teacher->fullname }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- SUBJECTS --}}
    <div class="mb-3">
        <label class="block text-sm font-medium">
            Subjects
        </label>

        @foreach($subjectlist as $standardId => $sections)
            @foreach($sections as $sectionId => $subjects)
                <div class="mb-2">
                    @foreach($subjects as $subject)
                        <label class="inline-flex items-center mr-3">
                            <input type="checkbox" name="subjects[]" value="{{ $subject->id }}">
                            <span class="ml-1">{{ $subject->name }}</span>
                        </label>
                    @endforeach
                </div>
            @endforeach
        @endforeach
    </div>

    {{-- SUBMIT --}}
    <div class="mt-4">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
            Save
        </button>
    </div>

</div>