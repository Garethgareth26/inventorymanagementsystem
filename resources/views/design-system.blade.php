<x-layout.app 
    title="Design System Showcase"
    pageTitle="Design System Showcase"
    pageSubtitle="Enterprise styleguide & component playground (Sprint 2.1)"
>
    <!-- Breadcrumb section -->
    <div class="mb-lg">
        <x-navigation.breadcrumb :items="[['label' => 'Design System Showcase', 'url' => null]]" />
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-12 gap-lg">
        
        <!-- ── Section 1: Buttons & Badges (col-span-12) ── -->
        <div class="col-span-12 lg:col-span-6 flex flex-col gap-lg">
            <x-ui.analytics-card title="Buttons Playground" subtitle="Primary, secondary, and icon buttons with varied sizes and icons">
                <div class="flex flex-col gap-md">
                    <!-- Standard Row -->
                    <div class="flex flex-wrap items-center gap-md">
                        <x-ui.primary-button>Primary Action</x-ui.primary-button>
                        <x-ui.primary-button icon="add">Add Product</x-ui.primary-button>
                        <x-ui.secondary-button>Secondary Action</x-ui.secondary-button>
                        <x-ui.secondary-button icon="download">Download PDF</x-ui.secondary-button>
                    </div>

                    <!-- Disabled State -->
                    <div class="flex flex-wrap items-center gap-md">
                        <x-ui.primary-button disabled class="opacity-50 cursor-not-allowed">Disabled Primary</x-ui.primary-button>
                        <x-ui.secondary-button disabled class="opacity-50 cursor-not-allowed">Disabled Secondary</x-ui.secondary-button>
                    </div>

                    <!-- Icon Buttons sizes -->
                    <div class="flex items-center gap-md pt-2">
                        <span class="text-body-md text-text-secondary mr-2">Icon Buttons:</span>
                        <x-ui.icon-button icon="notifications" size="sm" />
                        <x-ui.icon-button icon="chat_bubble" size="md" />
                        <x-ui.icon-button icon="settings" size="lg" />
                    </div>
                </div>
            </x-ui.analytics-card>
        </div>

        <div class="col-span-12 lg:col-span-6 flex flex-col gap-lg">
            <x-ui.analytics-card title="Status Badges & Feedback Indicators" subtitle="Color-coded chips with soft semantic background tints">
                <div class="flex flex-col gap-md">
                    <div class="flex flex-wrap gap-md items-center">
                        <x-feedback.status-badge type="success" icon="check_circle">On Track</x-feedback.status-badge>
                        <x-feedback.status-badge type="warning" icon="warning">Low Stock</x-feedback.status-badge>
                        <x-feedback.status-badge type="danger" icon="error">Out of Stock</x-feedback.status-badge>
                        <x-feedback.status-badge type="neutral">Archived</x-feedback.status-badge>
                    </div>

                    <!-- Interactive Toast triggers -->
                    <div class="pt-4 border-t border-border-divider">
                        <h4 class="font-headline-md text-body-lg font-bold mb-2">Interactive Toasts (Alpine-driven)</h4>
                        <p class="text-body-md text-text-secondary mb-4">Click below to dispatch browser-level notify events which simulate Livewire interactions.</p>
                        <div class="flex flex-wrap gap-md">
                            <x-ui.primary-button @click="$dispatch('notify', { message: 'Successfully synced parameters!', type: 'success' })" class="bg-primary hover:bg-surface-tint">
                                Success Toast
                            </x-ui.primary-button>
                            <x-ui.secondary-button @click="$dispatch('notify', { message: 'Parameter settings are modified.', type: 'warning' })">
                                Warning Toast
                            </x-ui.secondary-button>
                            <x-ui.secondary-button @click="$dispatch('notify', { message: 'Database connection interrupted!', type: 'danger' })" class="text-danger-red border-danger-red hover:bg-negative-bg">
                                Danger Toast
                            </x-ui.secondary-button>
                        </div>
                    </div>
                </div>
            </x-ui.analytics-card>
        </div>

        <!-- ── Section 2: Cards & Modals (col-span-12) ── -->
        <div class="col-span-12">
            <h2 class="font-headline-lg text-headline-lg font-bold text-on-background mb-md">Cards & Modals</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-lg mb-lg">
                <!-- Standard KPI -->
                <x-ui.kpi-card title="Spent this month" value="Rp 9.680.500" icon="payments" />
                
                <!-- KPI with custom trend footer -->
                <x-ui.kpi-card title="Raw Materials" value="142 SKUs" icon="inventory" iconBg="bg-primary-fixed text-primary">
                    <x-slot:footer>
                        <span class="text-primary font-bold inline-flex items-center gap-0.5"><span class="material-symbols-outlined text-[14px]">arrow_upward</span>+12.4%</span> vs last month
                    </x-slot:footer>
                </x-ui.kpi-card>

                <!-- KPI with Sparkline graphic -->
                <x-ui.kpi-card title="Total Procurement" value="23 POs" icon="shopping_cart" iconBg="bg-accent-tan-light text-warning-amber">
                    <x-slot:graphic>
                        <div class="w-16 h-10 flex items-end justify-between gap-0.5">
                            <div class="w-1.5 bg-warning-amber/40 rounded-full h-4"></div>
                            <div class="w-1.5 bg-warning-amber/40 rounded-full h-8"></div>
                            <div class="w-1.5 bg-warning-amber rounded-full h-6"></div>
                            <div class="w-1.5 bg-warning-amber rounded-full h-10"></div>
                        </div>
                    </x-slot:graphic>
                </x-ui.kpi-card>

                <!-- Hero Solid KPI Card -->
                <x-ui.kpi-card title="Monthly Activity" value="84% Usage" icon="trending_up" hero="true">
                    <x-slot:graphic>
                        <svg class="w-20 h-10" preserveAspectRatio="none" viewBox="0 0 100 50">
                            <path d="M0,40 C20,30 30,50 50,20 C70,-10 80,40 100,10" fill="none" stroke="#FFFFFF" stroke-linecap="round" stroke-width="2.5"></path>
                        </svg>
                    </x-slot:graphic>
                </x-ui.kpi-card>
            </div>
            
            <!-- Interactive Modal Trigger -->
            <x-ui.analytics-card title="Interactive Modal Dialogs" subtitle="Triggers confirmation overlay dialog components">
                <div class="flex flex-col gap-sm">
                    <p class="text-body-md text-text-secondary mb-2">Click to open a dialog. The backdrop blurs and handles escape button triggers naturally.</p>
                    <div class="flex gap-md">
                        <x-ui.primary-button @click="$dispatch('toggle-modal', { name: 'demo-modal-delete', show: true })" class="bg-danger-red hover:bg-negative-rose">
                            Open Delete Modal
                        </x-ui.primary-button>
                        <x-ui.secondary-button @click="$dispatch('toggle-modal', { name: 'demo-modal-info', show: true })">
                            Open Info Modal
                        </x-ui.secondary-button>
                    </div>

                    <!-- Modal definition 1: Danger delete -->
                    <x-feedback.confirmation-modal name="demo-modal-delete" title="Delete Raw Material SKU" type="danger">
                        Are you sure you want to permanently delete this inventory asset item? All history and usage calculations associated with it will be immediately removed. This action is irreversible.
                        
                        <x-slot:confirm>
                            <x-ui.primary-button class="bg-danger-red hover:bg-negative-rose" @click="show = false; $dispatch('notify', { message: 'Item deleted.', type: 'danger' })">
                                Confirm Delete
                            </x-ui.primary-button>
                        </x-slot:confirm>
                    </x-feedback.confirmation-modal>

                    <!-- Modal definition 2: Info details -->
                    <x-feedback.confirmation-modal name="demo-modal-info" title="System Parameters Synced" type="info">
                        System successfully updated classification algorithms to match historical 12-month data targets. The safety stock Z-factor has been set to 1.65 (95% SL).
                        
                        <x-slot:confirm>
                            <x-ui.primary-button class="bg-primary hover:bg-surface-tint" @click="show = false">
                                Close Window
                            </x-ui.primary-button>
                        </x-slot:confirm>
                    </x-feedback.confirmation-modal>
                </div>
            </x-ui.analytics-card>
        </div>

        <!-- ── Section 3: Forms & Filter Bar (col-span-12) ── -->
        <div class="col-span-12 flex flex-col gap-lg">
            <h2 class="font-headline-lg text-headline-lg font-bold text-on-background mb-xs">Tables & Forms</h2>
            
            <x-forms.filter-bar>
                <x-slot:search>
                    <x-forms.search-input placeholder="Search catalog..." />
                </x-slot:search>
                
                <x-slot:filters>
                    <!-- Simulated Category Select options -->
                    <div class="relative">
                        <select class="bg-card-surface border border-border-divider text-text-secondary rounded-full font-label-sm text-label-sm py-2 px-6 focus:ring-2 focus:ring-surface-tint focus:border-transparent select-none cursor-pointer pr-10 appearance-none">
                            <option>Category: All</option>
                            <option>Category: Raw Materials</option>
                            <option>Category: Packaging</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant pointer-events-none text-[16px]">expand_more</span>
                    </div>

                    <div class="relative">
                        <select class="bg-card-surface border border-border-divider text-text-secondary rounded-full font-label-sm text-label-sm py-2 px-6 focus:ring-2 focus:ring-surface-tint focus:border-transparent select-none cursor-pointer pr-10 appearance-none">
                            <option>Sort: Latest Added</option>
                            <option>Sort: Critical First</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant pointer-events-none text-[16px]">expand_more</span>
                    </div>
                </x-slot:filters>
                
                <x-slot:actions>
                    <x-ui.secondary-button icon="download">Export CSV</x-ui.secondary-button>
                    <x-ui.primary-button icon="add" class="bg-primary hover:bg-surface-tint">Add Item</x-ui.primary-button>
                </x-slot:actions>
            </x-forms.filter-bar>
        </div>

        <!-- ── Section 4: Data Tables Variants (col-span-12) ── -->
        <!-- Variant A: Standard Active Table -->
        <div class="col-span-12">
            <x-ui.analytics-card title="Data Table: Standard populated rows" subtitle="Renders padded cells with divider lines and custom pagination footers">
                @php
                    $dummyItems = [
                        ['id' => 'MB-001', 'name' => 'Bahan Baku Gandum', 'category' => 'Raw Materials', 'stock' => 1250, 'status' => 'success', 'statusText' => 'In Stock'],
                        ['id' => 'MB-002', 'name' => 'Kemasan Plastik Premium', 'category' => 'Packaging', 'stock' => 120, 'status' => 'warning', 'statusText' => 'Low Stock'],
                        ['id' => 'MB-003', 'name' => 'Garam Industri Kristal', 'category' => 'Raw Materials', 'stock' => 0, 'status' => 'danger', 'statusText' => 'Out of Stock'],
                    ];
                @endphp

                <x-tables.data-table :headers="['Material SKU', 'Material Name', 'Category', 'Current Stock', 'Status', 'Actions']" :items="$dummyItems">
                    @foreach($dummyItems as $item)
                        <tr class="hover:bg-surface-container-low transition-colors duration-150">
                            <td class="px-lg py-md font-semibold text-text-primary numeric select-all">{{ $item['id'] }}</td>
                            <td class="px-lg py-md text-text-primary">{{ $item['name'] }}</td>
                            <td class="px-lg py-md text-text-secondary">{{ $item['category'] }}</td>
                            <td class="px-lg py-md text-text-primary font-bold numeric">{{ number_format($item['stock']) }} kg</td>
                            <td class="px-lg py-md">
                                <x-feedback.status-badge :type="$item['status']">
                                    {{ $item['statusText'] }}
                                </x-feedback.status-badge>
                            </td>
                            <td class="px-lg py-md flex items-center gap-sm">
                                <x-ui.icon-button icon="edit" size="sm" class="text-primary hover:bg-surface-container-high" title="Edit Item" />
                                <x-ui.icon-button icon="delete" size="sm" class="text-danger-red hover:bg-negative-bg" title="Delete Item" />
                            </td>
                        </tr>
                    @endforeach

                    <x-slot:pagination>
                        <x-tables.pagination :current="1" :total="43" :perPage="10" />
                    </x-slot:pagination>
                </x-tables.data-table>
            </x-ui.analytics-card>
        </div>

        <!-- Variant B: Loading Table -->
        <div class="col-span-12">
            <x-ui.analytics-card title="Data Table: Loading State" subtitle="Renders animated shimmer skeleton placeholders when loading is active">
                <x-tables.data-table :headers="['Material SKU', 'Material Name', 'Category', 'Current Stock', 'Status', 'Actions']" :items="[]" :loading="true">
                    <x-slot:pagination>
                        <x-tables.pagination :current="1" :total="0" :perPage="10" />
                    </x-slot:pagination>
                </x-tables.data-table>
            </x-ui.analytics-card>
        </div>

        <!-- Variant C: Empty Table -->
        <div class="col-span-12">
            <x-ui.analytics-card title="Data Table: Empty State" subtitle="Renders decorative illustrations when listing returns 0 records">
                <x-tables.data-table :headers="['Material SKU', 'Material Name', 'Category', 'Current Stock', 'Status', 'Actions']" :items="[]" :loading="false">
                    <x-slot:empty>
                        <x-tables.empty-state title="No Materials Registered" description="Get started by creating your first Raw Material or syncing from database.">
                            <x-slot:action>
                                <x-ui.primary-button icon="add" class="bg-primary hover:bg-surface-tint">Add Your First Material</x-ui.primary-button>
                            </x-slot:action>
                        </x-tables.empty-state>
                    </x-slot:empty>
                </x-tables.data-table>
            </x-ui.analytics-card>
        </div>

    </div>
</x-layout.app>
