@extends('layouts.app')

@section('content')
    <livewire:class-roster.show
        :section-id="$sectionId"
        :academic-year-id="$academicYearId"
    />
@endsection
