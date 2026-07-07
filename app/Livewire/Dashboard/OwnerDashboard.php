<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use App\Services\DashboardQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Full-page Livewire component for the Owner Dashboard.
 *
 * Enforces Owner-only access, delegates all calculations/queries to
 * DashboardQueryService, and exposes structured metrics to the Blade view.
 */
class OwnerDashboard extends Component
{
    use AuthorizesRequests;

    /**
     * Render the Owner Dashboard view.
     */
    public function render(DashboardQueryService $queryService)
    {
        $this->authorize('viewOwnerDashboard', User::class);

        $metrics = $queryService->getOwnerMetrics();
        $chartData = $queryService->getChartData();
        $criticalStock = $queryService->getCriticalStockList();
        $recentActivity = $queryService->getRecentActivities();
        $upcomingReorders = $queryService->getUpcomingReorders();

        return view('livewire.dashboard.owner-dashboard', [
            'metrics' => $metrics,
            'chartData' => $chartData,
            'criticalStock' => $criticalStock,
            'recentActivity' => $recentActivity,
            'upcomingReorders' => $upcomingReorders,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Dashboard',
            'pageSubtitle' => 'Selamat datang, '.auth()->user()->name,
        ]);
    }
}
