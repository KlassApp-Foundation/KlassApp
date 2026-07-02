{{-- SPDX-License-Identifier: MIT --}}
@php function aActive($p) { $s=Request()->segment('2'); foreach((array)$p as $v) if($s===$v) return 'active'; return ''; } @endphp
<ul class="list-reset text-sm">
    <li class="py-3 px-3 hover:font-semibold {{ aActive('dashboard') }}">
        <a href="{{ url('accountant/dashboard') }}" class="flex items-center"><x-icons.sidebar name="dashboard"/><span class="mx-3 whitespace-no-wrap">Dashboard</span></a>
    </li>
    <li class="py-3 px-3 hover:font-semibold {{ aActive(['fees','fee']) }}">
        <a href="{{ url('accountant/fees') }}" class="flex items-center"><x-icons.sidebar name="fees"/><span class="mx-3 whitespace-no-wrap">Fees</span></a>
    </li>
    <li class="py-3 px-3 hover:font-semibold {{ aActive(['payments','payment']) }}">
        <a href="{{ url('accountant/payments') }}" class="flex items-center"><x-icons.sidebar name="accountant"/><span class="mx-3 whitespace-no-wrap">Payments</span></a>
    </li>
    <li class="py-3 px-3 hover:font-semibold {{ aActive(['invoices','invoice']) }}">
        <a href="{{ url('accountant/invoices') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-no-wrap">Invoices</span></a>
    </li>
    <li class="py-3 px-3 hover:font-semibold {{ aActive(['expenses','expense']) }}">
        <a href="{{ url('accountant/expenses') }}" class="flex items-center"><x-icons.sidebar name="fees"/><span class="mx-3 whitespace-no-wrap">Expenses</span></a>
    </li>
    <li class="py-3 px-3 hover:font-semibold {{ aActive(['reports','report']) }}">
        <a href="{{ url('accountant/reports') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-no-wrap">Reports</span></a>
    </li>
    <li class="py-3 px-3 hover:font-semibold {{ aActive(['holidays','holiday']) }}">
        <a href="{{ url('accountant/holidays') }}" class="flex items-center"><x-icons.sidebar name="calendar"/><span class="mx-3 whitespace-no-wrap">Holidays</span></a>
    </li>
</ul>
