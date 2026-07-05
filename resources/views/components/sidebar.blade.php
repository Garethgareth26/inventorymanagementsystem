{{--
    Sidebar Component
    ─────────────────
    Source: Stitch export (owner_dashboard & employee_dashboard SideNavBar)

    Behaviour:
    - Always visible on md+ (desktop), hidden on mobile.
    - Logo + brand name at the top.
    - Employee role gets an "Add New SKU" CTA button (Owner is read-only).
    - Navigation items use accordion groups for Master Data, Inventory, and
      Inventory Optimization (expandable in future sprints — for now flat links
      with logical groupings).
    - Active state driven by route name matching.
    - Log Out + Help links pinned to the bottom.

    Usage:
        <x-sidebar />

    No props required — reads auth()->user() directly.
--}}

@php
    $user = auth()->user();
    $isOwner    = $user?->isOwner();
    $isKaryawan = $user?->isKaryawan();

    /**
     * Helper: returns the correct nav-item CSS classes based on whether the
     * current route matches the given pattern.
     *
     * @param  string|array  $routePattern   e.g. 'owner.dashboard' or ['bahan_baku.*']
     * @return string
     */
    $navClass = function (string|array $routePattern) {
        return request()->routeIs($routePattern) ? 'nav-item-active' : 'nav-item';
    };
@endphp

{{-- ── Desktop Sidebar (md and above) ──────────────────────────────── --}}
<nav
    id="app-sidebar"
    class="hidden md:flex flex-col fixed left-0 top-0 h-full w-sidebar-expanded z-40
           bg-surface-container-lowest border-r border-border-subtle p-md shrink-0"
    aria-label="Main navigation"
