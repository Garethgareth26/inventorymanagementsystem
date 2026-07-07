<?php

namespace App\Livewire\Navigation;

use App\Models\BahanBaku;
use Livewire\Component;

class NotificationBell extends Component
{
    public $criticalCount = 0;
    public $criticalItems = [];

    public function mount()
    {
        $this->loadCriticalStock();
    }

    public function loadCriticalStock()
    {
        // Simple logic for critical stock: stok_saat_ini <= rop
        // But since rop is dynamically calculated in the view, we can just fetch the raw materials
        // and evaluate if it's critical. To be fast, we use the DB if we have ROP stored.
        // Actually, we can just use the service or a direct query if ROP is stored.
        // In the dashboard, it is queried via ReorderPointSimulation->calculateRop().
        // For performance in header, let's just use a simple heuristic or fetch 
        // the top 5 critical items.

        // To reuse the exact logic from Dashboard:
        $materials = BahanBaku::with('inventoryParameter')->get();
        
        $critical = [];
        foreach ($materials as $bb) {
            $rop = (float) ($bb->inventoryParameter?->reorder_point ?? 0.0);
            if ($bb->inventoryParameter && $bb->stok_saat_ini <= $rop) {
                $critical[] = [
                    'id' => $bb->id,
                    'kode' => $bb->kode,
                    'nama' => $bb->nama,
                    'defisit' => $rop - $bb->stok_saat_ini,
                ];
            }
        }

        $this->criticalCount = count($critical);
        $this->criticalItems = array_slice($critical, 0, 5); // Show top 5
    }

    public function render()
    {
        return view('livewire.navigation.notification-bell');
    }
}
