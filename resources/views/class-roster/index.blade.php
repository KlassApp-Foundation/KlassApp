{{-- SPDX-License-Identifier: MIT --}}
{{-- Must extend a role layout that bridges @section('content') → base-content.
     layouts.app only yields base-content, so extending it directly left /admin/classes blank. --}}
@extends(auth()->user()->usergroup_id === 5 ? 'layouts.teacher.layout' : 'layouts.admin.layout')

@section('content')
    @can('full-school-admin')
        @include('admin.classes.partials.unlinked-sections')
    @endcan
    <livewire:class-roster.index />
@endsection
