<?php

namespace App\Livewire\Optimization;

use App\Models\BahanBaku;
use App\Models\InventoryParameter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Safety Stock Overview — paginated table of all BahanBaku with current
 * SD Harian, lead time, Z-factor, and Safety Stock values.
 */
class SafetyStockOverview extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('viewAny', InventoryParameter::class);

        $materials = BahanBaku::with('inventoryParameter')
            ->when(
                $this->search,
                fn ($q) => $q->where('nama', 'like', "%{$this->search}%")
                    ->orWhere('kode', 'like', "%{$this->search}%")
            )
            ->orderBy('nama')
            ->paginate(25);

        return view('livewire.optimization.safety-stock-overview', [
            'materials' => $materials,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Safety Stock — Stok Pengaman',
            'pageSubtitle' => 'Tinjauan dan simulasi stok pengaman per bahan baku',
        ]);
    }
}
