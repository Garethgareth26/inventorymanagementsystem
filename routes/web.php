<?php

use App\Http\Controllers\ProfileController;
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
    Route::get('/owner/dashboard', function () {
        return view('dashboard.owner');
    })->name('owner.dashboard');

    // ── Employee Dashboard ──────────────────────────────────────────────
    Route::get('/employee/dashboard', function () {
        return view('dashboard.employee');
    })->name('employee.dashboard');

    // ── Profile ─────────────────────────────────────────────────────────
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Module Route Stubs — Sprint 2+ (these make sidebar links resolvable)
    |
    | Each stub returns a minimal placeholder view until the real controller
    | is implemented. This prevents route() calls in the sidebar from throwing
    | exceptions during Sprint 1.
    |--------------------------------------------------------------------------
    */
    $placeholder = fn (string $title) => function () use ($title) {
        return view('placeholder', ['title' => $title]);
    };

    // Master Data
    Route::get('/suppliers',                $placeholder('Suppliers'))->name('suppliers.index');
    Route::get('/suppliers/create',         $placeholder('Add Supplier'))->name('suppliers.create');
    Route::get('/bahan-baku',               $placeholder('Raw Materials'))->name('bahan_baku.index');
    Route::get('/bahan-baku/create',        $placeholder('Add Raw Material'))->name('bahan_baku.create');
    Route::get('/barang-jadi',              $placeholder('Finished Goods'))->name('barang_jadi.index');
    Route::get('/barang-jadi/create',       $placeholder('Add Finished Good'))->name('barang_jadi.create');
    Route::get('/bom',                      $placeholder('Bill of Materials'))->name('bom.index');

    // Operations
    Route::get('/pesanan-pembelian',        $placeholder('Purchase Orders'))->name('pesanan_pembelian.index');
    Route::get('/production',               $placeholder('Production'))->name('production.index');
    Route::get('/mutasi-stok',              $placeholder('Inventory Assets'))->name('mutasi_stok.index');

    // Optimization
    Route::get('/eoq',                      $placeholder('EOQ Analysis'))->name('eoq.index');
    Route::get('/safety-stock',             $placeholder('Safety Stock'))->name('safety_stock.index');
    Route::get('/reorder-point',            $placeholder('Reorder Point'))->name('reorder_point.index');
    Route::get('/abc-analysis',             $placeholder('ABC Analysis'))->name('abc_analysis.index');

    // Reports
    Route::get('/reports',                  $placeholder('Reports'))->name('reports.index');

    // Administration (Owner only — capability enforced in real controllers later)
    Route::get('/user-management',          $placeholder('User Management'))->name('user_management.index');
    Route::get('/settings',                 $placeholder('Settings'))->name('settings.index');
});

require __DIR__ . '/auth.php';
