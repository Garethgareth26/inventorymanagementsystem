<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <!-- Bell Button -->
    <button @click="open = !open" 
            class="bg-card-surface text-text-primary rounded-full w-12 h-12 flex items-center justify-center shadow-sm hover:bg-surface-container-low transition-colors duration-150 relative focus:outline-none focus:ring-2 focus:ring-surface-tint"
            title="Notifications">
        <span class="material-symbols-outlined text-[24px]">notifications</span>
        @if($criticalCount > 0)
            <span class="absolute top-3 right-3 w-4 h-4 bg-negative-rose text-white text-[9px] font-bold rounded-full flex items-center justify-center">
                {{ $criticalCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-2 w-80 bg-surface-container-lowest rounded-xl shadow-soft-ambient border border-border-divider overflow-hidden z-50"
         style="display: none;">
        
        <div class="px-4 py-3 border-b border-border-divider bg-surface-container-low">
            <h3 class="font-headline-sm text-text-primary font-bold flex items-center gap-2">
                <span class="material-symbols-outlined text-negative-rose text-[18px]">emergency_heat</span>
                Peringatan Stok
            </h3>
        </div>

        <div class="max-h-64 overflow-y-auto scrollbar-thin">
            @if($criticalCount > 0)
                <div class="flex flex-col">
                    @foreach($criticalItems as $item)
                        <a href="{{ route('pesanan_pembelian.create', ['bahan_baku_id' => $item['id'], 'jenis' => 'Darurat', 'jumlah' => max(1, round($item['defisit']))]) }}" 
                           class="px-4 py-3 border-b border-border-divider hover:bg-surface-container transition-colors flex flex-col gap-1"
                           wire:navigate>
                            <span class="font-body-md text-text-primary font-semibold">{{ $item['nama'] }}</span>
                            <span class="font-label-sm text-negative-rose">
                                Defisit stok: {{ number_format($item['defisit'], 1) }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="px-4 py-6 text-center text-text-secondary flex flex-col items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[32px] text-primary">check_circle</span>
                    <span class="font-body-md">Semua stok aman!</span>
                </div>
            @endif
        </div>
        
        @if($criticalCount > 0)
            <div class="px-4 py-2 border-t border-border-divider bg-surface-container text-center">
                <a href="{{ route('reorder_point.index') }}" class="text-xs font-semibold text-primary hover:underline" wire:navigate>
                    Lihat semua material kritis
                </a>
            </div>
        @endif
    </div>
</div>
