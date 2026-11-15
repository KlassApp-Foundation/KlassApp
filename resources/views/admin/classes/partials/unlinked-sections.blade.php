@php
    $__schoolId = (int) auth()->user()->school_id;
    $__year = \App\Helpers\SiteHelper::getAcademicYear($__schoolId);
    $__unlinked = $__year ? app(\App\Services\Setup\SectionClassLinker::class)->unlinkedSections($__schoolId, (int) $__year->id) : collect();
@endphp
@if ($__unlinked->isNotEmpty())
    <div data-testid="unlinked-sections" style="margin:12px 16px;padding:12px 16px;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;color:#78350f;">
        <strong>{{ $__unlinked->count() === 1 ? '1 section is' : $__unlinked->count().' sections are' }} not linked to a class</strong> for {{ $__year->name }}, so {{ $__unlinked->count() === 1 ? 'it' : 'they' }} cannot hold learners yet.
        <ul style="margin:8px 0 0;padding:0;list-style:none;">
            @foreach ($__unlinked as $__section)
                <li style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:4px 0;">
                    <span>{{ $__section->name }}</span>
                    <a href="{{ route('admin.classes.link-section', $__section->id) }}" class="ds-btn ds-btn-sm" style="min-height:44px;display:inline-flex;align-items:center;" data-testid="link-section-{{ $__section->id }}">Link to a class</a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
