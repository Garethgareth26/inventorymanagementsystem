<?php

namespace App\Livewire\Dashboard;

use App\Imports\FullExcelImport;
use App\Models\User;
use App\Services\DashboardQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Full-page Livewire component for the Employee Dashboard.
 *
 * Enforces Karyawan-only access, displays pending tasks, critical stock,
 * own activity feeds, and provides quick navigation actions.
 */
class EmployeeDashboard extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public $importFile;

    /**
     * Render the Employee Dashboard view.
     */
    public function render(DashboardQueryService $queryService)
    {
        $this->authorize('viewEmployeeDashboard', User::class);

        $metrics = $queryService->getEmployeeMetrics();
        $criticalStock = $queryService->getCriticalStockList();
        $recentActivity = $queryService->getRecentActivities(auth()->user());
        $upcomingReorders = $queryService->getUpcomingReorders();

        return view('livewire.dashboard.employee-dashboard', [
            'metrics' => $metrics,
            'criticalStock' => $criticalStock,
            'recentActivity' => $recentActivity,
            'upcomingReorders' => $upcomingReorders,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Dashboard',
            'pageSubtitle' => 'Selamat datang, '.auth()->user()->name,
        ]);
    }

    public function import(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv|max:10240', // max 10MB
        ]);

        try {
            DB::transaction(function () {
                Excel::import(new FullExcelImport, $this->importFile->getRealPath());
            });

            $this->dispatch('notify', message: 'Data Master, Parameter ABC, dan Pemesanan berhasil diimport!', type: 'success');

            // Invalidate cache and reset state
            app(DashboardQueryService::class)->invalidateCache();
            $this->importFile = null;
            $this->dispatch('close-modal', 'import-modal');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Gagal mengimport data: '.$e->getMessage(), type: 'danger');
        }
    }
}
