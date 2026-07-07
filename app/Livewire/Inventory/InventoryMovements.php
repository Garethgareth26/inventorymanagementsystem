<?php

namespace App\Livewire\Inventory;

use App\Models\MutasiStok;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Component to display the read-only Inventory Movements ledger.
 */
class InventoryMovements extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $item_type = ''; // 'bahan_baku', 'finished_good', or ''

    public string $filterJenis = ''; // 'masuk', 'keluar', or ''

    public string $filterSumber = ''; // 'manual', 'po_penerimaan', 'produksi', or ''

    public string $filterStartDate = '';

    public string $filterEndDate = '';

    /**
     * Reset pagination when search or filters update.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedItemType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterJenis(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSumber(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedFilterEndDate(): void
    {
        $this->resetPage();
    }

    /**
     * Build base query with active filters.
     */
    private function getFilteredQuery()
    {
        $query = MutasiStok::query()->with(['bahanBaku', 'finishedGood', 'dicatatOleh']);

        if (! empty($this->item_type)) {
            if ($this->item_type === 'bahan_baku') {
                $query->whereNotNull('bahan_baku_id');
            } elseif ($this->item_type === 'finished_good') {
                $query->whereNotNull('finished_goods_id');
            }
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->whereHas('bahanBaku', function ($sub) {
                    $sub->where('nama', 'like', '%'.$this->search.'%')
                        ->orWhere('kode', 'like', '%'.$this->search.'%');
                })->orWhereHas('finishedGood', function ($sub) {
                    $sub->where('nama', 'like', '%'.$this->search.'%')
                        ->orWhere('kode', 'like', '%'.$this->search.'%');
                });
            });
        }

        if (! empty($this->filterJenis)) {
            $query->where('jenis_mutasi', $this->filterJenis);
        }

        if (! empty($this->filterSumber)) {
            $query->where('sumber', $this->filterSumber);
        }

        if (! empty($this->filterStartDate)) {
            $query->whereDate('tanggal', '>=', $this->filterStartDate);
        }

        if (! empty($this->filterEndDate)) {
            $query->whereDate('tanggal', '<=', $this->filterEndDate);
        }

        return $query;
    }

    /**
     * Export movements ledger to CSV respecting active filters.
     */
    public function exportCsv()
    {
        $this->authorize('viewAny', MutasiStok::class);

        $query = $this->getFilteredQuery();
        $mutations = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=mutasi_stok_'.now()->format('Ymd_His').'.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($mutations) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['Tanggal', 'Kode Item', 'Nama Item', 'Tipe Item', 'Jenis Mutasi', 'Jumlah', 'Sumber', 'Referensi ID', 'Dicatat Oleh', 'Keterangan']);

            foreach ($mutations as $m) {
                $item = $m->bahanBaku ?? $m->finishedGood;
                fputcsv($file, [
                    $m->tanggal->format('Y-m-d'),
                    $item->kode ?? '—',
                    $item->nama ?? '—',
                    $m->bahan_baku_id ? 'Bahan Baku' : 'Barang Jadi',
                    $m->jenis_mutasi,
                    $m->jumlah,
                    $m->sumber,
                    $m->po_id ?? $m->production_entry_id ?? '—',
                    $m->dicatatOleh->name ?? '—',
                    $m->keterangan ?? '—',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Render view.
     */
    public function render()
    {
        $this->authorize('viewAny', MutasiStok::class);

        $query = $this->getFilteredQuery();
        $movements = $query->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(50);

        return view('livewire.inventory.inventory-movements', [
            'movements' => $movements,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Mutasi Stok',
            'pageSubtitle' => 'Buku besar histori keluar-masuk persediaan barang',
        ]);
    }
}
