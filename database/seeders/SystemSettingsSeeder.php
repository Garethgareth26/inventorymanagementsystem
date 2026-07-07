<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Seeds system_settings with pre-configured defaults per the UI Specification.
 *
 * Pre-seeded defaults per roadmap:
 *   z_factor               = 1.65   (95% service level)
 *   abc_threshold_a        = 80     (top 80% cumulative usage = Class A)
 *   abc_threshold_b        = 95     (top 95% cumulative usage = Class B)
 *   historical_window      = 12     (months of mutasi_stok used for D/SD computation)
 *   biaya_pesan            = 75000  (order cost per PO, Rp)
 *   biaya_simpan_pct       = 20     (holding cost as % of unit price)
 *   company_name           = CV Akuna
 *   polling_interval_seconds = 15  (dashboard wire:poll interval)
 */
class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'z_factor' => '1.65',
            'abc_threshold_a' => '80',
            'abc_threshold_b' => '95',
            'historical_window' => '12',
            'biaya_pesan' => '75000',
            'biaya_simpan_pct' => '20',
            'company_name' => 'CV Akuna',
            'company_address' => 'Jl. Industri Bakery No. 1, Surabaya',
            'polling_interval_seconds' => '15',
        ];

        foreach ($defaults as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Flush any stale cache entries so SystemSettings service picks up fresh values
        Cache::flush();
    }
}
