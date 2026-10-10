{{-- SPDX-License-Identifier: MIT --}}
@props(['list'])

<link rel="stylesheet" href="/css/people-list.css?v={{ filemtime(public_path('css/people-list.css')) }}">

<div class="people-list" data-people-list="{{ $list['kind'] }}">
    <form class="people-tools" method="GET" action="{{ $list['url'] }}" data-people-search-form>
        @if(($list['chip'] ?? 'all') !== 'all')
            <input type="hidden" name="chip" value="{{ $list['chip'] }}">
        @endif
        @if(!empty($list['standard']))
            <input type="hidden" name="standard" value="{{ $list['standard'] }}">
        @endif
        <label class="people-search">
            <span class="people-sr">Search {{ strtolower($list['title']) }}</span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input id="{{ $list['kind'] }}-search" class="ds-form-input ds-form-input--with-icon" type="search" name="search" value="{{ $list['search'] }}" placeholder="{{ $list['placeholder'] }}" data-people-search autocomplete="off">
        </label>
        <div class="people-chips" role="group" aria-label="Filters">
            @foreach($list['chips'] as $chip)
                @if(!empty($chip['picker']))
                    <details class="people-picker">
                        <summary class="people-chip" data-chip="class" aria-pressed="{{ $chip['pressed'] ? 'true' : 'false' }}">
                            {{ $chip['label'] }}
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="people-picker-menu" role="list">
                            @forelse($chip['options'] as $option)
                                <a role="listitem" href="{{ $option['href'] }}">{{ $option['label'] }}</a>
                            @empty
                                <span class="people-picker-empty">No classes yet</span>
                            @endforelse
                        </div>
                    </details>
                @else
                    <a class="people-chip" data-chip="{{ $chip['key'] }}" href="{{ $chip['href'] }}" aria-pressed="{{ $chip['pressed'] ? 'true' : 'false' }}">
                        {{ $chip['label'] }}
                        @if($chip['count'] !== null)
                            <span class="people-chip-n">{{ $chip['count'] }}</span>
                        @endif
                    </a>
                @endif
            @endforeach
        </div>
    </form>

    <div class="people-bulk" data-people-bulk hidden>
        <b><span data-people-count>0</span> selected</b>
        @foreach($list['bulk'] as $action)
            <a class="people-bulk-btn" href="{{ $action['href'] }}">{{ $action['label'] }}</a>
        @endforeach
        <button class="people-bulk-btn" type="button" data-people-clear>Clear</button>
    </div>

    <div class="people-loading" data-people-loading hidden aria-busy="true">
        <p class="people-sr" role="status">Loading {{ strtolower($list['title']) }}…</p>
        @for($i = 0; $i < 6; $i++)
            <div class="people-skel" aria-hidden="true"></div>
        @endfor
    </div>

    @if($list['empty'])
        <div class="people-empty">
            <b>No {{ strtolower($list['title']) }} yet</b>
            <p>{{ $list['empty_copy'] }}</p>
            <div class="people-empty-actions">
                <a class="people-btn people-btn-primary" href="{{ $list['add_url'] }}">{{ $list['add_label'] }}</a>
                <a class="people-btn" href="{{ $list['import_url'] }}">Import a list</a>
            </div>
        </div>
    @elseif($list['no_match'])
        <div class="people-empty">
            <b>No {{ strtolower($list['title']) }} match @if($list['search'] !== '')“{{ $list['search'] }}”@else these filters @endif</b>
            <p>Check the spelling or clear the filters to search all {{ strtolower($list['title']) }}.</p>
            <a class="people-btn" href="{{ $list['clear_url'] }}">Clear filters</a>
        </div>
    @else
        <div class="people-table-wrap">
            <table class="people-table">
                <thead>
                    <tr>
                        <th class="people-check">
                            <label class="people-check-hit">
                                <input type="checkbox" data-people-all aria-label="Select all on this page">
                            </label>
                        </th>
                        <th scope="col">{{ $list['kind'] === 'parents' ? 'Parent or guardian' : rtrim($list['title'], 's') }}</th>
                        @foreach($list['columns'] as $index => $column)
                            <th scope="col" @if($index === 2) class="people-col-4" @endif>{{ $column }}</th>
                        @endforeach
                        <th class="people-menu"><span class="people-sr">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($list['rows'] as $row)
                        <tr class="people-row">
                            <td class="people-check">
                                <label class="people-check-hit">
                                    <input type="checkbox" value="{{ $row['id'] }}" data-people-check aria-label="Select {{ $row['name'] }}">
                                </label>
                            </td>
                            <td>
                                <span class="people-who">
                                    <x-profile-photo :user="$row['user']" size="xs" />
                                    <span class="people-who-text">
                                        <a class="people-name" href="{{ $row['profile_url'] }}">{{ $row['name'] }}</a>
                                        @if(!empty($row['role']))
                                            <span class="people-role">{{ $row['role'] }}</span>
                                        @endif
                                    </span>
                                </span>
                            </td>
                            @foreach($row['cells'] as $index => $cell)
                                <td @if($index === 2) class="people-col-4" @endif @if(!empty($cell['last_login'])) data-last-login="{{ $cell['last_login'] }}" @endif>
                                    @if(!empty($cell['badge']))
                                        <span class="people-badge people-badge-{{ $cell['badge'] }}">{{ $cell['text'] }}</span>
                                    @else
                                        <span class="{{ $cell['class'] ?? '' }}">{{ $cell['text'] }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="people-menu">
                                <div class="people-menu-wrap">
                                    <button type="button" class="people-icon-btn" data-people-menu aria-haspopup="menu" aria-expanded="false" aria-label="Actions for {{ $row['name'] }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="12" cy="19" r="1.4"/></svg>
                                    </button>
                                    <div class="people-menu-pop" role="menu" hidden>
                                        @foreach($row['menu'] as $item)
                                            <a role="menuitem" href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="people-cards">
                @foreach($list['rows'] as $row)
                    <article class="people-card">
                        <label class="people-check-hit">
                            <input type="checkbox" value="{{ $row['id'] }}" data-people-check aria-label="Select {{ $row['name'] }}">
                        </label>
                        <x-profile-photo :user="$row['user']" size="sm" />
                        <div class="people-card-main">
                            <a class="people-name" href="{{ $row['profile_url'] }}">{{ $row['name'] }}</a>
                            <p class="people-card-line">
                                @foreach($row['card'] as $bit)
                                    <span>{{ $bit }}</span>
                                @endforeach
                            </p>
                        </div>
                        <div class="people-menu-wrap">
                            <button type="button" class="people-icon-btn" data-people-menu aria-haspopup="menu" aria-expanded="false" aria-label="Actions for {{ $row['name'] }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="12" cy="19" r="1.4"/></svg>
                            </button>
                            <div class="people-menu-pop" role="menu" hidden>
                                @foreach($row['menu'] as $item)
                                    <a role="menuitem" href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="people-pager">
                <span>{{ $list['showing'] }}</span>
                <span class="people-pager-nav">
                    @if($list['paginator']->onFirstPage())
                        <span class="people-icon-btn" aria-disabled="true">Previous page</span>
                    @else
                        <a class="people-icon-btn" href="{{ $list['paginator']->previousPageUrl() }}" aria-label="Previous page">Previous page</a>
                    @endif
                    @if($list['paginator']->hasMorePages())
                        <a class="people-icon-btn" href="{{ $list['paginator']->nextPageUrl() }}" aria-label="Next page">Next page</a>
                    @else
                        <span class="people-icon-btn" aria-disabled="true">Next page</span>
                    @endif
                </span>
            </div>
        </div>
    @endif
</div>
<script src="/js/people-list.js?v={{ filemtime(public_path('js/people-list.js')) }}" defer></script>
