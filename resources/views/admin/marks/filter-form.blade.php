{{-- @extends('layouts.admin.layout') --}}

{{-- @section('content') --}}
{{-- max-w-2xl mx-auto --}}
<div class=" bg-white p-3 rounded-lg shadow-md">

    <h2 class="text-lg font-bold mb-2 text-gray-600">Filter Students Marks</h2>

   <form method="GET" action="{{ route('admin.marks.filter') }}" class="flex items-end justify-end gap-4">

    <!-- Term -->
    <div>
        <label for="term" class="block text-sm text-gray-700">Term</label>
        <select name="term" id="term" class="border-gray-300 rounded-lg p-1">
            <option value="">-- Select Term --</option>
            @forelse ($terms as $term)
                <option value="{{ $term }}" @selected(old('term', request('term')) == $term) >
                    {{ $term }}
                </option>
            @empty
                <option disabled>No terms available</option>
            @endforelse
        </select>
    </div>

    <!-- standard -->
    <div>
        <label for="standard" class="block text-sm text-gray-700">Level/Standard</label>
        <select name="standard" id="standard" class="border-gray-300 rounded-lg p-1">
            <option value="">-- Select standard --</option>
            @forelse ($standards as $standard)
                <option value="{{ $standard->id }}" @selected(old("standard", request('standard')) == $standard->id) >
                    {{ $standard->name }}
                </option>
            @empty
                <option disabled>No standards available</option>
            @endforelse
        </select>
    </div>

    <!-- Year -->
    <div>
        <label for="year" class="block text-sm text-gray-700">Year</label>
        <select name="year" id="year" class="border-gray-300 rounded-lg p-1">
            <option value="">-- Select Year --</option>
            @forelse ($years as $year)
                <option value="{{ $year->id }}" @selected(old("year", request('year')) == $year->id)>
                    {{ $year->name }}
                </option>
            @empty
                <option disabled>No years available</option>
            @endforelse
        </select>
    </div>

    {{-- subject --}}
     <div>
        <label for="year" class="block text-sm text-gray-700">Subject</label>
        <select name="subject" id="year" class="border-gray-300 rounded-lg p-1">
            <option value="">-- Select Subject --</option>
            @forelse ($subjects as $subject)
                <option value="{{ $subject->id }}" @selected(old("subject", request('subject')) == $subject->id)>
                    {{ $subject->name }}
                </option>
            @empty
                <option disabled>No subject available</option>
            @endforelse
        </select>
    </div>

    <button type="submit" class="bg-blue-600 text-white p-1 font-semibold rounded-lg">
       Apply Filter
    </button>
@csrf
</form>

</div>
{{-- @endsection --}}