<?php

namespace App\Livewire\Optimization;

use App\Models\BahanBaku;
use App\Models\InventoryParameter;
use App\Services\OptimizationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * EOQ Overview — searchable, paginated table of all BahanBaku with their
 * current EOQ parameters. Links to EoqSimulation per material.
 */
class EoqOverview extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(OptimizationService $service): View
    {
        $this->authorize('viewAny', InventoryParameter::class);

        $query = BahanBaku::with('inventoryParameter')
            ->when(
                $this->search,
                fn ($q) => $q->where('nama', 'like', "%{$this->search}%")
                    ->orWhere('kode', 'like', "%{$this->search}%")
            )
            ->orderBy('nama');

        $materials = $query->paginate(25);

        return view('livewire.optimization.eoq-overview', [
            'materials' => $materials,
        ])->layout('components.layout.app', [
            'pageTitle' => 'EOQ — Economic Order Quantity',
            'pageSubtitle' => 'Tinjauan dan simulasi jumlah pemesanan ekonomis per bahan baku',
        ]);
    }
}
