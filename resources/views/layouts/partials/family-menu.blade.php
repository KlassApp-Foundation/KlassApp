{{-- SPDX-License-Identifier: MIT --}}
{{--
    Parent header: the linked children and their schools.
    A parent may span several schools, so the header shows the KlassApp mark plus
    this menu instead of a single school's logo. Read-only; guarded so a data
    error can never break the header.
--}}
@php
    $children = [];
    try {
        if ($navUser && (int) $navUser->usergroup_id === 7) {
            $listed = app(\App\Services\Parent\ParentPortalService::class)->listChildren($navUser);
            $children = $listed['children'] ?? [];
        }
    } catch (\Throwable $e) {
        $children = [];
    }
@endphp
@if(count($children))
    <details class="parent-family-menu ml-2">
        <summary class="ds-btn ds-btn-sm ds-btn-outline" aria-label="Your children and their schools">
            Children ({{ count($children) }})
        </summary>
        <div class="parent-family-menu__panel">
            @foreach($children as $child)
                <a class="parent-family-menu__item"
                   href="{{ route('parent.dashboard', ['child' => $child['student_id']]) }}"
                   onclick="this.closest('details').removeAttribute('open')">
                    <span class="parent-family-menu__name">{{ ucwords(strtolower($child['name'])) }}</span>
                    <span class="parent-family-menu__school">
                        {{ $child['school_name'] }}@if(!empty($child['class']) && $child['class'] !== 'N/A') &middot; {{ $child['class'] }}@endif
                    </span>
                </a>
            @endforeach
        </div>
    </details>
@endif
