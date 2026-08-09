{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
{{-- Manual path: hide Toshi entirely so its stale 1/18 checklist cannot compete with the wizard. --}}
<div class="py-6 px-4" data-testid="manual-wizard-page" data-toshi-manual-wizard="1">
    @livewire('manual-onboarding-wizard')
</div>
<script>
(function () {
    document.body.classList.add('toshi-collapsed', 'toshi-manual-wizard');
    var toggle = document.getElementById('toshi-toggle');
    if (toggle) toggle.textContent = '◀';
})();
</script>
@endsection
