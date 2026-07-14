{{-- @extends('layouts.admin.layout') --}}

{{-- @section('content') --}}
{{-- max-w-2xl mx-auto --}}
<div class=" bg-white p-3 rounded-lg shadow-md text-xs">

    <h2 class="text-sm font-bold mb-2 text-gray-600">Filter Students Marks</h2>

   <form method="GET" action="{{ route('admin.marks.filter') }}" class="flex items-end justify-end gap-2">

    <!-- Term -->
    <div>
        <label for="term" class="block text-gray-700">
            <span>Term</span>
            <span class="text-red-400">*</span>
        </label>
        <select name="term" id="term" class="border-gray-300 rounded-lg p-1" required>
            <option value="">-- Term --</option>
            @forelse ($terms as $term)
                <option value="{{ $term->id }}" @selected(old('term', request("term") === $term->id )) >
                    {{ $term->name }}
                </option>
            @empty
                <option disabled>No terms available</option>
            @endforelse
        </select>
    </div>

    <!-- standard -->
    {{-- <div>
        <label for="standard" class="block  text-gray-700">Level/Standard</label>
        <select name="standard" id="standard" class="border-gray-300 rounded-lg p-1">
            <option value="">-- standard --</option>
            @forelse ($standards as $standard)
                <option value="{{ $standard->id }}" @selected(old("standard", request('standard')) == $standard->id) >
                    {{ $standard->name }}
                </option>
            @empty
                <option disabled>No standards available</option>
            @endforelse
        </select>
    </div> --}}

    <!-- Year -->
    {{-- <div>
        <label for="year" class="block  text-gray-700">Year</label>
        <select name="year" id="year" class="border-gray-300 rounded-lg p-1">
            <option value="">-- Year --</option>
            @forelse ($years as $year)
                <option value="{{ $year->id }}" @selected(old("year", request('year')) == $year->id)>
                    {{ $year->name }}
                </option>
            @empty
                <option disabled>No years available</option>
            @endforelse
        </select>
    </div> --}}

    {{-- class/section --}}
     <div>
        <label for="year" class="block  text-gray-700">
            <span>Class</span>
            <span class="text-red-400">*</span>
        </label>
        <select name="class" id="class" class="border-gray-300 rounded-lg p-1" required>
            <option value="">-- Class --</option>
            @forelse ($classes as $class)
                <option value="{{ $class->id }}" @selected(old("class", request('class')) == $class->id)>
                    {{ $class->name }}
                </option>
            @empty
                <option disabled>No class available</option>
            @endforelse
        </select>
    </div>

    {{-- subject --}}
     {{-- <div>
        <label for="year" class="block  text-gray-700">Subject</label>
        <select name="subject" id="year" class="border-gray-300 rounded-lg p-1">
            <option value="">-- Subject --</option>
            @forelse ($subjects as $subject)
                <option value="{{ $subject->id }}" @selected(old("subject", request('subject')) == $subject->id)>
                    {{ $subject->name }}
                </option>
            @empty
                <option disabled>No subject available</option>
            @endforelse
        </select>
    </div> --}}

     {{-- exam type --}}
     <div>
        <label for="year" class="block  text-gray-700">
            <span>Exam Type</span>
            <span class="text-red-400">*</span> 
        </label>
        <select name="examType" id="year" class="border-gray-300 rounded-lg p-1" >
            <option value="">-- Exam Type --</option>
            @forelse ($examTypes as $examType)
                <option value="{{ $examType->id }}" @selected(old("examType", request('examType')) == $examType->id)>
                    {{ $examType->name }}
                </option>
            @empty
                <option disabled>No exam type available</option>
            @endforelse
        </select>
    </div>

    <button type="submit" class="ds-btn ds-btn-primary text-sm">
       Apply Filter
    </button>

   @if ($students && $students->isNotEmpty())
        <a href="{{ route('admin.marksheet.download', request()->query()) }}"
   class="ds-btn ds-btn-primary text-sm">
    Download PDF
</a>
   @endif
@csrf
</form>

</div>
{{-- @endsection --}}