>
    {{-- ── Brand ───────────────────────────────────────────────────── --}}
    <a href="{{ $isOwner ? route('owner.dashboard') : route('employee.dashboard') }}"
       class="flex items-center gap-sm mb-xl px-sm focus:outline-none"
       aria-label="CV Akuna home">
        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center shrink-0" aria-hidden="true">
            <span class="material-symbols-outlined text-on-primary icon-fill text-[20px]">inventory_2</span>
        </div>
        <div class="overflow-hidden">
            <p class="text-headline-md font-black text-primary leading-none truncate">CV Akuna</p>
            <p class="text-label-caps text-secondary truncate mt-0.5">Inventory Management</p>
        </div>
    </a>

    {{-- ── Employee-only CTA ───────────────────────────────────────── --}}
    @if($isKaryawan)
        <a href="{{ route('bahan_baku.create') }}"
           class="btn-primary w-full justify-center mb-lg text-body-sm"
           id="sidebar-add-sku-btn">
            <span class="material-symbols-outlined text-[16px]">add</span>
            Add New SKU
        </a>
    @endif

    {{-- ── Navigation ─────────────────────────────────────────────── --}}
    <div class="flex-1 overflow-y-auto space-y-0.5 pr-xs" role="navigation">

        {{-- Dashboard --}}
        <a href="{{ $isOwner ? route('owner.dashboard') : route('employee.dashboard') }}"
           class="{{ $navClass(['owner.dashboard', 'employee.dashboard']) }}"
           id="nav-dashboard">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">dashboard</span>
            <span class="text-body-md">Dashboard</span>
        </a>

        {{-- ── Master Data group ─────────────────────────────────── --}}
        <div class="pt-2 pb-0.5">
            <p class="px-3 mb-1 text-label-caps text-secondary uppercase tracking-wider">Master Data</p>
        </div>

        <a href="{{ route('suppliers.index') }}"
           class="{{ $navClass('suppliers.*') }}"
           id="nav-suppliers">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">store</span>
            <span class="text-body-md">Suppliers</span>
        </a>

        <a href="{{ route('bahan_baku.index') }}"
           class="{{ $navClass('bahan_baku.*') }}"
           id="nav-raw-materials">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">inventory</span>
            <span class="text-body-md">Raw Materials</span>
        </a>

        <a href="{{ route('barang_jadi.index') }}"
           class="{{ $navClass('barang_jadi.*') }}"
           id="nav-finished-goods">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">deployed_code</span>
            <span class="text-body-md">Finished Goods</span>
        </a>

        <a href="{{ route('bom.index') }}"
           class="{{ $navClass('bom.*') }}"
           id="nav-bom">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">account_tree</span>
            <span class="text-body-md">Bill of Materials</span>
        </a>

        {{-- ── Purchasing & Production ───────────────────────────── --}}
        <div class="pt-2 pb-0.5">
            <p class="px-3 mb-1 text-label-caps text-secondary uppercase tracking-wider">Operations</p>
        </div>

        <a href="{{ route('pesanan_pembelian.index') }}"
           class="{{ $navClass('pesanan_pembelian.*') }}"
           id="nav-purchasing">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">shopping_cart</span>
            <span class="text-body-md">Purchasing</span>
        </a>

        <a href="{{ route('production.index') }}"
           class="{{ $navClass('production.*') }}"
           id="nav-production">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">precision_manufacturing</span>
            <span class="text-body-md">Production</span>
        </a>

        <a href="{{ route('mutasi_stok.index') }}"
           class="{{ $navClass('mutasi_stok.*') }}"
           id="nav-inventory">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">inventory_2</span>
            <span class="text-body-md">Inventory Assets</span>
        </a>

        {{-- ── Inventory Optimization ────────────────────────────── --}}
        <div class="pt-2 pb-0.5">
            <p class="px-3 mb-1 text-label-caps text-secondary uppercase tracking-wider">Optimization</p>
        </div>

        <a href="{{ route('eoq.index') }}"
           class="{{ $navClass('eoq.*') }}"
           id="nav-eoq">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">calculate</span>
            <span class="text-body-md">EOQ</span>
        </a>

        <a href="{{ route('safety_stock.index') }}"
           class="{{ $navClass('safety_stock.*') }}"
           id="nav-safety-stock">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">security</span>
            <span class="text-body-md">Safety Stock</span>
        </a>

        <a href="{{ route('reorder_point.index') }}"
           class="{{ $navClass('reorder_point.*') }}"
           id="nav-rop">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">cycle</span>
            <span class="text-body-md">Reorder Point</span>
        </a>

        <a href="{{ route('abc_analysis.index') }}"
           class="{{ $navClass('abc_analysis.*') }}"
           id="nav-abc">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">analytics</span>
            <span class="text-body-md">ABC Analysis</span>
        </a>

        {{-- ── Reports ───────────────────────────────────────────── --}}
        <div class="pt-2 pb-0.5">
            <p class="px-3 mb-1 text-label-caps text-secondary uppercase tracking-wider">Reporting</p>
        </div>

        <a href="{{ route('reports.index') }}"
           class="{{ $navClass('reports.*') }}"
           id="nav-reports">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">summarize</span>
            <span class="text-body-md">Supply Chain Reports</span>
        </a>

        {{-- ── Administration (Owner only) ───────────────────────── --}}
        @if($isOwner)
            <div class="pt-2 pb-0.5">
                <p class="px-3 mb-1 text-label-caps text-secondary uppercase tracking-wider">Administration</p>
            </div>

            <a href="{{ route('user_management.index') }}"
               class="{{ $navClass('user_management.*') }}"
               id="nav-user-management">
                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">manage_accounts</span>
                <span class="text-body-md">User Management</span>
            </a>

            <a href="{{ route('settings.index') }}"
               class="{{ $navClass('settings.*') }}"
               id="nav-settings">
                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">settings</span>
                <span class="text-body-md">System Settings</span>
            </a>
        @endif

    </div>

    {{-- ── Footer Links ────────────────────────────────────────────── --}}
    <div class="mt-auto pt-md border-t border-border-subtle space-y-0.5">
        <a href="#" class="nav-item" id="nav-help">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">help</span>
            <span class="text-body-md">Help Center</span>
        </a>

        <form method="POST" action="{{ route('logout') }}" id="sidebar-logout-form">
            @csrf
            <button type="submit"
                    class="nav-item w-full text-left"
                    id="sidebar-logout-btn">
                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">logout</span>
                <span class="text-body-md">Log Out</span>
            </button>
        </form>
    </div>
</nav>

{{-- ── Mobile Header (< md) ───────────────────────────────────────────── --}}
<header class="flex md:hidden items-center justify-between px-lg h-header-height w-full
               bg-surface border-b border-border-subtle fixed top-0 left-0 z-50">
    {{-- Hamburger (Alpine-toggled in future sprint) --}}
    <div class="flex items-center gap-sm">
        <button class="text-primary p-1 rounded" aria-label="Open navigation menu" id="mobile-menu-btn">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <span class="text-headline-md font-bold text-primary">CV Akuna</span>
    </div>

    {{-- Mobile right actions --}}
    <div class="flex items-center gap-md text-primary">
        <button class="p-1 rounded hover:bg-surface-container-low transition-colors" aria-label="Search">
            <span class="material-symbols-outlined">search</span>
        </button>
        {{-- Profile avatar --}}
        <div class="w-8 h-8 rounded-full bg-primary-fixed border border-border-subtle flex items-center justify-center overflow-hidden">
            <span class="material-symbols-outlined text-on-primary-fixed text-[18px]">person</span>
        </div>
    </div>
</header>
