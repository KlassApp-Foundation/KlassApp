{{-- SPDX-License-Identifier: MIT --}}
{{--
    THE shared sidebar renderer. Every role's sidebar (@see layouts/<role>/sidebar.blade.php)
    includes this with the role key; the menu itself lives in config/navigation.php.

    Replaces 10 duplicated layouts/<role>/menu.blade.php files and their per-role
    segment('2') active-state helpers.

    Params: $role (string) — key in config('navigation.roles').
--}}
@php
    $role = $role ?? null;
    $nav = $role ? config('navigation.roles.'.$role) : null;

    if (! $nav) {
        // Fail loudly while developing; degrade to an empty menu rather than
        // breaking every page in production.
        if (config('app.debug')) {
            throw new \RuntimeException('sidebar-menu: unknown role ['.($role ?? 'null').']');
        }
        $nav = ['layout' => 'flat', 'items' => []];
    }

    $itemClass   = $nav['item_class'] ?? 'dashboard-menu-item';
    $activeClass = $nav['active_class'] ?? 'active';
    $prefix      = trim((string) ($nav['prefix'] ?? ''), '/');

    $navHref = function (array $item): string {
        $href = isset($item['route']) ? route($item['route']) : url($item['url'] ?? '#');
        if (! empty($item['hash'])) {
            $href .= '#'.$item['hash'];
        }

        return $href;
    };

    // Active state is derived from route/path patterns (never from a hard-coded
    // URL segment index, which is what the retired per-role helpers did).
    $navActive = function (array $item) use ($prefix): bool {
        foreach ((array) ($item['paths'] ?? []) as $pattern) {
            if (request()->is($pattern)) {
                return true;
            }
        }

        foreach ((array) ($item['active'] ?? []) as $alias) {
            if ($prefix !== '' && request()->is($prefix.'/'.$alias.'*')) {
                return true;
            }
        }

        return false;
    };

    // Class-teacher-only items (teacher sidebar). Resolved once, and only if a
    // visible item actually needs it.
    $ctLinks = null;
    $needsCt = collect($nav['items'] ?? [])->contains(fn ($i) => ($i['condition'] ?? null) === 'class_teacher');
    if ($needsCt && auth()->check() && auth()->user()->school_id) {
        $ctLinks = \App\Helpers\SiteHelper::getClassTeacherStandardLinks(
            (int) auth()->user()->school_id,
            (int) auth()->id()
        );
    }

    $showItem = function (array $item) use ($ctLinks): bool {
        return ($item['condition'] ?? null) !== 'class_teacher' || ($ctLinks && $ctLinks->isNotEmpty());
    };
@endphp
<ul class="list-reset text-sm">
    @if(($nav['layout'] ?? 'flat') === 'grouped')
        @foreach($nav['items'] as $item)
            @include('layouts.partials.sidebar-menu-item', compact('item', 'itemClass', 'activeClass', 'navHref', 'navActive'))
        @endforeach

        @foreach($nav['groups'] as $group)
            {{-- Group header. All behaviour lives in x-data methods (a multi-statement
                 x-on:click string is re-parsed by Alpine as an expression and throws
                 "Unexpected token ';'"), so the markup only ever calls a method. --}}
            <li x-data="{
                    open: false,
                    previewOpen: false,
                    _ht: null,
                    _lt: null,
                    _ch: false,
                    _key: 'sidebar-group-{{ $group['key'] }}',
                    init() {
                        this._ch = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
                        this.open = localStorage.getItem(this._key) === 'true' || this.$el.querySelector('.active, .dashboard-active') !== null;
                    },
                    hoverOn() {
                        if (this._ch && !this.open) { clearTimeout(this._lt); this._ht = setTimeout(() => { this.previewOpen = true; }, 200); }
                    },
                    hoverOff() {
                        if (!this._ch) { return; }
                        clearTimeout(this._ht); this._lt = setTimeout(() => { this.previewOpen = false; }, 300);
                    },
                    toggle() {
                        clearTimeout(this._ht); clearTimeout(this._lt);
                        if (this.previewOpen) { this.open = true; this.previewOpen = false; localStorage.setItem(this._key, true); }
                        else { this.open = !this.open; localStorage.setItem(this._key, this.open); }
                    }
                }"
                x-on:mouseenter="hoverOn()"
                x-on:mouseleave="hoverOff()"
                data-sidebar-group="{{ $group['key'] }}"
                class="sidebar-group">
                <div x-on:click="toggle()"
                     class="sidebar-group-header"
                     x-bind:class="{ 'sidebar-group-header--open': open || previewOpen }">
                    <div class="flex items-center gap-2">
                        <x-icons.sidebar-group name="{{ $group['icon'] ?? $group['key'] }}"/>
                        <span class="sidebar-group-label">{{ $group['label'] }}</span>
                    </div>
                    <svg class="sidebar-group-chevron" x-bind:class="{ 'rotate-180': open || previewOpen }" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <ul x-show="open || previewOpen" x-collapse.duration.200ms>
                    @foreach($group['items'] as $item)
                        @include('layouts.partials.sidebar-menu-item', compact('item', 'itemClass', 'activeClass', 'navHref', 'navActive'))
                    @endforeach
                </ul>
            </li>
        @endforeach
    @else
        @foreach($nav['items'] as $item)
            @if($showItem($item))
                @if($item['submenu'] ?? false)
                    @php $subActive = $navActive($item); @endphp
                    <li x-data="{ open: {{ $subActive ? 'true' : 'false' }} }" class="py-0">
                        <div x-on:click="open = !open"
                             class="py-3 px-3 flex items-center cursor-pointer hover:font-semibold {{ $subActive ? $activeClass : '' }}">
                            @if(!empty($item['icon']))
                                <x-icons.sidebar name="{{ $item['icon'] }}"/>
                            @endif
                            <span class="mx-3 whitespace-nowrap">{{ $item['label'] }}</span>
                            <svg class="w-3 h-3 ml-auto transition-transform" x-bind:class="open ? 'rotate-90' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                        <ul x-show="open" class="ml-4 border-l-2" style="border-color: var(--d-border);">
                            @foreach($item['children'] as $child)
                                @php
                                    $childActive = $navActive($child);
                                    $childClass = trim('py-2 px-3 hover:font-semibold '.($childActive ? $activeClass : ''));
                                @endphp
                                <li class="{{ $childClass }}">
                                    <a href="{{ $navHref($child) }}" class="flex items-center"><span class="mx-2">{{ $child['label'] }}</span></a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @else
                    @include('layouts.partials.sidebar-menu-item', compact('item', 'itemClass', 'activeClass', 'navHref', 'navActive'))
                @endif
            @endif
        @endforeach
    @endif
</ul>

@if(!empty($nav['footer']))
    {{-- Bottom-of-sidebar link (role config `footer`). --}}
    <div class="hidden md:block mt-auto border-t border-gray-100 px-3 py-3">
        <a href="{{ $nav['footer']['href'] }}"
           @if($nav['footer']['external'] ?? false) target="_blank" rel="noopener noreferrer" @endif
           class="{{ $itemClass }} flex items-center gap-2 text-xs text-gray-400 hover:text-gray-600 transition-colors duration-150">
            <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <span>{{ $nav['footer']['label'] }}</span>
        </a>
    </div>
@endif
