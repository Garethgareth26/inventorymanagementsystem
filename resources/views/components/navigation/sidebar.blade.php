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
            'title' => 'Raw Materials',
            'capability' => 'material.view',
        ],
        [
            'route' => 'barang_jadi.index',
            'icon' => 'deployed_code',
            'title' => 'Finished Goods',
            'capability' => 'finished-good.view',
        ],
        [
            'route' => 'suppliers.index',
            'icon' => 'group',
            'title' => 'Suppliers',
            'capability' => 'supplier.view',
        ],
        [
            'route' => 'bom.index',
            'icon' => 'account_tree',
            'title' => 'Bill of Materials',
            'capability' => 'bom.view',
        ],
        [
            'route' => 'pesanan_pembelian.index',
            'icon' => 'shopping_cart',
            'title' => 'Purchasing',
            'capability' => 'procurement.view',
        ],
        [
            'route' => 'production.index',
            'icon' => 'precision_manufacturing',
            'title' => 'Production',
            'capability' => 'production.view',
        ],
        [
            'route' => 'mutasi_stok.index',
            'icon' => 'inventory_2',
            'title' => 'Stock Mutation',
            'capability' => 'stock.view',
        ],
        [
            'route' => 'eoq.index',
            'icon' => 'calculate',
            'title' => 'EOQ Simulation',
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
            'title' => 'ABC Analysis',
            'capability' => 'parameter.view',
        ],
        [
            'route' => 'reports.index',
            'icon' => 'assignment',
            'title' => 'Reports',
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

<!-- SideNavBar (Stitch v2 capsule style) -->
<aside class="bg-surface-container-lowest fixed left-4 top-4 bottom-4 w-16 rounded-full flex flex-col items-center py-6 shadow-soft-ambient z-50 justify-between border border-border-divider">
    <div class="flex flex-col items-center gap-6 w-full">
        <!-- Logo / Brand Avatar -->
        <a href="{{ $user?->isOwner() ? route('owner.dashboard') : route('employee.dashboard') }}" 
           class="w-10 h-10 mb-2 rounded-full overflow-hidden flex-shrink-0 border-2 border-primary-container p-0.5" 
           title="CV Akuna Home">
            <div class="w-full h-full bg-primary-container rounded-full flex items-center justify-center text-on-primary">
                <span class="material-symbols-outlined text-[18px]">inventory_2</span>
            </div>
        </a>

        <!-- Nav Items Section -->
        <nav class="flex flex-col items-center gap-4 w-full px-2 overflow-y-auto max-h-[calc(100vh-280px)] scrollbar-thin">
            @foreach($filteredItems as $item)
                @php
                    $isActive = request()->routeIs($item['route']) || (request()->path() === ltrim(route($item['route'], [], false), '/'));
                @endphp
                <a class="{{ $isActive ? 'bg-on-background text-on-primary shadow-soft-ambient' : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-high' }} rounded-full w-10 h-10 flex items-center justify-center scale-95 hover:scale-100 active:scale-90 transition-all duration-200" 
                   href="{{ route($item['route']) }}" 
                   title="{{ $item['title'] }}">
                    <span class="material-symbols-outlined {{ $isActive ? 'icon-fill' : '' }} text-[20px]">{{ $item['icon'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Footer actions -->
    <div class="flex flex-col items-center gap-4 w-full px-2">
        <!-- Settings (Only for users with settings capability or owner) -->
        @if ($user?->hasCapability('settings.manage') || $user?->isOwner())
            <a class="{{ request()->routeIs('settings.*') ? 'bg-on-background text-on-primary' : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-high' }} rounded-full w-10 h-10 flex items-center justify-center scale-95 hover:scale-100 transition-all duration-200" 
               href="{{ route('settings.index') }}" 
               title="Settings">
                <span class="material-symbols-outlined text-[20px]">settings</span>
            </a>
        @endif

        <!-- User Dropdown Menu wrapper -->
        <x-navigation.user-dropdown />
    </div>
</aside>
