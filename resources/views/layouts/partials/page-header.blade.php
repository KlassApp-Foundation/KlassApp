{{-- Shared page header matching dashboard design --}}
<div class="dashboard-heading">
    <div>
        <h1 class="dashboard-section-title" style="font-size: 1.25rem;">{{ $title }}</h1>
        <p class="dashboard-subtitle" style="margin-top: 4px;">{{ $subtitle ?? '' }}</p>
    </div>
    @if(!empty($actions))
        <div class="flex items-center gap-2">
            {!! $actions !!}
        </div>
    @endif
</div>
