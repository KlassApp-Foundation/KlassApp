{{-- SPDX-License-Identifier: MIT --}}
@php function stActive($p) { $s=Request()->segment('2'); foreach((array)$p as $v) if($s===$v) return 'active'; return ''; } @endphp
<ul class="list-reset text-sm">
    <li class="py-3 px-3 {{ stActive('dashboard') }}">
        <a href="{{ url('stock/dashboard') }}" class="flex items-center"><x-icons.sidebar name="dashboard"/><span class="mx-3 whitespace-no-wrap">Dashboard</span></a>
    </li>
    <li class="py-3 px-3 {{ stActive(['products','product']) }}">
        <a href="{{ url('stock/products') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-no-wrap">Products</span></a>
    </li>
    <li class="py-3 px-3 {{ stActive(['categories','category']) }}">
        <a href="{{ url('stock/categories') }}" class="flex items-center"><x-icons.sidebar name="subjects"/><span class="mx-3 whitespace-no-wrap">Categories</span></a>
    </li>
    <li class="py-3 px-3 {{ stActive(['suppliers','supplier']) }}">
        <a href="{{ url('stock/suppliers') }}" class="flex items-center"><x-icons.sidebar name="parents"/><span class="mx-3 whitespace-no-wrap">Suppliers</span></a>
    </li>
    <li class="py-3 px-3 {{ stActive(['orders','order']) }}">
        <a href="{{ url('stock/orders') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-no-wrap">Orders</span></a>
    </li>
    <li class="py-3 px-3 {{ stActive(['reports','report']) }}">
        <a href="{{ url('stock/reports') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-no-wrap">Reports</span></a>
    </li>
</ul>
