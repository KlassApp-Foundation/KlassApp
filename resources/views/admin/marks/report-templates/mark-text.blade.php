{{-- Printed mark. Stored grade stays AB. The card says ABS. --}}
@php
    $printed = '-';
    if ($mark instanceof \App\Models\Academics\Marks) {
        $printed = $mark->displayMark();
    } elseif ($mark instanceof \App\Services\Results\ResultCell) {
        $grade = strtoupper(trim((string) $mark->grade));
        if ($mark->status === \App\Services\Results\ResultCell::ABSENT || in_array($grade, ['AB', 'ABS'], true)) {
            $printed = 'ABS';
        } elseif ($mark->status === \App\Services\Results\ResultCell::MISSING || $mark->mark === null) {
            $printed = 'Missing';
        } elseif ($mark->mark !== null) {
            $printed = (string) (int) floor($mark->mark);
        }
    }
@endphp
{{ $printed }}
