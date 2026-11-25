{{-- Printed mark. A missing mark and a legacy absent row both print a dash. --}}
@php
    $printed = '–';
    if ($mark instanceof \App\Models\Academics\Marks) {
        $printed = $mark->displayMark();
    } elseif ($mark instanceof \App\Services\Results\ResultCell) {
        $grade = strtoupper(trim((string) $mark->grade));
        if ($mark->status === \App\Services\Results\ResultCell::ABSENT || in_array($grade, ['AB', 'ABS'], true)) {
            $printed = \App\Services\Results\ResultCell::DASH;
        } elseif ($mark->status === \App\Services\Results\ResultCell::MISSING || $mark->mark === null) {
            $printed = \App\Services\Results\ResultCell::DASH;
        } elseif ($mark->mark !== null) {
            $printed = $mark->sheetLabel();
        }
    }
@endphp
{{ $printed }}
