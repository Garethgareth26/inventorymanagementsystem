@php
    $user = auth()->user();

    // Define the sidebar items structure.
    // Each item represents a navigation node with its route, icon, label, and required capability.
    $navItems = [
        [
            'route' => $user?->isOwner() ? 'owner.dashboard' : 'employee.dashboard',
            'icon' => 'grid_view',
            'title' => 'Dashboard',
            'capability' => null, // accessible to everyone
        ],
        [
            'route' => 'bahan_baku.index',
            'icon' => 'inventory',
            'title' => 'Bahan Baku',
            'capability' => 'material.view',
        ],
        [
            'route' => 'barang_jadi.index',
            'icon' => 'deployed_code',
            'title' => 'Barang Jadi',
            'capability' => 'finished-good.view',
        ],
        [
            'route' => 'suppliers.index',
            'icon' => 'group',
            'title' => 'Supplier',
            'capability' => 'supplier.view',
        ],
        [
            'route' => 'bom.index',
            'icon' => 'account_tree',
            'title' => 'Resep (BOM)',
            'capability' => 'bom.view',
        ],
        [
            'route' => 'pesanan_pembelian.index',
            'icon' => 'shopping_cart',
            'title' => 'Pesanan Pembelian',
            'capability' => 'procurement.view',
        ],
        [
            'route' => 'production.index',
            'icon' => 'precision_manufacturing',
            'title' => 'Produksi',
            'capability' => 'production.view',
        ],
        [
            'route' => 'mutasi_stok.index',
            'icon' => 'inventory_2',
            'title' => 'Mutasi Stok',
            'capability' => 'stock.view',
        ],
        [
            'route' => 'eoq.index',
            'icon' => 'calculate',
            'title' => 'Simulasi EOQ',
            'capability' => 'parameter.view',
        ],
        [
            'route' => 'safety_stock.index',
            'icon' => 'security',
            'title' => 'Safety Stock',
            'capability' => 'parameter.view',
        ],
        [
            'route' => 'reorder_point.index',
            'icon' => 'cycle',
            'title' => 'Reorder Point',
            'capability' => 'parameter.view',
        ],
        [
            'route' => 'abc_analysis.index',
            'icon' => 'analytics',
            'title' => 'Analisis ABC',
            'capability' => 'parameter.view',
        ],
        [
            'route' => 'reports.index',
            'icon' => 'assignment',
            'title' => 'Laporan',
            'capability' => 'report.view',
        ],
    ];

    // Filter items based on user capability if specified.
    $filteredItems = array_filter($navItems, function ($item) use ($user) {
        if (is_null($item['capability'])) {
            return true;
        }
        return $user?->hasCapability($item['capability']) ?? false;
    });
@endphp

<!-- Mobile overlay -->
<div x-show="sidebarOpen" 
     @click="sidebarOpen = false"
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-black/50 z-40 lg:hidden" style="display: none;"></div>

<!-- SideNavBar -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-[120%]'"
       class="bg-surface-container-lowest fixed left-4 top-4 bottom-4 w-64 rounded-3xl flex flex-col items-stretch px-4 py-6 shadow-soft-ambient z-50 justify-between border border-border-divider transition-transform duration-300 lg:translate-x-0">
    <div class="flex flex-col items-start gap-6 w-full">
        <!-- Logo / Brand Avatar -->
        <a href="{{ $user?->isOwner() ? route('owner.dashboard') : route('employee.dashboard') }}" 
           class="flex items-center gap-3 mb-2 px-2 hover:opacity-80 transition-opacity" 
           title="CV Akuna Home"
           wire:navigate>
            <div class="w-10 h-10 rounded-full flex-shrink-0 border-2 border-primary-container p-0.5 overflow-hidden">
                <div class="w-full h-full bg-primary-container rounded-full flex items-center justify-center text-on-primary">
                    <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                </div>
            </div>
            <span class="font-headline-sm font-bold text-primary">CV Akuna</span>
        </a>

        <!-- Nav Items Section -->
        <nav class="flex flex-col items-start gap-1 w-full overflow-y-auto max-h-[calc(100vh-280px)] scrollbar-thin">
            @foreach($filteredItems as $item)
                @php
                    $isActive = request()->routeIs($item['route']) || (request()->path() === ltrim(route($item['route'], [], false), '/'));
                @endphp
                <a class="{{ $isActive ? 'bg-on-background text-on-primary shadow-soft-ambient' : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-high' }} rounded-full w-full h-11 flex items-center justify-start gap-3 px-4 scale-[0.98] hover:scale-100 active:scale-95 transition-all duration-200" 
                   href="{{ route($item['route']) }}" 
                   title="{{ $item['title'] }}"
                   wire:navigate>
                    <span class="material-symbols-outlined {{ $isActive ? 'icon-fill' : '' }} text-[20px]">{{ $item['icon'] }}</span>
                    <span class="font-label-md text-label-md font-semibold truncate">{{ $item['title'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Footer actions -->
    <div class="flex flex-col items-start gap-2 w-full pt-4 border-t border-border-divider">
        <!-- Settings -->
        @if ($user?->hasCapability('settings.manage') || $user?->isOwner())
            <a class="{{ request()->routeIs('settings.*') ? 'bg-on-background text-on-primary' : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-high' }} rounded-full w-full h-11 px-4 flex items-center justify-start gap-3 scale-[0.98] hover:scale-100 transition-all duration-200" 
               href="{{ route('settings.index') }}" 
               title="Pengaturan"
               wire:navigate>
                <span class="material-symbols-outlined text-[20px]">settings</span>
                <span class="font-label-md text-label-md font-semibold">Pengaturan</span>
            </a>
        @endif

        <!-- User Dropdown Menu wrapper -->
        <x-navigation.user-dropdown />
    </div>
</aside>
