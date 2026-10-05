@once<link rel="stylesheet" href="{{ asset('css/marks-import.css') }}">@endonce
{{-- Table of import rows. Needs $rows; optional $showExisting (bool), $showStatus (bool). Restacks as cards below 768px via data-label. --}}
<div class="ds-table-wrap">
    <table class="ds-table-ledger ds-table-card-mobile marks-import-table">
        <thead>
            <tr>
                <th scope="col">Row</th>
                <th scope="col">Admission No</th>
                <th scope="col">Student</th>
                <th scope="col">Marks</th>
                @if(!empty($showExisting)) <th scope="col">Saved now</th> @endif
                <th scope="col">{{ !empty($showStatus) ? 'Result' : 'Outcome' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td data-label="Row">{{ $r['row'] }}</td>
                    <td data-label="Admission No">{{ $r['identifier'] ?? '—' }}</td>
                    <td data-label="Student">{{ $r['student_name'] ?? $r['name'] ?? '—' }}</td>
                    <td data-label="Marks">{{ $r['mark'] ?? ($r['raw_mark'] ?? '—') }}</td>
                    @if(!empty($showExisting)) <td data-label="Saved now">{{ $r['existing'] ?? '—' }}</td> @endif
                    <td data-label="Outcome">
                        @switch($r['outcome'])
                            @case('new') <span class="ds-badge ds-badge-active ds-badge-sm">Will be saved</span> @break
                            @case('saved') <span class="ds-badge ds-badge-active ds-badge-sm">Saved</span> @break
                            @case('update') <span class="ds-badge ds-badge-warning ds-badge-sm">Differs from saved mark</span> @break
                            @case('updated') <span class="ds-badge ds-badge-active ds-badge-sm">Updated</span> @break
                            @case('unchanged') <span class="ds-badge ds-badge-info ds-badge-sm">Already saved, no change</span> @break
                            @default <span class="ds-badge ds-badge-rejected ds-badge-sm">Skipped</span>
                                <span class="block text-sm text-gray-800">{{ $r['message'] ?? ($reasons[$r['reason']] ?? $r['reason']) }}</span>
                        @endswitch
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
