@props([
    'headers' => [],
    'striped' => false,
    'hover' => true,
    'density' => 'comfortable', // comfortable | compact
    'selectable' => false,
    'sortable' => false,
    'cardMobile' => true, // stacked-card layout on mobile ≤767px
    'class' => '',
])

@php
    $densityClass = $density === 'compact' ? 'dt-compact' : 'dt-comfortable';
    $cardMobileClass = $cardMobile ? 'ds-table-card-mobile' : '';
    $classes = 'ds-table-ledger ' . $densityClass . ' ' . $cardMobileClass . ' ' . $class;
@endphp

<div class="ds-table-wrap">
    <table class="{{ $classes }}">
        @if(count($headers) > 0)
            <thead>
                <tr>
                    @if($selectable)
                        <th class="dt-cell-check" style="cursor: default;">
                            <input type="checkbox" class="dt-checkbox" id="select-all">
                        </th>
                    @endif
                    @foreach($headers as $header)
                        <th>
                            {{ $header }}
                            @if($sortable)
                                <span class="dt-sort-arrow">&#x25B4;</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
