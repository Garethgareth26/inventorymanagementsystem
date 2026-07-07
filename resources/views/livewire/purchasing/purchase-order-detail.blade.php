<div>
    {{-- ── Header Card ────────────────────────────────────────────────── --}}
    <div class="mb-lg bg-card-surface rounded-DEFAULT border border-border-divider p-lg shadow-sm">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-md select-none">
            <div>
                <div class="flex items-center gap-sm">
                    <span class="text-xs text-text-secondary uppercase font-semibold">Purchase Order</span>
                    @if($po->jenis === 'Darurat')
                        <span class="bg-negative-bg text-negative-rose text-[10px] font-bold uppercase px-2 py-0.5 rounded border border-danger-red/20 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[12px] animate-pulse">warning</span>
                            Darurat
                        </span>
                    @endif
                </div>
                <h2 class="font-headline-md text-headline-md text-text-primary mt-xs">{{ $po->kode_po }}</h2>
                <p class="text-body-md text-text-secondary mt-1">Dicatat oleh: <span class="font-semibold text-text-primary">{{ $po->dicatatOleh->name ?? '—' }}</span></p>
            </div>
            
            <div class="flex items-center gap-sm">
                <a href="{{ route('pesanan_pembelian.index') }}" class="inline-flex items-center gap-2 border border-border-divider text-text-secondary bg-transparent font-label-sm text-label-sm px-6 py-2.5 rounded-full hover:bg-surface-container-low transition-all active:scale-[0.98] cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
        {{-- ── Left: PO Details ────────────────────────────────────────── --}}
        <div class="lg:col-span-2 flex flex-col gap-lg">
            <div class="bg-card-surface rounded-DEFAULT border border-border-divider p-lg shadow-sm">
                <h3 class="font-headline-md text-headline-md text-text-primary mb-lg select-none pb-sm border-b border-border-divider">
                    Detail Informasi
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-lg select-none">
                    <div>
                        <span class="text-xs text-text-secondary uppercase font-semibold">Bahan Baku</span>
                        <p class="text-body-lg font-semibold text-text-primary mt-xs">{{ $po->bahanBaku->nama }}</p>
                        <p class="text-xs text-text-secondary mt-0.5">Kode: {{ $po->bahanBaku->kode }} · Satuan: {{ $po->bahanBaku->satuan }}</p>
                    </div>

                    <div>
                        <span class="text-xs text-text-secondary uppercase font-semibold">Supplier</span>
                        <p class="text-body-lg font-semibold text-text-primary mt-xs">{{ $po->supplier->nama }}</p>
                        <p class="text-xs text-text-secondary mt-0.5">Kontak: {{ $po->supplier->kontak }}</p>
                    </div>

                    <div class="border-t border-border-divider pt-md">
                        <span class="text-xs text-text-secondary uppercase font-semibold">Jumlah Dipesan</span>
                        <p class="text-body-lg font-semibold text-text-primary mt-xs">{{ number_format($po->jumlah, 2) }} {{ $po->bahanBaku->satuan }}</p>
                    </div>

                    <div class="border-t border-border-divider pt-md">
                        <span class="text-xs text-text-secondary uppercase font-semibold">Harga Satuan</span>
                        <p class="text-body-lg font-semibold text-text-primary mt-xs">Rp {{ number_format($po->harga_satuan, 0, ',', '.') }}</p>
                    </div>

                    <div class="border-t border-border-divider pt-md col-span-2 bg-surface-container-low p-md rounded-DEFAULT">
                        <span class="text-xs text-text-secondary uppercase font-semibold">Total Harga Pesanan</span>
                        <p class="text-headline-md font-bold text-text-primary mt-xs">Rp {{ number_format(($po->jumlah * $po->harga_satuan), 0, ',', '.') }}</p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                @can('update', $po)
                    @if($po->status === 'Menunggu' || $po->status === 'Dalam Proses')
                        <div class="flex justify-end gap-md mt-xl border-t border-border-divider pt-lg select-none">
                            @if($po->status === 'Menunggu')
                                @can('delete', $po)
                                    <button 
                                        type="button" 
                                        wire:click="cancelOrder" 
                                        class="inline-flex items-center gap-2 border border-transparent text-negative-rose bg-negative-bg hover:bg-negative-bg/80 font-label-sm text-label-sm px-8 py-2.5 rounded-full transition-all active:scale-[0.98] cursor-pointer"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">cancel</span>
                                        Batalkan Pesanan
                                    </button>
                                @endcan

                                <button 
                                    type="button" 
                                    wire:click="processOrder" 
                                    class="inline-flex items-center gap-2 bg-primary text-text-on-primary hover:bg-primary-hover font-label-sm text-label-sm px-8 py-2.5 rounded-full shadow-sm transition-all active:scale-[0.98] cursor-pointer"
                                >
                                    <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                                    Proses Pesanan
                                </button>
                            @elseif($po->status === 'Dalam Proses')
                                <button 
                                    type="button" 
                                    wire:click="openReceiveModal" 
                                    class="inline-flex items-center gap-2 bg-primary text-text-on-primary hover:bg-primary-hover font-label-sm text-label-sm px-8 py-2.5 rounded-full shadow-sm transition-all active:scale-[0.98] cursor-pointer"
                                >
                                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                    Terima Barang
                                </button>
                            @endif
                        </div>
                    @endif
                @else
                    @if($po->status === 'Menunggu' || $po->status === 'Dalam Proses')
                        <div class="mt-xl border-t border-border-divider pt-lg text-xs text-text-secondary italic select-none">
                            Akses Read-Only: Hanya Employee yang dapat mengubah status Purchase Order.
                        </div>
                    @endif
                @endcan
            </div>

            {{-- ── Linked Mutation (Only if Diterima) ──────────────────────── --}}
            @if($po->status === 'Diterima' && $mutation)
                <div class="bg-card-surface rounded-DEFAULT border border-border-divider p-lg shadow-sm">
                    <h3 class="font-headline-md text-headline-md text-text-primary mb-lg select-none pb-sm border-b border-border-divider">
                        Penerimaan Gudang (Mutasi Stok)
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-md select-none text-body-md text-text-secondary">
                        <div>
                            <span>Tanggal Penerimaan</span>
                            <p class="font-semibold text-text-primary mt-xs">{{ $po->tanggal_terima ? $po->tanggal_terima->format('d/m/Y') : '—' }}</p>
                        </div>
                        <div>
                            <span>Jumlah Diterima Fisik</span>
                            <p class="font-semibold text-text-primary mt-xs">{{ number_format($mutation->jumlah, 2) }} {{ $po->bahanBaku->satuan }}</p>
                        </div>
                        <div class="col-span-2 border-t border-border-divider pt-md">
                            <span>Keterangan Mutasi</span>
                            <p class="text-text-primary mt-xs italic">"{{ $mutation->keterangan }}"</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ── Right: Timeline & Flow ───────────────────────────────────── --}}
        <div class="bg-card-surface rounded-DEFAULT border border-border-divider p-lg shadow-sm h-fit">
            <h3 class="font-headline-md text-headline-md text-text-primary mb-lg select-none pb-sm border-b border-border-divider">
                Status Alur Kerja
            </h3>

            <div class="flex flex-col gap-lg select-none">
                <!-- Row 1: Menunggu -->
                <div class="flex items-start gap-md">
                    <div class="flex flex-col items-center">
                        <div class="rounded-full w-8 h-8 flex items-center justify-center font-bold text-xs {{ $po->status === 'Dibatalkan' ? 'bg-negative-bg text-negative-rose' : 'bg-primary text-text-on-primary' }}">
                            @if($po->status === 'Dibatalkan')
                                <span class="material-symbols-outlined text-[16px]">close</span>
                            @else
                                <span class="material-symbols-outlined text-[16px]">check</span>
                            @endif
                        </div>
                        <div class="w-[2px] h-12 bg-border-divider mt-xs"></div>
                    </div>
                    <div>
                        <h4 class="font-label-md text-label-md font-semibold text-text-primary">Order Dibuat</h4>
                        <p class="text-xs text-text-secondary mt-xs">Diajukan pada {{ $po->tanggal_pesan ? $po->tanggal_pesan->format('d/m/Y') : '—' }}</p>
                        @if($po->status === 'Menunggu')
                            <span class="inline-flex mt-sm bg-warning-bg text-warning-orange text-[10px] font-bold px-2 py-0.5 rounded border border-warning-orange/10">Menunggu Proses</span>
                        @elseif($po->status === 'Dibatalkan')
                            <span class="inline-flex mt-sm bg-negative-bg text-negative-rose text-[10px] font-bold px-2 py-0.5 rounded border border-danger-red/10">Dibatalkan</span>
                        @endif
                    </div>
                </div>

                <!-- Row 2: Dalam Proses -->
                <div class="flex items-start gap-md">
                    <div class="flex flex-col items-center">
                        <div class="rounded-full w-8 h-8 flex items-center justify-center font-bold text-xs {{ $po->status === 'Dalam Proses' || $po->status === 'Diterima' ? 'bg-primary text-text-on-primary' : 'bg-surface-container-low text-text-secondary border border-border-divider' }}">
                            @if($po->status === 'Diterima')
                                <span class="material-symbols-outlined text-[16px]">check</span>
                            @elseif($po->status === 'Dalam Proses')
                                <span class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                            @else
                                2
                            @endif
                        </div>
                        <div class="w-[2px] h-12 bg-border-divider mt-xs"></div>
                    </div>
                    <div>
                        <h4 class="font-label-md text-label-md font-semibold {{ $po->status === 'Dalam Proses' || $po->status === 'Diterima' ? 'text-text-primary' : 'text-text-secondary' }}">Pemesanan Diproses</h4>
                        <p class="text-xs text-text-secondary mt-xs">Estimasi Tiba: <span class="font-semibold text-text-primary">{{ $po->estimasi_tiba ? $po->estimasi_tiba->format('d/m/Y') : '—' }}</span></p>
                        @if($po->status === 'Dalam Proses')
                            <span class="inline-flex mt-sm bg-info-bg text-info-blue text-[10px] font-bold px-2 py-0.5 rounded border border-info-blue/10">Pengiriman Berjalan</span>
                        @endif
                    </div>
                </div>

                <!-- Row 3: Diterima -->
                <div class="flex items-start gap-md">
                    <div class="flex flex-col items-center">
                        <div class="rounded-full w-8 h-8 flex items-center justify-center font-bold text-xs {{ $po->status === 'Diterima' ? 'bg-primary text-text-on-primary' : 'bg-surface-container-low text-text-secondary border border-border-divider' }}">
                            @if($po->status === 'Diterima')
                                <span class="material-symbols-outlined text-[16px]">check</span>
                            @else
                                3
                            @endif
                        </div>
                    </div>
                    <div>
                        <h4 class="font-label-md text-label-md font-semibold {{ $po->status === 'Diterima' ? 'text-text-primary' : 'text-text-secondary' }}">Barang Diterima</h4>
                        @if($po->status === 'Diterima')
                            <p class="text-xs text-text-secondary mt-xs">Diterima di gudang pada {{ $po->tanggal_terima ? $po->tanggal_terima->format('d/m/Y') : '—' }}</p>
                            <span class="inline-flex mt-sm bg-success-bg text-success-green text-[10px] font-bold px-2 py-0.5 rounded border border-success-green/10">Selesai</span>
                        @else
                            <p class="text-xs text-text-secondary mt-xs">Belum diterima</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Receive Date Input Dialog / Modal ──────────────────────────── --}}
    <x-modal name="po-receive-modal" :show="$isReceiveModalOpen" maxWidth="sm">
        <form wire:submit.prevent="receiveOrder" class="p-lg">
            <h3 class="font-headline-md text-headline-md text-text-primary mb-md select-none">
                Konfirmasi Penerimaan Barang
            </h3>
            <p class="text-body-md text-text-secondary mb-lg select-none">
                Silakan masukkan tanggal penerimaan fisik barang ke gudang untuk dicatat ke mutasi masuk.
            </p>

            <div class="flex flex-col gap-xs mb-lg">
                <x-input-label for="tanggal_terima" value="Tanggal Terima Gudang" />
                <x-text-input 
                    id="tanggal_terima" 
                    type="date" 
                    wire:model="tanggal_terima" 
                    class="w-full" 
                />
                <x-input-error :messages="$errors->get('tanggal_terima')" />
            </div>

            <!-- Footer Actions -->
            <div class="flex justify-end gap-md select-none">
                <x-ui.secondary-button type="button" wire:click="$set('isReceiveModalOpen', false)" class="cursor-pointer">
                    Batal
                </x-ui.secondary-button>

                <x-ui.primary-button type="submit" class="cursor-pointer bg-success-green hover:bg-success-green/90 border-transparent">
                    Terima & Tambah Stok
                </x-ui.primary-button>
            </div>
        </form>
    </x-modal>
</div>
