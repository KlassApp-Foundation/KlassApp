{{-- SPDX-License-Identifier: MIT --}}
@php
    $children = $children ?? [];
    $selectedId = (int) ($selectedChild['student_id'] ?? 0);
    $colors = ['#1E6FD9', '#B45309', '#15803D', '#7C3AED'];
@endphp
@if(count($children) > 0)
    <div class="family-child-pills" role="radiogroup" aria-label="Child" data-testid="parent-child-selector">
        @foreach($children as $child)
            @php
                $active = (int) $child['student_id'] === $selectedId;
                $color = $colors[((int) $child['student_id']) % count($colors)];
            @endphp
            <a href="{{ route('parent.dashboard', ['child' => $child['student_id']]) }}"
               role="radio"
               aria-checked="{{ $active ? 'true' : 'false' }}"
               class="family-child-pill {{ $active ? 'is-active' : '' }}"
               data-testid="child-chip-{{ $child['student_id'] }}">
                <span class="family-child-pill__avatar" style="background: {{ $color }};">{{ $child['initials'] }}</span>
                <span class="family-child-pill__text">
                    <strong>{{ $child['name'] }}</strong>
                    <span>{{ $child['class'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
    <div class="family-child-select-wrap">
        <label for="family-child" class="family-child-select-label">Child</label>
        <select id="family-child" class="family-child-select" onchange="if (this.value) window.location = this.value">
            @foreach($children as $child)
                <option value="{{ route('parent.dashboard', ['child' => $child['student_id']]) }}" @selected((int) $child['student_id'] === $selectedId)>
                    {{ $child['name'] }} · {{ $child['class'] }}
                </option>
            @endforeach
        </select>
    </div>
@endif
