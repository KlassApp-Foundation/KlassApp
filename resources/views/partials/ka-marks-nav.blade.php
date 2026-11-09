{{-- Approvals / Mark sheets / Marks Release (page 3). $marksNav is approvals, sheet, or release. --}}
@php $marksNav = $marksNav ?? 'sheet'; @endphp
<nav class="ka-tabs" aria-label="Marks sections" data-testid="marks-section-nav">
    <a href="{{ route('admin.marks.submissions') }}" @if($marksNav === 'approvals') aria-current="page" @endif>Approvals</a>
    <a href="{{ route('admin.marks') }}" @if($marksNav === 'sheet') aria-current="page" @endif>Mark sheets</a>
    <a href="{{ route('admin.marks.releases.create') }}" @if($marksNav === 'release') aria-current="page" @endif>Marks Release</a>
</nav>
