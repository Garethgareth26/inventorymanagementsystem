<?php

namespace App\Livewire\Inventory;

use App\Models\BahanBaku;
use App\Models\FinishedGood;
use App\Models\MutasiStok;
use App\Services\AuditLogger;
use App\Services\DashboardQueryService;
use App\Services\StockMutationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Component to record manual Stock Adjustments.
 */
class StockAdjustment extends Component
{
    use AuthorizesRequests;

    public string $item_type = 'bahan_baku'; // 'bahan_baku' or 'finished_good'

    public ?int $item_id = null;

    public string $jenis_mutasi = MutasiStok::JENIS_MASUK; // 'masuk' or 'keluar'

    public float $jumlah = 0.0;

    public string $keterangan = '';

    // Advisory warning fields
    public bool $showAdvisoryWarning = false;

    public bool $confirm_large_adjustment = false;

    public float $avgMonthly = 0.0;

    /**
     * Mount component.
     */
    public function mount(): void
    {
        $this->authorize('create', MutasiStok::class);
    }

    /**
     * Reset selected item when item type changes.
     */
    public function updatedItemType(): void
    {
        $this->item_id = null;
        $this->jumlah = 0.0;
        $this->showAdvisoryWarning = false;
        $this->confirm_large_adjustment = false;
    }

    /**
     * Trigger check when item_id or jumlah is updated.
     */
    public function updatedItemId(): void
    {
        $this->checkAdjustmentLimits();
    }

    public function updatedJumlah(): void
    {
        $this->checkAdjustmentLimits();
    }

    public function updatedJenisMutasi(): void
    {
        $this->checkAdjustmentLimits();
    }

    /**
     * Check if adjustment exceeds 3x average monthly movement.
     */
    private function checkAdjustmentLimits(): void
    {
        if (! $this->item_id || $this->jumlah <= 0) {
            $this->showAdvisoryWarning = false;

            return;
        }

        // Query last 12 months mutations for average monthly calculation
        $query = MutasiStok::query()
            ->where('jenis_mutasi', $this->jenis_mutasi)
            ->whereDate('tanggal', '>=', now()->subMonths(12)->toDateString());

        if ($this->item_type === 'bahan_baku') {
            $query->where('bahan_baku_id', $this->item_id);
        } else {
            $query->where('finished_goods_id', $this->item_id);
        }

        $totalVal = (float) $query->sum('jumlah');
        $this->avgMonthly = $totalVal / 12.0;

        // If amount > 3x average monthly (and average is greater than 0, to avoid dividing by zero warnings)
        if ($this->avgMonthly > 0 && $this->jumlah > (3.0 * $this->avgMonthly)) {
            $this->showAdvisoryWarning = true;
        } else {
            $this->showAdvisoryWarning = false;
        }
    }

    /**
     * Record adjustment.
     */
    public function save(StockMutationService $mutationService)
    {
        $this->authorize('create', MutasiStok::class);

        $rules = [
            'item_type' => 'required|in:bahan_baku,finished_good',
            'item_id' => 'required|integer',
            'jenis_mutasi' => 'required|in:masuk,keluar',
            'jumlah' => 'required|numeric|gt:0',
            'keterangan' => 'required|string|max:500',
        ];

        $this->validate($rules);

        // Perform stock limit validation
        if ($this->item_type === 'bahan_baku') {
            $item = BahanBaku::findOrFail($this->item_id);
        } else {
            $item = FinishedGood::findOrFail($this->item_id);
        }

        // Hard negative stock block
        if ($this->jenis_mutasi === MutasiStok::JENIS_KELUAR && $item->stok_saat_ini < $this->jumlah) {
            $this->addError('jumlah', "Stok tidak mencukupi. Stok saat ini: {$item->stok_saat_ini} {$item->satuan}.");

            return;
        }

        // Check advisory limit warning
        if ($this->showAdvisoryWarning && ! $this->confirm_large_adjustment) {
            $this->addError('jumlah', 'Jumlah penyesuaian terdeteksi sangat besar (> 3x rata-rata bulanan). Harap centang konfirmasi jika tindakan ini disengaja.');

            return;
        }

        try {
            DB::transaction(function () use ($mutationService, $item) {
                $mutation = $mutationService->recordMutation(
                    itemType: $this->item_type,
                    itemId: $item->id,
                    jenisMutasi: $this->jenis_mutasi,
                    jumlah: $this->jumlah,
                    tanggal: now()->toDateString(),
                    actor: auth()->user(),
                    sumber: MutasiStok::SUMBER_MANUAL,
                    keterangan: '[Penyesuaian Stok] '.$this->keterangan
                );

                AuditLogger::log(
                    auth()->user(),
                    'stock.adjust',
                    $mutation,
                    null,
                    $mutation->toArray()
                );

                app(DashboardQueryService::class)->invalidateCache();
            });

            $this->dispatch('notify', message: 'Penyesuaian stok berhasil dicatat.', type: 'success');

            return redirect()->route('dashboard'); // Redirect to dashboard or movements page
        } catch (\Exception $e) {
            $this->addError('jumlah', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Render view.
     */
    public function render()
    {
        $items = [];
        if ($this->item_type === 'bahan_baku') {
            $items = BahanBaku::orderBy('nama')->get();
        } else {
            $items = FinishedGood::orderBy('nama')->get();
        }

        return view('livewire.inventory.stock-adjustment', [
            'itemsList' => $items,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Penyesuaian Stok',
            'pageSubtitle' => 'Catat penyesuaian stok manual (Opname gudang)',
        ]);
    }
}
