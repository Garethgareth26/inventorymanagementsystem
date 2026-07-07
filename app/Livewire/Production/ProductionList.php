<?php

namespace App\Livewire\Production;

use App\Models\ProductionEntry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * ProductionList Component.
 */
class ProductionList extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    /**
     * Reset pagination on search query update.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Render view.
     */
    public function render()
    {
        $this->authorize('viewAny', ProductionEntry::class);

        $query = ProductionEntry::query()->with(['finishedGood', 'dicatatOleh']);

        if (! empty($this->search)) {
            $query->whereHas('finishedGood', function ($q) {
                $q->where('kode', 'like', '%'.$this->search.'%')
                    ->orWhere('nama', 'like', '%'.$this->search.'%');
            });
        }

        $entries = $query->orderBy('tanggal_produksi', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25);

        return view('livewire.production.production-list', [
            'entries' => $entries,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Riwayat Produksi',
            'pageSubtitle' => 'Kelola dan pantau catatan hasil produksi barang jadi',
        ]);
    }
}
