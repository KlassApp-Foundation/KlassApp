{{-- SPDX-License-Identifier: MIT --}}
@if(($rows ?? []) !== [])
    <div class="t-flags" data-testid="checklist-later">
        <p class="t-kicker">To fix later</p>
        @foreach($rows as $later)
            @php
                $parts = ['Tab '.($later['tab'] ?? ''), 'row '.($later['row'] ?? '—'), 'field '.($later['field'] ?? '')];
                if (($later['value'] ?? '') !== '') {
                    $parts[] = 'value '.$later['value'];
                }
                $sentence = implode(', ', $parts).'.';
                if (($later['message'] ?? '') !== '') {
                    $sentence .= ' '.$later['message'];
                }
                if (($later['fix'] ?? '') !== '') {
                    $sentence .= ' Suggested fix: '.$later['fix'];
                }
            @endphp
            <p class="t-later">{{ $sentence }}</p>
        @endforeach
    </div>
@endif
