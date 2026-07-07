<?php

namespace App\Livewire\Optimization;

use App\Models\InventoryParameter;
use App\Services\DashboardQueryService;
use App\Services\OptimizationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

/**
 * ABC Analysis page.
 *
 * Chart data (donut + Top-5) is reused from DashboardQueryService::getChartData()
 * — no ABC aggregation is duplicated here.
 *
 * The per-material detail table (with individual % and cumulative %) comes
 * from OptimizationService::getAbcTable() which performs a live query.
 *
 * Donut segment clicks filter the table via Alpine → Livewire event.
 */
class AbcAnalysis extends Component
{
    use AuthorizesRequests;

    public string $filterKelas = '';   // 'A' | 'B' | 'C' | ''

    public string $search = '';

    public function setFilter(string $kelas): void
    {
        $this->filterKelas = $this->filterKelas === $kelas ? '' : $kelas;
    }

    public function render(
        DashboardQueryService $dashboardService,
        OptimizationService $optimizationService
    ): View {
        $this->authorize('viewAny', InventoryParameter::class);

        // ── Chart data from shared cache (Group 2 infrastructure) ────────────
        $chartData = $dashboardService->getChartData();

        // ── Detail table (live query, per-material breakdown) ─────────────────
        $table = $optimizationService->getAbcTable();

        if ($this->filterKelas) {
            $table = array_filter($table, fn ($row) => $row['kelas'] === $this->filterKelas);
        }

        if ($this->search) {
            $search = mb_strtolower($this->search);
            $table = array_filter(
                $table,
                fn ($row) => str_contains(mb_strtolower($row['nama']), $search)
                    || str_contains(mb_strtolower($row['kode']), $search)
            );
        }

        return view('livewire.optimization.abc-analysis', [
            'chartData' => $chartData,
            'table' => array_values($table),
        ])->layout('components.layout.app', [
            'pageTitle' => 'Analisis ABC',
            'pageSubtitle' => 'Klasifikasi bahan baku berdasarkan nilai pemakaian tahunan',
        ]);
    }
}
