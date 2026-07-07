<?php

namespace App\Providers;

use App\Models\MutasiStok;
use App\Models\PesananPembelian;
use App\Models\ProductionEntry;
use App\Policies\ProcurementPolicy;
use App\Policies\ProductionPolicy;
use App\Policies\StockMutationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(PesananPembelian::class, ProcurementPolicy::class);
        Gate::policy(ProductionEntry::class, ProductionPolicy::class);
        Gate::policy(MutasiStok::class, StockMutationPolicy::class);
    }
}
