{{-- SPDX-License-Identifier: MIT --}}
@php if(!function_exists('aActive')){function aActive($p){ $s=Request()->segment('2'); foreach((array)$p as $v) if($s===$v) return 'active'; return ''; }} @endphp
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
        <a href="{{ url('accountant/reports') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-no-wrap">Data Exports</span></a>
    </li>
    <li class="py-3 px-3 hover:font-semibold {{ aActive(['holidays','holiday']) }}">
        <a href="{{ url('accountant/holidays') }}" class="flex items-center"><x-icons.sidebar name="calendar"/><span class="mx-3 whitespace-no-wrap">Holidays</span></a>
    </li>
    {{-- Payroll section with sub-items --}}
    <li x-data="{ open: {{ in_array(Request()->segment('2'), ['payroll']) ? 'true' : 'false' }} }" class="py-0">
        <div @click="open = !open" class="py-3 px-3 flex items-center cursor-pointer hover:font-semibold {{ aActive(['payroll']) }}">
            <x-icons.sidebar name="accountant"/><span class="mx-3 whitespace-no-wrap">Payroll</span>
            <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-90' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
        <ul x-show="open" class="ml-4 border-l-2" style="border-color: var(--d-border);">
            <li class="py-2 px-3 hover:font-semibold"><a href="{{ url('accountant/payroll/template') }}" class="flex items-center"><span class="mx-2">Templates</span></a></li>
            <li class="py-2 px-3 hover:font-semibold"><a href="{{ url('accountant/payroll/salary') }}" class="flex items-center"><span class="mx-2">Salaries</span></a></li>
            <li class="py-2 px-3 hover:font-semibold"><a href="{{ url('accountant/payroll/payslip') }}" class="flex items-center"><span class="mx-2">Payslips</span></a></li>
            <li class="py-2 px-3 hover:font-semibold {{ aActive(['payroll.batch']) }}"><a href="{{ url('accountant/payroll/batch') }}" class="flex items-center"><span class="mx-2">Batch Run</span></a></li>
        </ul>
    </li>
</ul>
