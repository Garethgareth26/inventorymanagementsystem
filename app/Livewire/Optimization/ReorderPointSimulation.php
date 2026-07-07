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
 * Reorder Point Simulation.
 *
 * Editable: daily demand (D/365), lead time, safety stock (manual override).
 * Apply → OptimizationService::apply() (Karyawan only).
 */
class ReorderPointSimulation extends Component
{
    use AuthorizesRequests;

    public BahanBaku $bahanBaku;

    // ── Simulation inputs ─────────────────────────────────────────────────────
    public ?float $dailyDemand = 0.0;   // D/365

    public ?int $leadTimeHari = 1;

    public ?float $safetyStock = 0.0;   // manual override or from param

    // ── Context fields ────────────────────────────────────────────────────────
    public ?float $annualDemand = 0.0;

    public ?float $sdHarian = 0.0;

    public ?float $zFactor = 1.65;

    public int $windowMonths = 12;

    public ?float $biayaPesan = 75000.0;

    public ?float $biayaSimpanPct = 20.0;

    // ── Simulation output ─────────────────────────────────────────────────────
    public ?float $simRop = null;

    public bool $simulated = false;

    // ── UI state ─────────────────────────────────────────────────────────────
    public bool $confirmApply = false;

    public function mount(BahanBaku $bahanBaku, OptimizationService $service): void
    {
        $this->authorize('simulate', InventoryParameter::class);
        $this->bahanBaku = $bahanBaku;

        $data = $service->getParameterData($bahanBaku);

        $this->annualDemand = round($data['annual_demand'], 4);
        $this->dailyDemand = round($this->annualDemand / 365.0, 4);
        $this->sdHarian = round($data['sd_harian'], 4);
        $this->leadTimeHari = (int) $bahanBaku->lead_time_hari;
        $this->windowMonths = $data['window_months'];

        $param = $data['param'];
        if ($param) {
            $this->safetyStock = round((float) $param->safety_stock, 4);
            $this->zFactor = (float) $param->z_factor;
            $this->biayaPesan = (float) $param->biaya_pesan;
            $this->biayaSimpanPct = round((float) $param->biaya_simpan_persen * 100.0, 2);
        } else {
            $this->safetyStock = 0.0;
            $this->zFactor = SystemSettings::getFloat('z_factor', 1.65);
            $this->biayaPesan = SystemSettings::getFloat('biaya_pesan', 75000.0);
            $this->biayaSimpanPct = SystemSettings::getFloat('biaya_simpan', 20.0);
        }
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function simulate(OptimizationService $service): void
    {
        $this->authorize('simulate', InventoryParameter::class);

        $this->validate([
            'dailyDemand' => 'required|numeric|min:0',
            'leadTimeHari' => 'required|integer|min:1',
            'safetyStock' => 'required|numeric|min:0',
        ]);

        // ROP = (dailyDemand × leadTime) + safetyStock
        $computedAnnual = $this->dailyDemand * 365.0;

        $result = $service->simulate($this->bahanBaku, [
            'annual_demand' => $computedAnnual,
            'biaya_pesan' => $this->biayaPesan,
            'biaya_simpan_persen' => $this->biayaSimpanPct / 100.0,
            'z_factor' => $this->zFactor,
            'sd_harian' => $this->sdHarian,
            'lead_time_hari' => $this->leadTimeHari,
        ]);

        // Override ROP using manual safety stock if user has changed it
        $this->simRop = ($this->dailyDemand * $this->leadTimeHari) + $this->safetyStock;
        $this->simulated = true;
    }

    public function apply(OptimizationService $service): void
    {
        $this->authorize('apply', InventoryParameter::class);

        $this->validate([
            'dailyDemand' => 'required|numeric|min:0',
            'leadTimeHari' => 'required|integer|min:1',
            'safetyStock' => 'required|numeric|min:0',
        ]);

        $computedAnnual = $this->dailyDemand * 365.0;

        $service->apply($this->bahanBaku, [
            'annual_demand' => $computedAnnual,
            'biaya_pesan' => $this->biayaPesan,
            'biaya_simpan_persen' => $this->biayaSimpanPct / 100.0,
            'z_factor' => $this->zFactor,
            'sd_harian' => $this->sdHarian,
            'lead_time_hari' => $this->leadTimeHari,
            'historical_window_months' => $this->windowMonths,
        ], auth()->user());

        $this->confirmApply = false;
        $this->simulated = false;
        $this->dispatch('notify', message: 'Parameter Reorder Point berhasil diterapkan.', type: 'success');
    }

    public function render(): View
    {
        return view('livewire.optimization.reorder-point-simulation', [
            'currentParam' => $this->bahanBaku->inventoryParameter,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Simulasi Reorder Point — '.$this->bahanBaku->nama,
            'pageSubtitle' => 'Hitung dan terapkan titik pemesanan ulang optimal',
        ]);
    }
}
