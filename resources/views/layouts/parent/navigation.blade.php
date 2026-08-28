{{-- SPDX-License-Identifier: MIT --}}
<nav class="navbar dashboard-themed-header w-full flex lg:flex-row px-4 lg:px-8 py-2 justify-between items-center">
  <div class="nav-brand flex items-center">
    @auth
      <button type="button" class="hidden lg:flex md:flex sidebar-toggle-btn mr-3" id="sidebar-toggle" title="Toggle sidebar" aria-label="Toggle sidebar" style="background: transparent; border: none; cursor: pointer; padding: 6px; border-radius: 6px; color: #0F172A;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="12" x2="15" y2="12"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
      <button class="block lg:hidden md:hidden mr-3" onclick="showsidebar('res_sidebar')">
        <span class="navbar-toggler-icon">
          <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path class="heroicon-ui" d="M4 5h16a1 1 0 0 1 0 2H4a1 1 0 1 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2z"/></svg>
        </span>
      </button>
      <a class="text-xl lg:text-2xl font-exo font-semibold text-white px-2" href="{{ route('parent.dashboard') }}">
        <strong>KlassApp Parent Portal</strong>
      </a>
    @else
      @include('layouts.partials.logo')
    @endauth
  </div>
  <div class="flex items-center">
    @auth
      <form method="POST" action="{{ route('logout') }}" class="inline">
        @csrf
        <button type="submit" class="ds-btn ds-btn-sm" style="background:#fff;color:#0F172A;font-weight:600;">Logout</button>
      </form>
    @endauth
  </div>
</nav>
