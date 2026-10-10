{{-- SPDX-License-Identifier: MIT --}}
{{-- Desktop sidebar — visible md+. Flex column so the bottom section stays anchored. --}}
<div id="admin-sidebar" class="hidden md:flex md:flex-col h-full lg:w-48 md:w-48 text-slate-700 dashboard-themed-sidebar admin-sidebar" data-collapsed="false" style="background-color: #FFFCF5;">
  <div class="flex-1 header-wrapper-b">
    @include('layouts.partials.sidebar-menu', ['role' => 'admin'])
  </div>
  @include('layouts.partials.sidebar-footer', ['notifyMode' => 'admin'])
</div>
{{-- Mobile sidebar — toggleable via hamburger. Reuses the same footer at the bottom of the menu.
     Light slide-in panel (concept .side, 288px); geometry/animation live in dashboard-refresh.css. --}}
<div id="res_sidebar" class="block md:hidden admin-sidebar dashboard-themed-sidebar hidden" style="background-color: #FFFCF5;">
  <div class="header-wrapper-b drawer-nav">
    @include('layouts.partials.sidebar-menu', ['role' => 'admin'])
  </div>
  @include('layouts.partials.sidebar-footer', ['notifyMode' => 'admin', 'showYear' => true])
</div>
