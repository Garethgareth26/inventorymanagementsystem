<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * System-wide key-value configuration store.
     *
     * Provides a Redis-backed (with DB fallback) settings store.
     * Written by SystemSettings service; read by CalculationEngine,
     * InventoryParameterSeeder, and the System Settings UI (Group 6).
     *
     * Pre-seeded by SystemSettingsSeeder with defaults:
     *   z_factor=1.65, abc_threshold_a=80, abc_threshold_b=95,
     *   historical_window=12, biaya_pesan=75000, biaya_simpan_pct=20.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()
                ->comment('Setting key, e.g. z_factor, biaya_pesan');
            $table->text('value')->nullable()
                ->comment('Setting value stored as string; cast by SystemSettings service');
            $table->timestamps();

            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
