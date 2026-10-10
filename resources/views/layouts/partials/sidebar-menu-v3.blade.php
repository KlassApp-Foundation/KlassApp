{{-- SPDX-License-Identifier: MIT --}}
{{-- Admin sidebar v3. Section labels stay visible. A parent row opens its page;
     the chevron only toggles children. Open state is not stored (ka:sidebar:v3
     is a version marker). The group that contains the current page opens itself. --}}
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
            @endphp
            <li class="sidebar-v3-item" data-sidebar-row
                @if(count($children) > 0)
                    x-data="{ open: {{ $open ? 'true' : 'false' }} }"
                    x-init="try { localStorage.setItem('ka:sidebar:v3', '1'); } catch (e) {}"
                @endif>
                <div class="sidebar-v3-row">
                    <a href="{{ $navHref($row) }}"
                       class="sidebar-v3-link {{ $itemClass }} {{ ($rowActive && ! $childActive) ? $activeClass : '' }}"
                       @if($rowActive && ! $childActive) aria-current="page" @endif>
                        @if(!empty($row['icon']))
                            <x-icons.sidebar name="{{ $row['icon'] }}"/>
                        @endif
                        <span>{{ $row['label'] }}</span>
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
                    <ul class="sidebar-v3-children" x-show="open" x-cloak>
                        @foreach($children as $child)
                            @php $isChild = $navActive($child); @endphp
                            <li>
                                <a href="{{ $navHref($child) }}"
                                   class="sidebar-v3-child {{ $isChild ? $activeClass : '' }}"
                                   @if($isChild) aria-current="page" @endif>{{ $child['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    @endif
@endforeach
