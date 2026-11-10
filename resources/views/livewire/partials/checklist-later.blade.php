{{-- SPDX-License-Identifier: MIT --}}
@include('livewire.partials.checklist-flag-table', [
    'rows' => $rows ?? [],
    'title' => 'To fix later',
    'testId' => 'checklist-later',
])
