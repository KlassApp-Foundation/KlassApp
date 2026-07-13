@props([
    'headers' => [],
    'striped' => false,
    'hover' => true,
    'density' => 'comfortable', // comfortable | compact
    'selectable' => false,
    'sortable' => false,
    'class' => '',
])

@php
    $densityClass = $density === 'compact' ? 'dt-compact' : 'dt-comfortable';
    $classes = 'ds-table-ledger ' . $densityClass . ' ' . $class;
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
