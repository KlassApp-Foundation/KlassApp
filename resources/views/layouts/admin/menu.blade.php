{{-- SPDX-License-Identifier: MIT --}}
@php
// Helper: check if current route segment matches any of the given patterns
if (!function_exists('sidebarActive')) {
    function sidebarActive($patterns): string {
        $segment = Request()->segment('2');
        foreach ((array)$patterns as $p) {
            if ($segment === $p) return 'active dashboard-active';
        }
        return '';
    }
}
@endphp
<ul class="list-reset text-sm">
    {{-- ═══ DASHBOARD (ungrouped, always at top) ═══ --}}
    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive('dashboard') }}">
        <a href="{{ url('admin/dashboard') }}" class="flex items-center">
            <x-icons.sidebar name="dashboard"/>
            <span class="mx-3 whitespace-no-wrap">Dashboard</span>
        </a>
    </li>

    {{-- ═══ ACADEMICS ═══ --}}
    <li x-data="{
        open: false,
        previewOpen: false,
        _ht: null,
        _lt: null,
        _ch: false,
        init() {
            this._ch = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            this.open = localStorage.getItem('sidebar-group-academics') === 'true' || this.$el.querySelector('.active, .dashboard-active') !== null;
        }
    }" x-on:mouseenter="if (!_ch || open) return; clearTimeout(_lt); _ht = setTimeout(() => { previewOpen = true; }, 200)" x-on:mouseleave="if (!_ch) return; clearTimeout(_ht); _lt = setTimeout(() => { previewOpen = false; }, 300)" data-sidebar-group="academics" class="sidebar-group">
        <div x-on:click="clearTimeout(_ht); clearTimeout(_lt); clearTimeout(_ht); clearTimeout(_lt); if (previewOpen) { open = true; previewOpen = false; localStorage.setItem('sidebar-group-academics', true); } else { open = !open; localStorage.setItem('sidebar-group-academics', open); }" class="sidebar-group-header" x-bind:class="{ 'sidebar-group-header--open': open || previewOpen }">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: #94A3B8;"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
                <span class="sidebar-group-label">Academics</span>
            </div>
            <svg class="sidebar-group-chevron" x-bind:class="{ 'rotate-180': open || previewOpen }" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </div>
        <ul x-show="open || previewOpen" x-collapse.duration.200ms>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['students','student','parents','parent','teachers','teacher','staff','staffs','alumni','blocked_students']) }}">
                <a href="{{ url('admin/students') }}" class="flex items-center">
                    <x-icons.sidebar name="students"/>
                    <span class="mx-3 whitespace-no-wrap">Students</span>
                </a>
            </li>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['parents','parent']) }}">
                <a href="{{ url('admin/parents') }}" class="flex items-center">
                    <x-icons.sidebar name="parents"/>
                    <span class="mx-3 whitespace-no-wrap">Parents</span>
                </a>
            </li>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['classes','sections','standardlinks','standardLink']) }}">
                <a href="{{ url('admin/classes') }}" class="flex items-center">
                    <x-icons.sidebar name="classes"/>
                    <span class="mx-3 whitespace-no-wrap">Classes &amp; Streams</span>
                </a>
            </li>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['subjects','subject']) }}">
                <a href="{{ url('admin/subjects') }}" class="flex items-center">
                    <x-icons.sidebar name="subjects"/>
                    <span class="mx-3 whitespace-no-wrap">Subjects</span>
                </a>
            </li>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['timetable','timetables']) }}">
                <a href="{{ url('admin/timetable') }}" class="flex items-center">
                    <x-icons.sidebar name="timetable"/>
                    <span class="mx-3 whitespace-no-wrap">Timetable</span>
                </a>
            </li>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['attendance']) }}">
                <a href="{{ url('admin/attendance') }}" class="flex items-center">
                    <x-icons.sidebar name="attendance"/>
                    <span class="mx-3 whitespace-no-wrap">Attendance</span>
                </a>
            </li>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['exams','exam','marks','mark']) }}">
                <a href="{{ url('admin/exams') }}" class="flex items-center">
                    <x-icons.sidebar name="exams"/>
                    <span class="mx-3 whitespace-no-wrap">Exams &amp; Marks</span>
                </a>
            </li>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['grades', 'grade']) }}">
                <a href="{{ url('admin/grades') }}" class="flex items-center">
                    <x-icons.sidebar name="grading"/>
                    <span class="mx-3 whitespace-no-wrap">Grading</span>
                </a>
            </li>
        </ul>
    </li>

    {{-- ═══ OPERATIONS ═══ --}}
    <li x-data="{
        open: false,
        previewOpen: false,
        _ht: null,
        _lt: null,
        _ch: false,
        init() {
            this._ch = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            this.open = localStorage.getItem('sidebar-group-operations') === 'true' || this.$el.querySelector('.active, .dashboard-active') !== null;
        }
    }" x-on:mouseenter="if (!_ch || open) return; clearTimeout(_lt); _ht = setTimeout(() => { previewOpen = true; }, 200)" x-on:mouseleave="if (!_ch) return; clearTimeout(_ht); _lt = setTimeout(() => { previewOpen = false; }, 300)" data-sidebar-group="operations" class="sidebar-group">
        <div x-on:click="clearTimeout(_ht); clearTimeout(_lt); if (previewOpen) { open = true; previewOpen = false; localStorage.setItem('sidebar-group-operations', true); } else { open = !open; localStorage.setItem('sidebar-group-operations', open); }" class="sidebar-group-header" x-bind:class="{ 'sidebar-group-header--open': open || previewOpen }">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: #94A3B8;"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                <span class="sidebar-group-label">Operations</span>
            </div>
            <svg class="sidebar-group-chevron" x-bind:class="{ 'rotate-180': open || previewOpen }" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </div>
        <ul x-show="open || previewOpen" x-collapse.duration.200ms>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['library','books']) }}">
                <a href="{{ route('admin.library.books') }}" class="flex items-center">
                    <x-icons.sidebar name="library"/>
                    <span class="mx-3 whitespace-no-wrap">Library</span>
                </a>
            </li>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['health','medical']) }}">
                <a href="{{ url('admin/students') }}" class="flex items-center">
                    <x-icons.sidebar name="health"/>
                    <span class="mx-3 whitespace-no-wrap">Health</span>
                </a>
            </li>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['transport']) }}">
                <a href="{{ url('admin/transport') }}" class="flex items-center">
                    <x-icons.sidebar name="transport"/>
                    <span class="mx-3 whitespace-no-wrap">Transport</span>
                </a>
            </li>
        </ul>
    </li>

    {{-- ═══ FINANCE ═══ --}}
    <li x-data="{
        open: false,
        previewOpen: false,
        _ht: null,
        _lt: null,
        _ch: false,
        init() {
            this._ch = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            this.open = localStorage.getItem('sidebar-group-finance') === 'true' || this.$el.querySelector('.active, .dashboard-active') !== null;
        }
    }" x-on:mouseenter="if (!_ch || open) return; clearTimeout(_lt); _ht = setTimeout(() => { previewOpen = true; }, 200)" x-on:mouseleave="if (!_ch) return; clearTimeout(_ht); _lt = setTimeout(() => { previewOpen = false; }, 300)" data-sidebar-group="finance" class="sidebar-group">
        <div x-on:click="clearTimeout(_ht); clearTimeout(_lt); if (previewOpen) { open = true; previewOpen = false; localStorage.setItem('sidebar-group-finance', true); } else { open = !open; localStorage.setItem('sidebar-group-finance', open); }" class="sidebar-group-header" x-bind:class="{ 'sidebar-group-header--open': open || previewOpen }">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: #94A3B8;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                <span class="sidebar-group-label">Finance</span>
            </div>
            <svg class="sidebar-group-chevron" x-bind:class="{ 'rotate-180': open || previewOpen }" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </div>
        <ul x-show="open || previewOpen" x-collapse.duration.200ms>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['fees','fee','payments','payment','invoices']) }}">
                <a href="{{ url('admin/fees/payments') }}" class="flex items-center">
                    <x-icons.sidebar name="fees"/>
                    <span class="mx-3 whitespace-no-wrap">Fees &amp; Payments</span>
                </a>
            </li>
            <li class="pl-6 py-2 px-3 dashboard-menu-item {{ sidebarActive('unmatched') }}">
                <a href="{{ url('admin/fees/payments/unmatched') }}" class="flex items-center text-xs">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <span>Unmatched Payments</span>
                </a>
            </li>
        </ul>
    </li>

    {{-- ═══ COMMUNICATION ═══ --}}
    <li x-data="{
        open: false,
        previewOpen: false,
        _ht: null,
        _lt: null,
        _ch: false,
        init() {
            this._ch = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            this.open = localStorage.getItem('sidebar-group-communication') === 'true' || this.$el.querySelector('.active, .dashboard-active') !== null;
        }
    }" x-on:mouseenter="if (!_ch || open) return; clearTimeout(_lt); _ht = setTimeout(() => { previewOpen = true; }, 200)" x-on:mouseleave="if (!_ch) return; clearTimeout(_ht); _lt = setTimeout(() => { previewOpen = false; }, 300)" data-sidebar-group="communication" class="sidebar-group">
        <div x-on:click="clearTimeout(_ht); clearTimeout(_lt); if (previewOpen) { open = true; previewOpen = false; localStorage.setItem('sidebar-group-communication', true); } else { open = !open; localStorage.setItem('sidebar-group-communication', open); }" class="sidebar-group-header" x-bind:class="{ 'sidebar-group-header--open': open || previewOpen }">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: #94A3B8;"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                <span class="sidebar-group-label">Communication</span>
            </div>
            <svg class="sidebar-group-chevron" x-bind:class="{ 'rotate-180': open || previewOpen }" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </div>
        <ul x-show="open || previewOpen" x-collapse.duration.200ms>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['messages','messaging','notifications','sentmessages']) }}">
                <a href="{{ route('admin.messages') }}" class="flex items-center">
                    <x-icons.sidebar name="messages"/>
                    <span class="mx-3 whitespace-no-wrap">Messaging</span>
                </a>
            </li>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['calendar','events']) }}">
                <a href="{{ url('admin/calendar') }}" class="flex items-center">
                    <x-icons.sidebar name="calendar"/>
                    <span class="mx-3 whitespace-no-wrap">Calendar</span>
                </a>
            </li>
        </ul>
    </li>

    {{-- ═══ SYSTEM ═══ --}}
    <li x-data="{
        open: false,
        previewOpen: false,
        _ht: null,
        _lt: null,
        _ch: false,
        init() {
            this._ch = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            this.open = localStorage.getItem('sidebar-group-system') === 'true' || this.$el.querySelector('.active, .dashboard-active') !== null;
        }
    }" x-on:mouseenter="if (!_ch || open) return; clearTimeout(_lt); _ht = setTimeout(() => { previewOpen = true; }, 200)" x-on:mouseleave="if (!_ch) return; clearTimeout(_ht); _lt = setTimeout(() => { previewOpen = false; }, 300)" data-sidebar-group="system" class="sidebar-group">
        <div x-on:click="clearTimeout(_ht); clearTimeout(_lt); if (previewOpen) { open = true; previewOpen = false; localStorage.setItem('sidebar-group-system', true); } else { open = !open; localStorage.setItem('sidebar-group-system', open); }" class="sidebar-group-header" x-bind:class="{ 'sidebar-group-header--open': open || previewOpen }">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: #94A3B8;"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                <span class="sidebar-group-label">System</span>
            </div>
            <svg class="sidebar-group-chevron" x-bind:class="{ 'rotate-180': open || previewOpen }" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </div>
        <ul x-show="open || previewOpen" x-collapse.duration.200ms>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['approvals','approval']) }}">
                <a href="{{ url('admin/approvals') }}" class="flex items-center">
                    <x-icons.sidebar name="tasks"/>
                    <span class="mx-3 whitespace-no-wrap">Approvals</span>
                </a>
            </li>
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['reports','report']) }}">
                <a href="{{ url('admin/reports') }}" class="flex items-center">
                    <x-icons.sidebar name="reports"/>
                    <span class="mx-3 whitespace-no-wrap">Data Exports</span>
                </a>
            </li>
            @if(Auth::user()->usergroup_id != 4)
            <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['settings']) }}">
                <a href="{{ url('admin/settings') }}" class="flex items-center">
                    <x-icons.sidebar name="settings"/>
                    <span class="mx-3 whitespace-no-wrap">Settings</span>
                </a>
            </li>
            @endif
        </ul>
    </li>
</ul>

{{-- Bottom section — pushed to bottom via mt-auto on desktop sidebar flex column --}}
<div class="hidden md:block mt-auto border-t border-gray-100 px-3 py-3">
    <a href="https://docs.klassapp.com" target="_blank" rel="noopener noreferrer" class="dashboard-menu-item flex items-center gap-2 text-xs text-gray-400 hover:text-gray-600 transition-colors duration-150">
        <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/>
            <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <span>Help &amp; Docs</span>
    </a>
</div>
