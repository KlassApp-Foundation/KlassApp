@props([
    'headers' => [],
    'striped' => false,
    'hover' => false,
    'class' => '',
])

@php
    $stripedClass = $striped ? 'ds-table-striped' : '';
    $hoverClass = $hover ? 'ds-table-hover' : '';
    $classes = 'ds-table ' . $stripedClass . ' ' . $hoverClass . ' ' . $class;
@endphp

<div class="ds-table-wrap">
    <table class="{{ $classes }}">
        @if(count($headers) > 0)
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
