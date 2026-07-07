<?php

namespace App\Livewire\Purchasing;

use App\Models\BahanBaku;
use App\Models\PesananPembelian;
use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\DashboardQueryService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Component to create a new Purchase Order.
 */
class CreatePurchaseOrder extends Component
{
    use AuthorizesRequests;

    public string $kode_po = '';

    public string $jenis = PesananPembelian::JENIS_RUTIN;

    public ?int $bahan_baku_id = null;

    public ?int $supplier_id = null;

    public float $jumlah = 0.0;

    public float $harga_satuan = 0.0;

    public string $tanggal_pesan = '';

    // Computed / helper attributes
    public string $estimasi_tiba = '';

    public float $harga_dasar = 0.0; // for reference

    /**
     * Mount component.
     */
    public function mount(): void
    {
        $this->authorize('create', PesananPembelian::class);
        $this->tanggal_pesan = now()->toDateString();

        // Generate sequential PO code
        $lastPo = PesananPembelian::orderBy('id', 'desc')->first();
        $nextId = $lastPo ? $lastPo->id + 1 : 1;
        $this->kode_po = 'PO-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Hook when bahan_baku_id is updated.
     */
    public function updatedBahanBakuId(mixed $value): void
    {
        if (empty($value)) {
            $this->resetAutoFilledFields();

            return;
        }

        $bb = BahanBaku::with('inventoryParameter')->find((int) $value);
        if ($bb) {
            $this->supplier_id = $bb->supplier_id;
            $this->harga_dasar = (float) $bb->harga_satuan;

            // Recalculate price based on order type (Rutin or Darurat)
            $this->recalculatePrice();

            // Auto-fill quantity with EOQ if it's Rutin
            if ($this->jenis === PesananPembelian::JENIS_RUTIN) {
                $eoq = (float) ($bb->inventoryParameter->eoq ?? 0.0);
                $this->jumlah = $eoq > 0 ? $eoq : 1.0;
            } else {
                $this->jumlah = 0.0; // let employee fill emergency amount manually
            }

            $this->calculateEstimasiTiba($bb->lead_time_hari);
        } else {
            $this->resetAutoFilledFields();
        }
    }

    /**
     * Hook when jenis (Rutin / Darurat) is updated.
     */
    public function updatedJenis(): void
    {
        if ($this->bahan_baku_id) {
            $bb = BahanBaku::find($this->bahan_baku_id);
            if ($bb) {
                $this->recalculatePrice();
                // If switching to Darurat, clear the amount so they specify exact emergency shortfall
                if ($this->jenis === PesananPembelian::JENIS_DARURAT) {
                    $this->jumlah = 0.0;
                } else {
                    $eoq = (float) ($bb->inventoryParameter->eoq ?? 0.0);
                    $this->jumlah = $eoq > 0 ? $eoq : 1.0;
                }
            }
        }
    }

    /**
     * Hook when tanggal_pesan is updated.
     */
    public function updatedTanggalPesan(): void
    {
        if ($this->bahan_baku_id) {
            $bb = BahanBaku::find($this->bahan_baku_id);
            if ($bb) {
                $this->calculateEstimasiTiba($bb->lead_time_hari);
            }
        }
    }

    /**
     * Recalculate unit price incorporating any emergency surcharges (+20%).
     */
    private function recalculatePrice(): void
    {
        if ($this->jenis === PesananPembelian::JENIS_DARURAT) {
            $this->harga_satuan = $this->harga_dasar * 1.20;
        } else {
            $this->harga_satuan = $this->harga_dasar;
        }
    }

    /**
     * Compute estimated arrival date.
     */
    private function calculateEstimasiTiba(int $leadTimeDays): void
    {
        if (! empty($this->tanggal_pesan)) {
            $this->estimasi_tiba = Carbon::parse($this->tanggal_pesan)
                ->addDays($leadTimeDays)
                ->toDateString();
        }
    }

    /**
     * Reset fields when raw material picker is cleared.
     */
    private function resetAutoFilledFields(): void
    {
        $this->supplier_id = null;
        $this->harga_dasar = 0.0;
        $this->harga_satuan = 0.0;
        $this->jumlah = 0.0;
        $this->estimasi_tiba = '';
    }

    /**
     * Create PO transaction.
     */
    public function save()
    {
        $this->authorize('create', PesananPembelian::class);

        $rules = [
            'kode_po' => 'required|string|max:30|unique:pesanan_pembelian,kode_po',
            'jenis' => 'required|in:Rutin,Darurat',
            'bahan_baku_id' => 'required|exists:bahan_baku,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'jumlah' => 'required|numeric|gt:0',
            'harga_satuan' => 'required|numeric|min:0',
            'tanggal_pesan' => 'required|date',
        ];

        $validated = $this->validate($rules);

        // Fetch lead time to calculate estimasi_tiba on server side securely
        $bb = BahanBaku::findOrFail($this->bahan_baku_id);
        $validated['estimasi_tiba'] = Carbon::parse($this->tanggal_pesan)
            ->addDays($bb->lead_time_hari)
            ->toDateString();

        $validated['status'] = PesananPembelian::STATUS_MENUNGGU;
        $validated['dicatat_oleh'] = auth()->id();

        DB::transaction(function () use ($validated) {
            $po = PesananPembelian::create($validated);

            AuditLogger::log(
                auth()->user(),
                'po.create',
                $po,
                null,
                $po->toArray()
            );

            app(DashboardQueryService::class)->invalidateCache();
        });

        $this->dispatch('notify', message: 'Purchase Order baru berhasil dibuat.', type: 'success');

        return redirect()->route('pesanan_pembelian.index');
    }

    /**
     * Render view.
     */
    public function render()
    {
        $materials = BahanBaku::orderBy('nama')->get();
        $suppliers = Supplier::orderBy('nama')->get();

        return view('livewire.purchasing.create-purchase-order', [
            'materials' => $materials,
            'suppliers' => $suppliers,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Buat PO Baru',
            'pageSubtitle' => 'Buat pesanan pembelian bahan baku baru',
        ]);
    }
}
