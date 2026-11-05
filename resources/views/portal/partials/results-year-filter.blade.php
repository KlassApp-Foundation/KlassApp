{{-- SPDX-License-Identifier: MIT --}}
@php
    $selected = $grades['selected_year'] ?? 'all';
    $years = $grades['years'] ?? [];
@endphp
<div class="family-filters" data-testid="results-year-filter">
    <button type="button" class="family-filters-open ds-btn ds-btn-outline" data-testid="family-filters-open" aria-haspopup="dialog" aria-controls="family-filters-sheet">Filters</button>
    <form method="get" action="{{ url()->current() }}" id="family-filters-sheet" class="family-filters-sheet" role="dialog" aria-label="Filters">
        <div class="family-filters-sheet__bar">
            <strong>Filters</strong>
            <button type="button" class="family-filters-close" data-family-filters-close>Close</button>
        </div>
        <label for="results-year">Year</label>
        <select id="results-year" name="year" class="family-child-select" onchange="this.form.submit()">
            <option value="all" @selected($selected === 'all')>All years</option>
            @foreach ($years as $year)
                <option value="{{ $year['id'] }}" @selected((string) $selected === (string) $year['id'])>
                    {{ $year['name'] }}@if(!empty($year['current'])) (current)@endif
                </option>
            @endforeach
        </select>
    </form>
</div>
<script>
document.addEventListener('click', function (event) {
    var open = event.target.closest && event.target.closest('[data-testid="family-filters-open"]');
    var close = event.target.closest && event.target.closest('[data-family-filters-close]');
    var sheet = document.getElementById('family-filters-sheet');
    if (!sheet) return;
    if (open) sheet.classList.add('is-open');
    if (close) sheet.classList.remove('is-open');
});
</script>
