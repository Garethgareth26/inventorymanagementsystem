<div>
    {{-- ── Header bar ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md mb-lg">
        <x-forms.search-input wire:model.live.debounce.300ms="search" placeholder="Cari bahan baku..." />
    </div>

    {{-- ── Table ──────────────────────────────────────────────────────────────── --}}
    <x-tables.data-table
        :headers="['Kode', 'Nama Bahan Baku', 'Kebutuhan Tahunan', 'Biaya Pesan', 'Holding Cost/unit', 'EOQ Saat Ini', 'Aksi']"
        :items="$materials->items()"
    >
        @foreach($materials as $bb)
            @php $param = $bb->inventoryParameter; @endphp
            <tr class="hover:bg-surface-container-lowest transition-colors border-b border-border-divider"
                wire:key="eoq-{{ $bb->id }}">
                <td class="px-lg py-md text-body-md font-semibold text-text-primary">{{ $bb->kode }}</td>
                <td class="px-lg py-md text-body-md text-text-primary">
                    <div class="font-semibold">{{ $bb->nama }}</div>
                    <div class="text-xs text-text-secondary">{{ $bb->satuan }}</div>
                </td>
                <td class="px-lg py-md text-body-md numeric text-text-secondary">
                    {{ $param ? number_format((float)$param->kebutuhan_tahunan, 2) : '—' }}
                </td>
                <td class="px-lg py-md text-body-md numeric text-text-secondary">
                    {{ $param ? 'Rp '.number_format((float)$param->biaya_pesan, 0, ',', '.') : '—' }}
                </td>
                <td class="px-lg py-md text-body-md numeric text-text-secondary">
                    @if($param)
                        Rp {{ number_format((float)$bb->harga_satuan * (float)$param->biaya_simpan_persen, 0, ',', '.') }}
                    @else
                        —
                    @endif
                </td>
                <td class="px-lg py-md">
                    @if($param && (float)$param->eoq > 0)
                        <span class="font-semibold text-primary numeric">{{ number_format((float)$param->eoq, 2) }}</span>
                        <span class="text-xs text-text-secondary ml-1">{{ $bb->satuan }}</span>
                    @else
                        <x-feedback.status-badge type="neutral">Belum dihitung</x-feedback.status-badge>
                    @endif
                </td>
                <td class="px-lg py-md">
                    <a href="{{ route('eoq.show', $bb) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded bg-primary text-on-primary text-xs font-semibold hover:opacity-90 transition-opacity">
                        <span class="material-symbols-outlined text-[14px]">science</span>
                        Simulasi
                    </a>
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-tables.empty-state
                icon="functions"
                title="Tidak ada bahan baku"
                description="Tambahkan bahan baku terlebih dahulu untuk memulai simulasi EOQ."
            />
        </x-slot:empty>

        <x-slot:pagination>
            <x-tables.pagination :paginator="$materials" />
        </x-slot:pagination>
    </x-tables.data-table>
</div>
