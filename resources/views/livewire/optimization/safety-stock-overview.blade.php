<div>
    {{-- ── Search ───────────────────────────────────────────────────────────────── --}}
    <div class="mb-lg">
        <x-forms.search-input wire:model.live.debounce.300ms="search" placeholder="Cari bahan baku..." />
    </div>

    {{-- ── Table ────────────────────────────────────────────────────────────────── --}}
    <x-tables.data-table
        :headers="['Kode', 'Nama Bahan Baku', 'SD Harian', 'Lead Time', 'Z-Factor', 'Safety Stock', 'Aksi']"
        :items="$materials->items()"
    >
        @foreach($materials as $bb)
            @php $param = $bb->inventoryParameter; @endphp
            <tr class="hover:bg-surface-container-lowest transition-colors border-b border-border-divider"
                wire:key="ss-{{ $bb->id }}">
                <td class="px-lg py-md text-body-md font-semibold text-text-primary">{{ $bb->kode }}</td>
                <td class="px-lg py-md text-body-md text-text-primary">
                    <div class="font-semibold">{{ $bb->nama }}</div>
                    <div class="text-xs text-text-secondary">Lead time: {{ $bb->lead_time_hari }} hari</div>
                </td>
                <td class="px-lg py-md text-body-md numeric text-text-secondary">
                    {{ $param ? number_format((float)$param->standar_deviasi_harian, 4) : '—' }}
                </td>
                <td class="px-lg py-md text-body-md numeric text-text-secondary">
                    {{ $bb->lead_time_hari }} hari
                </td>
                <td class="px-lg py-md text-body-md numeric text-text-secondary">
                    {{ $param ? number_format((float)$param->z_factor, 2) : '—' }}
                </td>
                <td class="px-lg py-md">
                    @if($param && (float)$param->safety_stock > 0)
                        <span class="font-semibold text-primary numeric">{{ number_format((float)$param->safety_stock, 2) }}</span>
                        <span class="text-xs text-text-secondary ml-1">{{ $bb->satuan }}</span>
                    @else
                        <x-feedback.status-badge type="neutral">Belum dihitung</x-feedback.status-badge>
                    @endif
                </td>
                <td class="px-lg py-md">
                    <a href="{{ route('safety_stock.show', $bb) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded bg-primary text-on-primary text-xs font-semibold hover:opacity-90 transition-opacity">
                        <span class="material-symbols-outlined text-[14px]">science</span>
                        Simulasi
                    </a>
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-tables.empty-state
                icon="shield"
                title="Tidak ada bahan baku"
                description="Tambahkan bahan baku terlebih dahulu."
            />
        </x-slot:empty>

        <x-slot:pagination>
            <x-tables.pagination :paginator="$materials" />
        </x-slot:pagination>
    </x-tables.data-table>
</div>
