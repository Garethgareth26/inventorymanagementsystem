<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Dashboard\EmployeeDashboard;
use App\Livewire\Dashboard\OwnerDashboard;
use App\Livewire\MasterData\BahanBaku;
use App\Livewire\MasterData\BomEditor;
use App\Livewire\MasterData\FinishedGoods;
use App\Livewire\MasterData\Suppliers;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Fallback dashboard route — redirects to role-appropriate dashboard
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user?->isOwner()) {
            return redirect()->route('owner.dashboard');
        }

        return redirect()->route('employee.dashboard');
    })->name('dashboard');

    // ── Owner Dashboard ─────────────────────────────────────────────────
    Route::get('/owner/dashboard', OwnerDashboard::class)->name('owner.dashboard');

    // ── Employee Dashboard ──────────────────────────────────────────────
    Route::get('/employee/dashboard', EmployeeDashboard::class)->name('employee.dashboard');

    // ── Profile ─────────────────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Master Data
    Route::get('/suppliers', Suppliers::class)->name('suppliers.index');
    Route::get('/bahan-baku', BahanBaku::class)->name('bahan_baku.index');
    Route::get('/barang-jadi', FinishedGoods::class)->name('barang_jadi.index');
    Route::get('/bom', function () {
        return redirect()->route('barang_jadi.index');
    })->name('bom.index');
    Route::get('/bom/{finishedGood}', BomEditor::class)->name('bom.edit');

    // Operations
    Route::get('/pesanan-pembelian', function () {
        return view('pesanan_pembelian.index');
    })->name('pesanan_pembelian.index');
    Route::get('/production', function () {
        return view('production.index');
    })->name('production.index');
    Route::get('/mutasi-stok', function () {
        return view('mutasi_stok.index');
    })->name('mutasi_stok.index');

    // Optimization
    Route::get('/eoq', function () {
        return view('eoq.index');
    })->name('eoq.index');
    Route::get('/safety-stock', function () {
        return view('safety_stock.index');
    })->name('safety_stock.index');
    Route::get('/reorder-point', function () {
        return view('reorder_point.index');
    })->name('reorder_point.index');
    Route::get('/abc-analysis', function () {
        return view('abc_analysis.index');
    })->name('abc_analysis.index');

    // Reports
    Route::get('/reports', function () {
        return view('reports.index');
    })->name('reports.index');

    // Administration (Owner only — capability enforced in real controllers later)
    Route::get('/user-management', function () {
        return view('user_management.index');
    })->name('user_management.index');
    Route::get('/settings', function () {
        return view('settings.index');
    })->name('settings.index');

    // Design System Showcase
    Route::get('/design-system', function () {
        return view('design-system');
    })->name('design-system');
});

require __DIR__.'/auth.php';
