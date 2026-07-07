<?php

namespace App\Livewire\Optimization;

use App\Models\BahanBaku;
use App\Models\InventoryParameter;
use App\Services\OptimizationService;
use App\Services\SystemSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Safety Stock Simulation — editable inputs with live preview.
 *
 * Editable fields: lead_time_hari, z_factor, historical_window_months.
 * Changing the historical window triggers a server-side SD recalculation
 * via OptimizationService::computeSdForWindow().
 */
class SafetyStockSimulation extends Component
{
    use AuthorizesRequests;

    public BahanBaku $bahanBaku;

    // ── Simulation inputs ─────────────────────────────────────────────────────
    public float $zFactor = 1.65;

    public int $leadTimeHari = 1;

    public int $windowMonths = 12;

    // ── Context (auto-computed from history, updated when window changes) ─────
    public float $sdHarian = 0.0;

    public float $annualDemand = 0.0;

    // ── Biaya for Apply ───────────────────────────────────────────────────────
    public float $biayaPesan = 75000.0;

    public float $biayaSimpanPct = 20.0;

    // ── Simulation output ─────────────────────────────────────────────────────
    public ?float $simSafetyStock = null;

    public ?float $simRop = null;

    public ?float $simEoq = null;

    public bool $simulated = false;

    // ── UI state ─────────────────────────────────────────────────────────────
    public bool $confirmApply = false;

    public function mount(BahanBaku $bahanBaku, OptimizationService $service): void
    {
        $this->authorize('simulate', InventoryParameter::class);
        $this->bahanBaku = $bahanBaku;

        $data = $service->getParameterData($bahanBaku);

        $this->annualDemand = round($data['annual_demand'], 4);
        $this->sdHarian = round($data['sd_harian'], 4);
        $this->leadTimeHari = (int) $bahanBaku->lead_time_hari;
        $this->windowMonths = $data['window_months'];

        $param = $data['param'];
        if ($param) {
            $this->zFactor = (float) $param->z_factor;
            $this->biayaPesan = (float) $param->biaya_pesan;
            $this->biayaSimpanPct = round((float) $param->biaya_simpan_persen * 100.0, 2);
        } else {
            $this->zFactor = SystemSettings::getFloat('z_factor', 1.65);
            $this->biayaPesan = SystemSettings::getFloat('biaya_pesan', 75000.0);
            $this->biayaSimpanPct = SystemSettings::getFloat('biaya_simpan', 20.0);
        }
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * When the historical window changes, recompute SD from the mutation ledger.
     */
    public function updatedWindowMonths(OptimizationService $service): void
    {
        if ($this->windowMonths >= 1) {
            $this->sdHarian = round($service->computeSdForWindow($this->bahanBaku, $this->windowMonths), 4);
            $this->simulated = false;
        }
    }

    public function simulate(OptimizationService $service): void
    {
        $this->authorize('simulate', InventoryParameter::class);

        $this->validate([
            'zFactor' => 'required|numeric|min:0.01|max:4',
            'leadTimeHari' => 'required|integer|min:1',
            'windowMonths' => 'required|integer|min:1|max:24',
        ]);

        $result = $service->simulate($this->bahanBaku, [
            'annual_demand' => $this->annualDemand,
            'biaya_pesan' => $this->biayaPesan,
            'biaya_simpan_persen' => $this->biayaSimpanPct / 100.0,
            'z_factor' => $this->zFactor,
            'sd_harian' => $this->sdHarian,
            'lead_time_hari' => $this->leadTimeHari,
        ]);

        $this->simSafetyStock = $result['safety_stock'];
        $this->simRop = $result['reorder_point'];
        $this->simEoq = $result['eoq'];
        $this->simulated = true;
    }

    public function resetToDefaults(): void
    {
        $this->zFactor = SystemSettings::getFloat('z_factor', 1.65);
        $this->windowMonths = SystemSettings::getInt('historical_window', 12);
        $this->simulated = false;
        $this->confirmApply = false;
        $this->dispatch('notify', message: 'Input direset ke nilai default sistem.', type: 'info');
    }

    public function apply(OptimizationService $service): void
    {
        $this->authorize('apply', InventoryParameter::class);

        $this->validate([
            'zFactor' => 'required|numeric|min:0.01|max:4',
            'leadTimeHari' => 'required|integer|min:1',
            'windowMonths' => 'required|integer|min:1|max:24',
        ]);

        $service->apply($this->bahanBaku, [
            'annual_demand' => $this->annualDemand,
            'biaya_pesan' => $this->biayaPesan,
            'biaya_simpan_persen' => $this->biayaSimpanPct / 100.0,
            'z_factor' => $this->zFactor,
            'sd_harian' => $this->sdHarian,
            'lead_time_hari' => $this->leadTimeHari,
            'historical_window_months' => $this->windowMonths,
        ], auth()->user());

        $this->confirmApply = false;
        $this->simulated = false;
        $this->dispatch('notify', message: 'Parameter Safety Stock berhasil diterapkan.', type: 'success');
    }

    public function render(): View
    {
        return view('livewire.optimization.safety-stock-simulation', [
            'currentParam' => $this->bahanBaku->inventoryParameter,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Simulasi Safety Stock — '.$this->bahanBaku->nama,
            'pageSubtitle' => 'Hitung dan terapkan stok pengaman optimal',
        ]);
    }
}
