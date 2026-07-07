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
 * EOQ Simulation — editable inputs with live preview.
 *
 * Editable fields: annual_demand (D), biaya_pesan (S), biaya_simpan_pct (H%).
 * Owner can simulate only; Apply is restricted to Karyawan (parameter.apply).
 */
class EoqSimulation extends Component
{
    use AuthorizesRequests;

    public BahanBaku $bahanBaku;

    // ── Simulation inputs ─────────────────────────────────────────────────────
    public ?float $annualDemand = 0.0;

    public ?float $biayaPesan = 75000.0;

    public ?float $biayaSimpanPct = 20.0;  // Displayed as % (20 = 20%), stored as 0.20 in DB

    // ── Read-only context (filled from current param / material) ──────────────
    public ?float $sdHarian = 0.0;

    public int $leadTimeHari = 1;

    public ?float $zFactor = 1.65;

    public int $windowMonths = 12;

    // ── Simulation output ─────────────────────────────────────────────────────
    public ?float $simEoq = null;

    public ?float $simSafetyStock = null;

    public ?float $simRop = null;

    public ?float $simHoldingCost = null;

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
            $this->biayaPesan = (float) $param->biaya_pesan;
            $this->biayaSimpanPct = round((float) $param->biaya_simpan_persen * 100.0, 2);
            $this->zFactor = (float) $param->z_factor;
        } else {
            $this->biayaPesan = SystemSettings::getFloat('biaya_pesan', 75000.0);
            $this->biayaSimpanPct = SystemSettings::getFloat('biaya_simpan', 20.0);
            $this->zFactor = SystemSettings::getFloat('z_factor', 1.65);
        }
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function simulate(OptimizationService $service): void
    {
        $this->authorize('simulate', InventoryParameter::class);

        $this->validate([
            'annualDemand' => 'required|numeric|min:0',
            'biayaPesan' => 'required|numeric|min:0',
            'biayaSimpanPct' => 'required|numeric|min:0.01|max:100',
        ]);

        $result = $service->simulate($this->bahanBaku, [
            'annual_demand' => $this->annualDemand,
            'biaya_pesan' => $this->biayaPesan,
            'biaya_simpan_persen' => $this->biayaSimpanPct / 100.0,
            'z_factor' => $this->zFactor,
            'sd_harian' => $this->sdHarian,
            'lead_time_hari' => $this->leadTimeHari,
        ]);

        $this->simEoq = $result['eoq'];
        $this->simSafetyStock = $result['safety_stock'];
        $this->simRop = $result['reorder_point'];
        $this->simHoldingCost = $result['holding_cost'];
        $this->simulated = true;
    }

    public function resetToDefaults(): void
    {
        $this->biayaPesan = SystemSettings::getFloat('biaya_pesan', 75000.0);
        $this->biayaSimpanPct = SystemSettings::getFloat('biaya_simpan', 20.0);
        $this->zFactor = SystemSettings::getFloat('z_factor', 1.65);
        $this->simulated = false;
        $this->confirmApply = false;
        $this->dispatch('notify', message: 'Input direset ke nilai default sistem.', type: 'info');
    }

    public function apply(OptimizationService $service): void
    {
        $this->authorize('apply', InventoryParameter::class);

        $this->validate([
            'annualDemand' => 'required|numeric|min:0',
            'biayaPesan' => 'required|numeric|min:0',
            'biayaSimpanPct' => 'required|numeric|min:0.01|max:100',
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

        $this->dispatch('notify', message: 'Parameter EOQ berhasil diterapkan.', type: 'success');
    }

    public function render(): View
    {
        return view('livewire.optimization.eoq-simulation', [
            'currentParam' => $this->bahanBaku->inventoryParameter,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Simulasi EOQ — '.$this->bahanBaku->nama,
            'pageSubtitle' => 'Hitung dan terapkan jumlah pemesanan ekonomis optimal',
        ]);
    }
}
