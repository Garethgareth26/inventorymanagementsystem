<?php

namespace App\Providers;

use App\Models\BahanBaku;
use App\Models\Bom;
use App\Models\FinishedGood;
use App\Models\InventoryParameter;
use App\Models\MutasiStok;
use App\Models\PesananPembelian;
use App\Models\ProductionEntry;
use App\Models\Supplier;
use App\Policies\BahanBakuPolicy;
use App\Policies\BomPolicy;
use App\Policies\FinishedGoodPolicy;
use App\Policies\ParameterPolicy;
use App\Policies\ProcurementPolicy;
use App\Policies\ProductionPolicy;
use App\Policies\StockMutationPolicy;
use App\Policies\SupplierPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        // Force HTTPS in production (Cloud Run behind load balancer)
        if (config('app.env') === 'production' || str_contains(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Gate::policy(PesananPembelian::class, ProcurementPolicy::class);
        Gate::policy(ProductionEntry::class, ProductionPolicy::class);
        Gate::policy(MutasiStok::class, StockMutationPolicy::class);
        Gate::policy(InventoryParameter::class, ParameterPolicy::class);
        Gate::policy(BahanBaku::class, BahanBakuPolicy::class);
        Gate::policy(FinishedGood::class, FinishedGoodPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Bom::class, BomPolicy::class);
    }
}
