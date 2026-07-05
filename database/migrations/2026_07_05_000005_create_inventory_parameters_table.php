<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inventory parameters table — one active row per raw material.
     *
     * Stores the official EOQ, Safety Stock, and Reorder Point parameters
     * for each bahan_baku. Per ADR-004, changes to these values are NOT
     * versioned in a separate history table; instead, old/new values are
     * captured in audit_logs when a Karyawan applies new simulation results.
     */
    public function up(): void
    {
        Schema::create('inventory_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_baku_id')
                ->unique()
                ->constrained('bahan_baku')
                ->cascadeOnDelete()
                ->comment('One active parameter set per material (ADR-004)');

            // Demand & variability inputs (derived from mutasi_stok history)
            $table->decimal('kebutuhan_tahunan', 15, 4)->nullable()
                ->comment('D — annual demand (sum of keluar mutations over history window)');
            $table->decimal('standar_deviasi_harian', 15, 4)->nullable()
                ->comment('SD_harian — daily standard deviation of demand');

            // Cost parameters (seeded defaults from Calculation Parameters settings)
            $table->decimal('biaya_pesan', 15, 2)->nullable()
                ->comment('S — order cost per order (default Rp 75.000)');
            $table->decimal('biaya_simpan_persen', 5, 4)->nullable()
                ->comment('H% — holding cost as fraction of unit price (default 0.20)');

            // Calculated official values
            $table->decimal('eoq', 15, 4)->nullable()
                ->comment('EOQ = sqrt(2DS/H)');
            $table->decimal('safety_stock', 15, 4)->nullable()
                ->comment('SS = Z × SD_harian × sqrt(lead_time_hari)');
            $table->decimal('reorder_point', 15, 4)->nullable()
                ->comment('ROP = (D/365 × lead_time_hari) + SS');

            // Seeded / user-configurable overrides (per Settings → Calculation Parameters)
            $table->decimal('z_factor', 5, 4)->default(1.65)
                ->comment('Service level Z-factor (default 1.65 = 95%)');
            $table->unsignedInteger('historical_window_months')->default(12)
                ->comment('Months of mutasi_stok history used to compute D and SD');

            // Audit
            $table->foreignId('last_applied_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Karyawan who last applied these parameters');
            $table->timestamp('last_applied_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_parameters');
    }
};
