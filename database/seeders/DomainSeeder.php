<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orchestrates all domain model seeders in strict FK dependency order.
 *
 * Dependency order:
 *   Roles → Users → Suppliers → BahanBaku → FinishedGood → BOM
 *   → SystemSettings → PesananPembelian → MutasiStok (PO receipts + consumption)
 *   → ProductionEntry (uses MutasiStok stock levels) → InventoryParameter (uses MutasiStok history)
 */
class DomainSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Auth / RBAC (M-2.2 seeders, always run first)
            RoleSeeder::class,
            UserSeeder::class,

            // Master data (FK order: Suppliers → BahanBaku → FinishedGood → BOM)
            SupplierSeeder::class,
            BahanBakuSeeder::class,
            FinishedGoodSeeder::class,
            BomSeeder::class,

            // System configuration
            SystemSettingsSeeder::class,

            // Operations (PO first so MutasiStok can link po_id)
            PesananPembelianSeeder::class,

            // Stock mutation history (depends on POs for receipt mutations)
            MutasiStokSeeder::class,

            // Production history (depends on sufficient stock from MutasiStokSeeder)
            ProductionEntrySeeder::class,

            // Inventory parameters (depends on MutasiStok history for D/SD computation)
            InventoryParameterSeeder::class,
        ]);
    }
}
