<div>
    {{-- ── Filter & Search Bar ────────────────────────────────────────── --}}
    <div class="mb-lg">
        <x-forms.filter-bar>
            <x-slot:search>
                <x-forms.search-input 
                    placeholder="Cari kode atau nama item..." 
                    wire:model.live.debounce.300ms="search" 
                />
            </x-slot:search>

            <x-slot:filters>
                <select 
                    wire:model.live="item_type" 
                    class="bg-card-surface border border-border-divider rounded-full px-4 py-1.5 text-body-md font-body-md text-text-primary focus:ring-2 focus:ring-surface-tint focus:border-transparent outline-none h-12 cursor-pointer"
                >
                    <option value="">Semua Kategori</option>
                    <option value="bahan_baku">Bahan Baku</option>
                    <option value="finished_good">Barang Jadi</option>
                </select>

                <select 
                    wire:model.live="filterJenis" 
                    class="bg-card-surface border border-border-divider rounded-full px-4 py-1.5 text-body-md font-body-md text-text-primary focus:ring-2 focus:ring-surface-tint focus:border-transparent outline-none h-12 cursor-pointer"
                >
                    <option value="">Semua Mutasi</option>
                    <option value="masuk">Masuk (+)</option>
                    <option value="keluar">Keluar (-)</option>
                </select>

                <select 
                    wire:model.live="filterSumber" 
                    class="bg-card-surface border border-border-divider rounded-full px-4 py-1.5 text-body-md font-body-md text-text-primary focus:ring-2 focus:ring-surface-tint focus:border-transparent outline-none h-12 cursor-pointer"
                >
                    <option value="">Semua Sumber</option>
                    <option value="manual">Manual / Opname</option>
                    <option value="po_penerimaan">Penerimaan PO</option>
                    <option value="produksi">Hasil Produksi</option>
                </select>

                <div class="flex items-center gap-xs">
                    <input 
                        type="date" 
                        wire:model.live="filterStartDate"
                        class="bg-card-surface border border-border-divider rounded-full px-4 py-1.5 text-body-md font-body-md text-text-primary focus:ring-2 focus:ring-surface-tint focus:border-transparent outline-none h-12 cursor-pointer"
                    />
                    <span class="text-text-secondary select-none">s/d</span>
                    <input 
                        type="date" 
                        wire:model.live="filterEndDate"
                        class="bg-card-surface border border-border-divider rounded-full px-4 py-1.5 text-body-md font-body-md text-text-primary focus:ring-2 focus:ring-surface-tint focus:border-transparent outline-none h-12 cursor-pointer"
                    />
                </div>
            </x-slot:filters>

            <x-slot:actions>
                @if($movements->total() > 0)
                    <x-ui.secondary-button wire:click="exportCsv" class="cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">download</span>
                        Ekspor CSV
                    </x-ui.secondary-button>
                @endif
            </x-slot:actions>
        </x-forms.filter-bar>
    </div>

    {{-- ── Data Table ────────────────────────────────────────────────── --}}
    <x-tables.data-table
        :headers="['Tanggal', 'Kategori', 'Item', 'Jenis', 'Jumlah', 'Sumber', 'Referensi', 'Dicatat Oleh', 'Keterangan']"
        :items="$movements->items()"
    >
        @foreach($movements as $item)
            @php
                $relatedItem = $item->bahanBaku ?? $item->finishedGood;
                $isBahanBaku = (bool) $item->bahan_baku_id;
            @endphp
            <tr class="hover:bg-surface-container-lowest transition-colors border-b border-border-divider" wire:key="mov-{{ $item->id }}">
                <td class="px-lg py-md text-body-md text-text-secondary select-none">
                    {{ $item->tanggal->format('d/m/Y') }}
                </td>
                <td class="px-lg py-md text-body-md select-none">
                    <x-feedback.status-badge status="{{ $isBahanBaku ? 'info' : 'success' }}">
                        {{ $isBahanBaku ? 'Bahan Baku' : 'Barang Jadi' }}
                    </x-feedback.status-badge>
                </td>
                <td class="px-lg py-md text-body-md text-text-primary font-semibold">
                    {{ $relatedItem->nama ?? '—' }} ({{ $relatedItem->kode ?? '—' }})
                </td>
                <td class="px-lg py-md text-body-md">
                    <span class="font-bold {{ $item->jenis_mutasi === 'masuk' ? 'text-success-green' : 'text-danger-red' }}">
                        {{ $item->jenis_mutasi === 'masuk' ? 'Masuk' : 'Keluar' }}
                    </span>
                </td>
                <td class="px-lg py-md text-body-md text-text-primary font-semibold numeric">
                    {{ $isBahanBaku ? number_format($item->jumlah, 2) : number_format($item->jumlah, 0, ',', '.') }} {{ $relatedItem->satuan ?? '' }}
                </td>
                <td class="px-lg py-md text-body-md select-none">
                    <x-feedback.status-badge status="{{ $item->sumber === 'po_penerimaan' ? 'success' : ($item->sumber === 'produksi' ? 'info' : 'warning') }}">
                        {{ $item->sumber === 'po_penerimaan' ? 'Penerimaan PO' : ($item->sumber === 'produksi' ? 'Produksi' : 'Manual') }}
                    </x-feedback.status-badge>
                </td>
                <td class="px-lg py-md text-body-md">
                    @if($item->po_id)
                        <a href="{{ route('pesanan_pembelian.show', $item->po_id) }}" class="text-primary hover:underline font-semibold flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">shopping_cart</span>
                            PO #{{ $item->po_id }}
                        </a>
                    @elseif($item->production_entry_id)
                        <a href="{{ route('production.index') }}" class="text-primary hover:underline font-semibold flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">precision_manufacturing</span>
                            Prod #{{ $item->production_entry_id }}
                        </a>
                    @else
                        <span class="text-text-secondary">—</span>
                    @endif
                </td>
                <td class="px-lg py-md text-body-md text-text-secondary select-none">
                    {{ $item->dicatatOleh->name ?? '—' }}
                </td>
                <td class="px-lg py-md text-body-sm text-text-secondary max-w-xs truncate" title="{{ $item->keterangan }}">
                    {{ $item->keterangan }}
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-tables.empty-state
                icon="compare_arrows"
                title="Histori mutasi tidak ditemukan"
                description="Coba gunakan filter pencarian lain."
            />
        </x-slot:empty>

        <x-slot:pagination>
            <x-tables.pagination :paginator="$movements" />
        </x-slot:pagination>
    </x-tables.data-table>
</div>
