<div>
    {{-- ── Filter & Search Bar ────────────────────────────────────────── --}}
    <div class="mb-lg">
        <x-forms.filter-bar>
            <x-slot:search>
                <x-forms.search-input 
                    placeholder="Cari barang jadi..." 
                    wire:model.live.debounce.300ms="search" 
                />
            </x-slot:search>

            <x-slot:actions>
                @can('create', App\Models\ProductionEntry::class)
                    <a href="{{ route('production.create') }}" class="inline-flex items-center gap-2 bg-primary text-text-on-primary hover:bg-primary-hover font-label-md text-label-md px-6 py-2.5 rounded-full shadow-sm transition-all active:scale-[0.98] cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Catat Produksi
                    </a>
                @endcan
            </x-slot:actions>
        </x-forms.filter-bar>
    </div>

    {{-- ── Data Table ────────────────────────────────────────────────── --}}
    <x-tables.data-table
        :headers="['ID Entri', 'Tanggal Produksi', 'Barang Jadi', 'Jumlah Diproduksi', 'Dicatat Oleh']"
        :items="$entries->items()"
    >
        @foreach($entries as $item)
            <tr class="hover:bg-surface-container-lowest transition-colors border-b border-border-divider" wire:key="entry-{{ $item->id }}">
                <td class="px-lg py-md text-body-md font-semibold text-text-primary">
                    #{{ $item->id }}
                </td>
                <td class="px-lg py-md text-body-md text-text-secondary">
                    {{ $item->tanggal_produksi->format('d/m/Y') }}
                </td>
                <td class="px-lg py-md text-body-md text-text-primary font-semibold">
                    {{ $item->finishedGood->nama }} ({{ $item->finishedGood->kode }})
                </td>
                <td class="px-lg py-md text-body-md text-text-primary numeric">
                    {{ number_format($item->jumlah_diproduksi, 0, ',', '.') }} {{ $item->finishedGood->satuan }}
                </td>
                <td class="px-lg py-md text-body-md text-text-secondary">
                    {{ $item->dicatatOleh->name }}
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-tables.empty-state
                icon="precision_manufacturing"
                title="Riwayat produksi tidak ditemukan"
                description="Coba gunakan kata kunci pencarian lain atau catat produksi baru."
            />
        </x-slot:empty>

        <x-slot:pagination>
            <x-tables.pagination :paginator="$entries" />
        </x-slot:pagination>
    </x-tables.data-table>
</div>
