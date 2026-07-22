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
            <span class="sidebar-group-label">Academics</span>
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
                    <x-icons.sidebar name="students"/>
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
                    <x-icons.sidebar name="subjects"/>
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
            <span class="sidebar-group-label">Operations</span>
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
            <span class="sidebar-group-label">Finance</span>
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
            <span class="sidebar-group-label">Communication</span>
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
            <span class="sidebar-group-label">System</span>
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
