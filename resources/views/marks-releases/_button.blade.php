{{-- SPDX-License-Identifier: MIT --}}
{{-- "Release marks" link, shown only to someone who may release (school admin, class teacher). --}}
@php
    $mrUser = auth()->user();
    $mrCan = $mrUser && app(\App\Services\MarksRelease\MarksReleaseService::class)->canRelease($mrUser);
@endphp
@if($mrCan)
    <a href="{{ route($area.'.marks.releases.create') }}" class="{{ $class ?? '' }}" @isset($style) style="{{ $style }}" @endisset data-testid="release-marks-button">Release marks</a>
@endif
