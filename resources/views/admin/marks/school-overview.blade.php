@extends('layouts.admin.layout')
@section('content')

<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

    @include('layouts.partials.page-header', [
        'title' => 'Filter Marks',
        'subtitle' => 'Select class, year, and term to view student marks.',
    ])

    <div class="bg-white rounded-lg shadow p-6 max-w-lg">
        <form action="{{ route('marks', ['class'=>0,'year'=>0,'term'=>0]) }}" 
              method="GET" 
              onsubmit="event.preventDefault(); 
                       window.location=this.action.replace('/0/year/0/term/0',
                       '/'+document.getElementById('class').value+
                       '/year/'+document.getElementById('year').value+
                       '/term/'+document.getElementById('term').value);">

            <div class="mb-4">
                <label class="ds-label">Class</label>
                <select id="class" class="ds-input">
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="ds-label">Year</label>
                <select id="year" class="ds-input">
                    @foreach($years as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="ds-label">Term</label>
                <select id="term" class="ds-input">
                    @foreach($terms as $term)
                        <option value="{{ $term }}">{{ $term }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="ds-btn ds-btn-primary">
                View Marks
            </button>
        </form>
    </div>
</div>

@endsection
