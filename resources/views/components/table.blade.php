{{--
  Row hover is intrinsic to .ds-table-ledger (dashboard-refresh.css) and is
  always on, for both this component and the raw `<table class="ds-table-ledger">`
  markup used elsewhere. There is deliberately no `hover` prop: it would have
  nothing to toggle. The prop that used to exist here emitted no class at all.
--}}
@props([
    'headers' => [],
    'striped' => false,
    'density' => 'comfortable', // comfortable | compact
    'selectable' => false,
    'sortable' => false,
    'cardMobile' => true, // stacked-card layout on mobile ≤767px
    'class' => '',
])

@php
    $densityClass = $density === 'compact' ? 'dt-compact' : 'dt-comfortable';
    $cardMobileClass = $cardMobile ? 'ds-table-card-mobile' : '';
    $stripedClass = $striped ? 'ds-table-striped' : '';
    $classes = 'ds-table-ledger ' . $densityClass . ' ' . $cardMobileClass . ' ' . $stripedClass . ' ' . $class;
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
