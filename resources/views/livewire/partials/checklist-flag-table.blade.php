{{-- SPDX-License-Identifier: MIT --}}
@if(($rows ?? []) !== [])
    <div class="t-flags" data-testid="{{ $testId ?? 'checklist-flag-table' }}">
        @if(($title ?? '') !== '')
            <p class="t-kicker">{{ $title }}</p>
        @endif
        @if(($note ?? '') !== '')
            <p class="t-flag-note" data-testid="checklist-flag-guidance">{{ $note }}</p>
        @endif
        <table class="t-flag-table">
            <thead>
                <tr>
                    <th>Tab · Row</th>
                    <th>Field</th>
                    <th>Value</th>
                    <th>Problem</th>
                    <th>Suggested fix</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $flag)
                    @php
                        $tab = trim((string) ($flag['tab'] ?? ''));
                        $rowNo = ($flag['row'] ?? null) === null || $flag['row'] === '' ? '—' : (string) $flag['row'];
                        $where = $tab === '' ? $rowNo : $tab.' · '.$rowNo;
                    @endphp
                    <tr wire:key="flag-{{ $tab }}-{{ $rowNo }}-{{ $loop->index }}" data-testid="checklist-flag" data-severity="{{ $flag['severity'] ?? 'minor' }}">
                        <td data-label="Tab · Row">{{ $where }}</td>
                        <td data-label="Field">{{ $flag['field'] ?? '' }}</td>
                        <td data-label="Value">{{ $flag['value'] ?? '' }}</td>
                        <td data-label="Problem">{{ $flag['message'] ?? '' }}</td>
                        <td data-label="Suggested fix">{{ $flag['fix'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
