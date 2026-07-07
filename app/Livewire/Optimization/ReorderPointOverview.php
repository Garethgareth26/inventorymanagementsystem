<?php

namespace App\Livewire\Optimization;

use App\Models\InventoryParameter;
use App\Services\OptimizationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Reorder Point Overview — all materials with 3-tier status badges.
 *
 * Status:
 *   critical → stok_saat_ini <= ROP
 *   near     → stok_saat_ini <= 1.2 × ROP
 *   ok       → otherwise
 *
 * Critical rows expose a "Buat PO Darurat" quick action that redirects to
 * the existing CreatePurchaseOrder page.
 */
class ReorderPointOverview extends Component
{
    use AuthorizesRequests;

    public string $search = '';

    public string $filterStatus = '';  // 'critical' | 'near' | 'ok' | ''

    public function render(OptimizationService $service): View
    {
        $this->authorize('viewAny', InventoryParameter::class);

        $all = $service->getReorderPointStatus();

        // Apply search filter in PHP (data set is small — ≤10 materials per seed)
        if ($this->search) {
            $search = mb_strtolower($this->search);
            $all = array_filter(
                $all,
                fn ($item) => str_contains(mb_strtolower($item['nama']), $search)
                    || str_contains(mb_strtolower($item['kode']), $search)
            );
        }

        // Status filter
        if ($this->filterStatus) {
            $all = array_filter($all, fn ($item) => $item['status'] === $this->filterStatus);
        }

        $counts = [
            'critical' => count(array_filter($all, fn ($i) => $i['status'] === 'critical')),
            'near' => count(array_filter($all, fn ($i) => $i['status'] === 'near')),
            'ok' => count(array_filter($all, fn ($i) => $i['status'] === 'ok')),
        ];

        return view('livewire.optimization.reorder-point-overview', [
            'materials' => array_values($all),
            'counts' => $counts,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Reorder Point — Titik Pemesanan Ulang',
            'pageSubtitle' => 'Pantau status stok terhadap titik pemesanan ulang',
        ]);
    }
}
