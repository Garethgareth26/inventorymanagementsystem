<?php

namespace App\Livewire\Production;

use App\Models\FinishedGood;
use App\Models\ProductionEntry;
use App\Services\ProductionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Component to record a new Production Entry.
 */
class CreateProduction extends Component
{
    use AuthorizesRequests;

    public ?int $finished_goods_id = null;

    public float $jumlah_diproduksi = 1.0;

    public string $tanggal_produksi = '';

    public string $keterangan = '';

    // Error flags
    public bool $hasStockShortfall = false;

    /**
     * Mount component.
     */
    public function mount(): void
    {
        $this->authorize('create', ProductionEntry::class);
        $this->tanggal_produksi = now()->toDateString();
    }

    /**
     * Live computed property for BOM ingredient preview and stock check.
     *
     * @return array<int, array{id: int, kode: string, nama: string, satuan: string, required: float, available: float, rop: float, is_insufficient: bool, is_near_rop: bool}>
     */
    public function getIngredientsPreviewProperty(): array
    {
        if (! $this->finished_goods_id || $this->jumlah_diproduksi <= 0) {
            return [];
        }

        $fg = FinishedGood::find($this->finished_goods_id);
        if (! $fg) {
            return [];
        }

        $preview = [];
        $shortfallFound = false;

        foreach ($fg->bomLines()->with(['bahanBaku.inventoryParameter'])->get() as $line) {
            $bb = $line->bahanBaku;
            if ($bb === null) {
                continue;
            }

            $required = (float) $line->qty_per_unit * $this->jumlah_diproduksi;
            $available = (float) $bb->stok_saat_ini;
            $rop = (float) ($bb->inventoryParameter->reorder_point ?? 0.0);

            $isInsufficient = $available < $required;
            $isNearRop = ! $isInsufficient && (($available - $required) <= $rop);

            if ($isInsufficient) {
                $shortfallFound = true;
            }

            $preview[] = [
                'id' => $bb->id,
                'kode' => $bb->kode,
                'nama' => $bb->nama,
                'satuan' => $bb->satuan,
                'required' => $required,
                'available' => $available,
                'rop' => $rop,
                'is_insufficient' => $isInsufficient,
                'is_near_rop' => $isNearRop,
            ];
        }

        // Expose shortfall flag to control form button state
        $this->hasStockShortfall = $shortfallFound;

        return $preview;
    }

    /**
     * Record the production run.
     */
    public function save(ProductionService $productionService)
    {
        $this->authorize('create', ProductionEntry::class);

        $this->validate([
            'finished_goods_id' => 'required|exists:finished_goods,id',
            'jumlah_diproduksi' => 'required|numeric|gt:0',
            'tanggal_produksi' => 'required|date',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $fg = FinishedGood::findOrFail($this->finished_goods_id);

        if ($fg->bomLines()->count() === 0) {
            $this->addError('finished_goods_id', "Barang jadi '{$fg->nama}' tidak memiliki resep BOM yang aktif.");

            return;
        }

        // Server-side check for sufficient stock (additional verification)
        foreach ($fg->bomLines as $line) {
            $required = (float) $line->qty_per_unit * $this->jumlah_diproduksi;
            if ($line->bahanBaku->stok_saat_ini < $required) {
                $this->addError('jumlah_diproduksi', "Gagal: Stok untuk '{$line->bahanBaku->nama}' tidak mencukupi.");

                return;
            }
        }

        try {
            $productionService->recordProduction(
                $fg,
                $this->jumlah_diproduksi,
                auth()->user(),
                $this->tanggal_produksi,
                $this->keterangan
            );

            $this->dispatch('notify', message: 'Hasil produksi berhasil dicatat dan stok bahan baku dikurangi.', type: 'success');

            return redirect()->route('production.index');
        } catch (\Exception $e) {
            $this->addError('jumlah_diproduksi', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Render view.
     */
    public function render()
    {
        $goods = FinishedGood::orderBy('nama')->get();

        return view('livewire.production.create-production', [
            'goods' => $goods,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Catat Produksi Baru',
            'pageSubtitle' => 'Input laporan hasil produksi harian CV Akuna',
        ]);
    }
}
