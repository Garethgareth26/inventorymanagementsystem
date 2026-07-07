<div>
    <x-feedback.toast />

    {{-- ── Summary KPI badges ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-3 gap-md mb-lg">
        <x-ui.kpi-card title="Material Kritis" icon="emergency_heat" iconBg="bg-negative-bg text-negative-rose">
            <x-slot:value><span class="text-negative-rose numeric">{{ $counts['critical'] }}</span></x-slot:value>
            <x-slot:footer><span class="text-xs text-text-secondary">Stok ≤ ROP</span></x-slot:footer>
        </x-ui.kpi-card>
        <x-ui.kpi-card title="Mendekati ROP" icon="warning" iconBg="bg-accent-tan-light text-warning-amber">
            <x-slot:value><span class="text-warning-amber numeric">{{ $counts['near'] }}</span></x-slot:value>
            <x-slot:footer><span class="text-xs text-text-secondary">Stok ≤ 1.2× ROP</span></x-slot:footer>
        </x-ui.kpi-card>
        <x-ui.kpi-card title="Stok Aman" icon="check_circle" iconBg="bg-primary-fixed text-primary">
            <x-slot:value><span class="text-primary numeric">{{ $counts['ok'] }}</span></x-slot:value>
            <x-slot:footer><span class="text-xs text-text-secondary">Di atas ROP</span></x-slot:footer>
        </x-ui.kpi-card>
    </div>

    {{-- ── Filter bar ──────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row gap-sm mb-lg">
        <x-forms.search-input wire:model.live.debounce.300ms="search" placeholder="Cari bahan baku..." />
        <select wire:model.live="filterStatus"
                class="rounded border border-border-divider bg-surface-container px-3 py-2 text-body-md text-text-primary focus:outline-none focus:ring-1 focus:ring-primary">
            <option value="">Semua Status</option>
            <option value="critical">🔴 Kritis</option>
            <option value="near">🟡 Mendekati</option>
            <option value="ok">🟢 Aman</option>
        </select>
    </div>

    {{-- ── Table ────────────────────────────────────────────────────────────────── --}}
    <x-tables.data-table
        :headers="['Kode', 'Nama', 'Stok Saat Ini', 'ROP', 'Safety Stock', 'Status', 'Aksi']"
        :items="$materials"
    >
        @foreach($materials as $item)
            <tr class="hover:bg-surface-container-lowest transition-colors border-b border-border-divider"
                wire:key="rop-{{ $item['id'] }}">
                <td class="px-lg py-md text-body-md font-semibold text-text-primary">{{ $item['kode'] }}</td>
                <td class="px-lg py-md text-body-md text-text-primary">{{ $item['nama'] }}</td>
                <td class="px-lg py-md text-body-md numeric">
                    {{ number_format($item['stok_saat_ini'], 2) }} {{ $item['satuan'] }}
                </td>
                <td class="px-lg py-md text-body-md numeric text-text-secondary">
                    {{ $item['rop'] > 0 ? number_format($item['rop'], 2) : '—' }} {{ $item['rop'] > 0 ? $item['satuan'] : '' }}
                </td>
                <td class="px-lg py-md text-body-md numeric text-text-secondary">
                    {{ $item['safety_stock'] > 0 ? number_format($item['safety_stock'], 2) : '—' }}
                </td>
                <td class="px-lg py-md">
                    @if($item['status'] === 'critical')
                        <x-feedback.status-badge type="danger" icon="emergency_heat">Kritis</x-feedback.status-badge>
                    @elseif($item['status'] === 'near')
                        <x-feedback.status-badge type="warning" icon="warning">Mendekati</x-feedback.status-badge>
                    @else
                        <x-feedback.status-badge type="success" icon="check_circle">Aman</x-feedback.status-badge>
                    @endif
                </td>
                <td class="px-lg py-md">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('reorder_point.show', $item['id']) }}"
                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-surface-container-high text-text-primary text-xs font-semibold hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-[13px]">science</span>
                            Simulasi
                        </a>
                        @if($item['status'] === 'critical')
                            <a href="{{ route('pesanan_pembelian.create', ['bahan_baku_id' => $item['id'], 'jenis' => 'Darurat', 'jumlah' => max(1, round($item['defisit']))]) }}"
                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-negative-bg text-negative-rose text-xs font-semibold hover:opacity-80 transition-opacity">
                                <span class="material-symbols-outlined text-[13px]">emergency</span>
                                Buat PO Darurat
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-tables.empty-state
                icon="check_circle"
                title="Tidak ada material"
                description="Tidak ada bahan baku yang sesuai filter."
            />
        </x-slot:empty>
    </x-tables.data-table>
</div>
