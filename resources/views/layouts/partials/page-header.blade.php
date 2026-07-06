{{-- Shared page header matching design system --}}
<div class="ds-page-head">
    <div>
        <h1 class="ds-page-head-title">{{ $title }}</h1>
        @if(!empty($subtitle))
            <p class="ds-page-head-sub">{{ $subtitle }}</p>
        @endif
    </div>
    @if(!empty($actions))
        <div class="flex items-center gap-2">
            {!! $actions !!}
        </div>
    @endif
</div>
