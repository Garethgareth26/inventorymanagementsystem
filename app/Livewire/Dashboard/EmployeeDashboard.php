<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use App\Services\DashboardQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Full-page Livewire component for the Employee Dashboard.
 *
 * Enforces Karyawan-only access, displays pending tasks, critical stock,
 * own activity feeds, and provides quick navigation actions.
 */
class EmployeeDashboard extends Component
{
    use AuthorizesRequests;

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
}
