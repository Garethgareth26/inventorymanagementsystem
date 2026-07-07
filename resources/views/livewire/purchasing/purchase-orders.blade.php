<div>
    {{-- ── Filter & Search Bar ────────────────────────────────────────── --}}
    <div class="mb-lg">
        <x-forms.filter-bar>
            <x-slot:search>
                <x-forms.search-input 
                    placeholder="Cari kode PO..." 
                    wire:model.live.debounce.300ms="search" 
                />
            </x-slot:search>

            <x-slot:filters>
                <select 
                    wire:model.live="filterStatus" 
                    class="bg-card-surface border border-border-divider rounded-full px-4 py-1.5 text-body-md font-body-md text-text-primary focus:ring-2 focus:ring-surface-tint focus:border-transparent outline-none h-12 cursor-pointer"
                >
                    <option value="">Semua Status</option>
                    <option value="Menunggu">Menunggu</option>
                    <option value="Dalam Proses">Dalam Proses</option>
                    <option value="Diterima">Diterima</option>
                    <option value="Dibatalkan">Dibatalkan</option>
                </select>

                <select 
                    wire:model.live="filterSupplier" 
                    class="bg-card-surface border border-border-divider rounded-full px-4 py-1.5 text-body-md font-body-md text-text-primary focus:ring-2 focus:ring-surface-tint focus:border-transparent outline-none h-12 cursor-pointer max-w-xs"
                >
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->nama }}</option>
                    @endforeach
                </select>

                <div class="flex items-center gap-xs">
                    <input 
                        type="date" 
                        wire:model.live="filterStartDate"
                        class="bg-card-surface border border-border-divider rounded-full px-4 py-1.5 text-body-md font-body-md text-text-primary focus:ring-2 focus:ring-surface-tint focus:border-transparent outline-none h-12 cursor-pointer"
                        placeholder="Mulai"
                    />
                    <span class="text-text-secondary select-none">s/d</span>
                    <input 
                        type="date" 
                        wire:model.live="filterEndDate"
                        class="bg-card-surface border border-border-divider rounded-full px-4 py-1.5 text-body-md font-body-md text-text-primary focus:ring-2 focus:ring-surface-tint focus:border-transparent outline-none h-12 cursor-pointer"
                        placeholder="Selesai"
                    />
                </div>
            </x-slot:filters>

            <x-slot:actions>
                @can('create', App\Models\PesananPembelian::class)
                    <a href="{{ route('pesanan_pembelian.create') }}" class="inline-flex items-center gap-2 bg-primary text-text-on-primary hover:bg-primary-hover font-label-md text-label-md px-6 py-2.5 rounded-full shadow-sm transition-all active:scale-[0.98] cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Buat PO Baru
                    </a>
                @endcan
            </x-slot:actions>
        </x-forms.filter-bar>
    </div>

    {{-- ── Data Table ────────────────────────────────────────────────── --}}
    <x-tables.data-table
        :headers="['Kode PO', 'Tanggal', 'Bahan Baku', 'Supplier', 'Jumlah', 'Total Harga', 'Status', 'Aksi']"
        :items="$purchaseOrders->items()"
    >
        @foreach($purchaseOrders as $item)
            <tr class="hover:bg-surface-container-lowest transition-colors border-b border-border-divider" wire:key="po-{{ $item->id }}">
                <td class="px-lg py-md text-body-md font-semibold text-text-primary">
                    <span class="flex items-center gap-xs">
                        @if($item->jenis === 'Darurat')
                            <span class="material-symbols-outlined text-negative-rose text-[18px] animate-pulse" title="Pesanan Darurat">warning</span>
                        @endif
                        {{ $item->kode_po }}
                    </span>
                </td>
                <td class="px-lg py-md text-body-md text-text-secondary">
                    {{ $item->tanggal_pesan->format('d/m/Y') }}
                </td>
                <td class="px-lg py-md text-body-md text-text-primary">
                    {{ $item->bahanBaku->nama }} ({{ $item->bahanBaku->kode }})
                </td>
                <td class="px-lg py-md text-body-md text-text-secondary">
                    {{ $item->supplier->nama }}
                </td>
                <td class="px-lg py-md text-body-md text-text-primary numeric">
                    {{ number_format($item->jumlah, 2) }} {{ $item->bahanBaku->satuan }}
                </td>
                <td class="px-lg py-md text-body-md text-text-primary numeric">
                    Rp {{ number_format(($item->jumlah * $item->harga_satuan), 0, ',', '.') }}
                </td>
                <td class="px-lg py-md text-body-md">
                    <x-feedback.status-badge status="{{ $item->status === 'Diterima' ? 'success' : ($item->status === 'Dibatalkan' ? 'danger' : ($item->status === 'Dalam Proses' ? 'info' : 'warning')) }}">
                        {{ $item->status }}
                    </x-feedback.status-badge>
                </td>
                <td class="px-lg py-md">
                    <div class="flex items-center gap-sm">
                        <a href="{{ route('pesanan_pembelian.show', $item->id) }}" 
                           class="text-text-secondary hover:text-primary p-1 rounded hover:bg-surface-container-low transition-colors inline-flex items-center"
                           title="Detail PO"
                        >
                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                        </a>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-tables.empty-state
                icon="shopping_cart"
                title="Purchase Order tidak ditemukan"
                description="Coba gunakan filter lain atau buat PO baru."
            />
        </x-slot:empty>

        <x-slot:pagination>
            <x-tables.pagination :paginator="$purchaseOrders" />
        </x-slot:pagination>
    </x-tables.data-table>
</div>
