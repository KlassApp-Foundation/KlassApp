{{-- SPDX-License-Identifier: MIT --}}
@php
    $selected = $grades['selected_year'] ?? 'all';
    $years = $grades['years'] ?? [];
@endphp
<form method="get" action="{{ url()->current() }}" class="mt-4 flex flex-wrap items-center gap-3" data-testid="results-year-filter">
    <label for="results-year" class="text-sm font-semibold" style="color: var(--d-text);">Year</label>
    <select id="results-year" name="year" class="border border-slate-300 rounded-md px-3 py-2 text-sm bg-white" style="color: #1E293B;" onchange="this.form.submit()">
        <option value="all" @selected($selected === 'all')>All years</option>
        @foreach ($years as $year)
            <option value="{{ $year['id'] }}" @selected((string) $selected === (string) $year['id'])>
                {{ $year['name'] }}@if(!empty($year['current'])) (current)@endif
            </option>
        @endforeach
    </select>
</form>
