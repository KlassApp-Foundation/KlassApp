{{-- SPDX-License-Identifier: MIT --}}
@props([
    'name',
    'initials',
    'className' => null,
    'kls' => null,
    'status' => 'active',
    'statusLabel' => 'Active',
    'role' => null,
    'joined' => null,
    'tabs' => [],
    'active' => 'overview',
    'baseUrl',
    'backUrl' => null,
    'backLabel' => 'Back',
    'user' => null,
    'facts' => [],
])
<div {{ $attributes->merge(['class' => 'ka-bare-page', 'style' => 'background:transparent;color:#0F172A;border:0;border-radius:0;padding:0;']) }} data-person-profile>
    {{-- [data-person-profile] dd and [data-fact] wrap long values (dashboard-refresh.css). --}}
    @if($backUrl)
        <p class="text-sm" style="margin:0 0 12px;">
            <a href="{{ $backUrl }}" style="color:#1D4ED8;">{{ $backLabel }}</a>
        </p>
    @endif

    <header data-testid="person-profile-header" style="display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start;justify-content:space-between;background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
        <div style="display:flex;gap:12px;align-items:flex-start;min-width:0;">
            <x-profile-photo :user="$user" size="header" class="person-header-photo" data-testid="person-initials" />
            <div style="min-width:0;">
                <h1 style="margin:0;font-size:22px;line-height:1.3;font-weight:700;letter-spacing:-0.02em;color:#0F172A;">{{ $name }}</h1>
                @if(filled($role))
                    <p style="margin:4px 0 0;font-size:14px;color:#475569;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                        <span data-testid="person-role" style="display:inline-flex;align-items:center;min-height:24px;border-radius:999px;padding:2px 8px;font-size:12px;font-weight:600;background:#DBEAFE;color:#1E40AF;">{{ $role }}</span>
                        @if(filled($joined))
                            <span data-testid="person-joined">{{ $joined }}</span>
                        @endif
                    </p>
                @elseif($facts !== [])
                    <p data-testid="person-facts" style="margin:4px 0 0;font-size:14px;color:#475569;display:flex;flex-wrap:wrap;column-gap:6px;row-gap:2px;">
                        @foreach($facts as $key => $fact)
                            @if(! $loop->first)<span aria-hidden="true">·</span>@endif
                            <span data-fact="{{ $key }}" @if($key === 'kls') data-testid="person-kls" @endif>{{ $fact }}</span>
                        @endforeach
                    </p>
                @else
                    <p style="margin:4px 0 0;font-size:14px;color:#475569;">
                        {{ $className ?: 'No class' }}
                        <span aria-hidden="true"> · </span>
                        <span data-testid="person-kls">{{ $kls ?: '—' }}</span>
                    </p>
                @endif
                <p style="margin:8px 0 0;">
                    <span data-testid="person-status" style="display:inline-block;border-radius:999px;padding:2px 8px;font-size:12px;font-weight:600;background:{{ $status === 'active' ? '#DCFCE7' : '#F1F5F9' }};color:{{ $status === 'active' ? '#166534' : '#475569' }};">{{ $statusLabel }}</span>
                </p>
            </div>
        </div>
        @isset($actions)
            <div style="display:flex;gap:8px;align-items:center;">{{ $actions }}</div>
        @endisset
    </header>

    @isset($tiles)
        <section aria-label="This term" data-testid="person-profile-tiles" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:16px;">
            {{ $tiles }}
        </section>
    @endisset

    @if($tabs !== [])
        <nav data-testid="person-profile-tabs" aria-label="Profile" style="display:flex;flex-wrap:nowrap;overflow-x:auto;gap:16px;margin-top:16px;">
            @foreach($tabs as $key => $label)
                <a href="{{ $baseUrl.'?tab='.$key }}" data-tab="{{ $key }}" aria-current="{{ $active === $key ? 'page' : 'false' }}" style="min-height:44px;display:inline-flex;align-items:center;box-sizing:border-box;padding:0 2px;border-radius:0;text-decoration:none;font-size:14px;font-weight:600;white-space:nowrap;background:transparent;color:#0F172A;border:0;border-bottom:2px solid {{ $active === $key ? '#15803D' : 'transparent' }};">{{ $label }}</a>
            @endforeach
        </nav>
    @endif

    <div style="margin-top:16px;">{{ $slot }}</div>
</div>
