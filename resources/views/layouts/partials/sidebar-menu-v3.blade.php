{{-- SPDX-License-Identifier: MIT --}}
{{-- Admin sidebar v3. Section labels stay visible. A parent row opens its page;
     the chevron only toggles children. Open state is not stored (ka:sidebar:v3
     is a version marker). The group that contains the current page opens itself. --}}
@php $navCounts = $navCounts ?? []; @endphp
@foreach($nav['sections'] as $section)
    @php
        $rows = array_values(array_filter($section['rows'], $showItem));
    @endphp
    @if(count($rows) > 0)
        @if(!empty($section['label']))
            <li class="sidebar-v3-section" role="presentation">{{ $section['label'] }}</li>
        @endif
        @foreach($rows as $row)
            @php
                $children = array_values(array_filter($row['children'] ?? [], $showItem));
                $rowActive = $navActive($row);
                $childActive = false;
                foreach ($children as $child) {
                    if ($navActive($child)) {
                        $childActive = true;
                        break;
                    }
                }
                $open = $rowActive || $childActive;
                $rowCount = count($children) === 0 ? (int) ($navCounts[$row['count'] ?? ''] ?? 0) : 0;
                $childCounts = [];
                foreach ($children as $child) {
                    $childCounts[] = (int) ($navCounts[$child['count'] ?? ''] ?? 0);
                }
                $hasChildCount = array_sum($childCounts) > 0;
                // A parent that shares its URL with a child the current user cannot
                // see (Classes → Classes and streams, school admin only) opens the
                // first child they can see instead of a page that would 403.
                $hrefItem = $row;
                foreach (($row['children'] ?? []) as $gated) {
                    if (($gated['url'] ?? null) === ($row['url'] ?? null) && ! $showItem($gated) && count($children) > 0) {
                        $hrefItem = $children[0];
                        break;
                    }
                }
            @endphp
            <li class="sidebar-v3-item{{ $hasChildCount ? ' has-count' : '' }}" data-sidebar-row data-tip="{{ $row['label'] }}"
                @if(count($children) > 0)
                    x-data="{ open: {{ $open ? 'true' : 'false' }}, flyout: false }"
                    x-bind:class="flyout ? 'is-flyout' : ''"
                @endif>
                <div class="sidebar-v3-row">
                    <a href="{{ $navHref($hrefItem) }}"
                       class="sidebar-v3-link {{ $itemClass }} {{ ($rowActive && ! $childActive) ? $activeClass : '' }}"
                       aria-label="{{ $row['label'] }}"
                       tabindex="0"
                       data-tip="{{ $row['label'] }}"
                       @if(count($children) > 0) aria-haspopup="true" x-bind:aria-expanded="(document.body.classList.contains('sidebar-collapsed') ? flyout : open) ? 'true' : 'false'" @endif
                       @if($rowActive && ! $childActive) aria-current="page" @endif>
                        @if(!empty($row['icon']))
                            <x-icons.sidebar name="{{ $row['icon'] }}"/>
                        @endif
                        <span>{{ $row['label'] }}</span>
                        @if($rowCount > 0)
                            <span class="sidebar-v3-count">{{ $rowCount }}</span>
                        @endif
                    </a>
                    @if(count($children) > 0)
                        <button type="button" class="sidebar-v3-chevron" x-on:click="open = !open" x-bind:aria-expanded="open ? 'true' : 'false'" aria-label="Show {{ $row['label'] }} items">
                            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true" x-bind:class="open ? 'rotate-90' : ''">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    @endif
                </div>
                @if(count($children) > 0)
                    <div class="sidebar-v3-bridge" hidden aria-hidden="true"></div>
                    <ul class="sidebar-v3-children" x-show="flyout || (open && !document.body.classList.contains('sidebar-collapsed'))" x-cloak role="group" aria-label="{{ $row['label'] }}">
                        <li class="sidebar-v3-flyout-title" role="presentation">{{ $row['label'] }}</li>
                        @foreach($children as $index => $child)
                            @php
                                $isChild = $navActive($child);
                                $childCount = $childCounts[$index] ?? 0;
                            @endphp
                            <li>
                                <a href="{{ $navHref($child) }}"
                                   class="sidebar-v3-child {{ $isChild ? $activeClass : '' }}"
                                   aria-label="{{ $child['label'] }}"
                                   tabindex="0"
                                   @if($isChild) aria-current="page" @endif>
                                    <span>{{ $child['label'] }}</span>
                                    @if($childCount > 0)
                                        <span class="sidebar-v3-count">{{ $childCount }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    @endif
@endforeach
