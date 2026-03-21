@extends('layouts.admin.layout')
@section('content')

<div class="max-w-lg mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Filter Marks</h2>

    <form action="{{ route('marks', ['class'=>0,'year'=>0,'term'=>0]) }}" 
          method="GET" 
          onsubmit="event.preventDefault(); 
                   window.location=this.action.replace('/0/year/0/term/0',
                   '/'+document.getElementById('class').value+
                   '/year/'+document.getElementById('year').value+
                   '/term/'+document.getElementById('term').value);">

        <div class="mb-4">
            <label class="block font-medium">Class</label>
            <select id="class" class="w-full border rounded px-2 py-1">
                @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block font-medium">Year</label>
            <select id="year" class="w-full border rounded px-2 py-1">
                @foreach($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block font-medium">Term</label>
            <select id="term" class="w-full border rounded px-2 py-1">
                @foreach($terms as $term)
                    <option value="{{ $term }}">{{ $term }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
            View Marks
        </button>
    </form>
</div>

@endsection
