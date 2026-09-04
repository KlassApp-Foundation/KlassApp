{{-- SPDX-License-Identifier: MIT --}}
{{-- Same layout bridge as class-roster.index — do not extend layouts.app directly. --}}
@extends(auth()->user()->usergroup_id === 5 ? 'layouts.teacher.layout' : 'layouts.admin.layout')

@section('content')
    <livewire:class-roster.show
        :section-id="$sectionId"
        :academic-year-id="$academicYearId"
    />
@endsection
